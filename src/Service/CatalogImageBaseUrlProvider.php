<?php

namespace App\Service;

use App\Entity\CatalogImageConfiguration;
use App\Repository\CatalogImageConfigurationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\ApiReceptionException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class CatalogImageBaseUrlProvider
{
    private const CACHE_VERSION_KEY = 'catalog_images.cache_version';
    private ?string $version = null;
    private ?CatalogImageConfiguration $configuration = null;

    public function __construct(
        private readonly CatalogImageConfigurationRepository $configurations,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $fallbackBaseUrl,
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $imageCache,
    ) {
    }

    public function getConfiguration(): CatalogImageConfiguration
    {
        if ($this->configuration !== null) {
            return $this->configuration;
        }
        $configuration = $this->configurations->findDefault();
        if ($configuration instanceof CatalogImageConfiguration) {
            return $this->configuration = $configuration;
        }
        $configuration = new CatalogImageConfiguration();
        $this->entityManager->persist($configuration);
        $this->entityManager->flush();

        return $this->configuration = $configuration;
    }

    public function baseUrl(): string
    {
        $configured = trim((string) $this->getConfiguration()->getBaseUrl());
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        return rtrim($this->fallbackBaseUrl ?: '/ebpsynch', '/');
    }

    public function imageUrl(
        string $uniqueId,
        \DateTimeInterface|string|null $modifiedAt = null,
        ?string $baseUrl = null
    ): string
    {
        // Keep the former imageUrl($uniqueId, $baseUrl) call valid for any
        // integration still using the provider directly.
        if (is_string($modifiedAt)) {
            $baseUrl = $modifiedAt;
            $modifiedAt = null;
        }

        return rtrim($baseUrl ?? $this->baseUrl(), '/') . '/' . rawurlencode($uniqueId) . '.png?v=' . $this->imageVersion($uniqueId, $modifiedAt);
    }

    /**
     * The manual cache purge stays global, while the article modification date
     * makes a browser revalidate only the image whose EBP record changed.
     */
    public function imageVersion(string $uniqueId, ?\DateTimeInterface $modifiedAt = null): string
    {
        $globalPurgeVersion = $this->cacheVersion();
        if ($modifiedAt === null) {
            return $globalPurgeVersion;
        }

        $articleVersion = substr(hash('sha256', strtoupper(trim($uniqueId)) . '|' . $modifiedAt->format('U.u')), 0, 20);

        return $globalPurgeVersion . '-' . $articleVersion;
    }

    public function cacheVersion(): string
    {
        return $this->localCacheVersion();
    }

    private function localCacheVersion(): string
    {
        if ($this->version !== null) {
            return $this->version;
        }
        $item = $this->imageCache->getItem(self::CACHE_VERSION_KEY);
        $version = $item->get();
        if ($item->isHit() && is_string($version) && preg_match('/^[a-f0-9]{32}$/D', $version)) {
            return $this->version = $version;
        }

        return $this->purgeCache();
    }

    public function purgeCache(): string
    {
        $version = bin2hex(random_bytes(16));
        $item = $this->imageCache->getItem(self::CACHE_VERSION_KEY);
        $item->set($version)->expiresAfter(null);
        if (!$this->imageCache->save($item)) {
            throw new ApiReceptionException('Le cache des photos n’a pas pu être renouvelé. Réessayez dans quelques instants.');
        }

        return $this->version = $version;
    }
}

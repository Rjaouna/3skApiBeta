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
        private readonly ?CatalogPhotoPublicationVersion $publications = null,
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

    public function imageUrl(string $uniqueId, ?string $baseUrl = null): string
    {
        return rtrim($baseUrl ?? $this->baseUrl(), '/') . '/' . rawurlencode($uniqueId) . '.png?v=' . $this->cacheVersion();
    }

    public function cacheVersion(): string
    {
        $local = $this->localCacheVersion();

        return $this->publications === null ? $local : $local . '-' . $this->publications->current();
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

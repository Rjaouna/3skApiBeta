<?php

namespace App\Service;

use App\Exception\ApiReceptionException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Shares successful FTPS publication revisions without querying the API for each card. */
final class CatalogPhotoPublicationVersion
{
    private ?string $revision = null;

    public function __construct(
        private readonly ApiReceptionConnectionProvider $connections,
        private readonly ApiReceptionClient $client,
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
    ) {
    }

    public function current(): string
    {
        if ($this->revision !== null) {
            return $this->revision;
        }
        $connection = $this->connections->get();
        if (!$connection->getBaseUrl() || !$connection->hasApiKey()) {
            return $this->revision = 'initial';
        }
        $key = 'catalog_photos.publication.' . hash('sha256', $connection->getBaseUrl() . $connection->getApiKeyCiphertext());
        $item = $this->cache->getItem($key);
        $state = $item->isHit() ? $item->get() : null;
        $revision = is_array($state) ? ($state['revision'] ?? 'initial') : 'initial';
        if (!is_array($state) || ($state['checkAfter'] ?? 0) <= time()) {
            try {
                $revision = $this->client->photoPublicationRevision($connection);
                $checkAfter = time() + 5;
            } catch (ApiReceptionException) {
                // Keep the last known version when the connector is temporarily unreachable.
                $checkAfter = time() + 30;
            }
            $this->cache->save($item->set(['revision' => $revision, 'checkAfter' => $checkAfter]));
        }

        return $this->revision = $revision;
    }
}

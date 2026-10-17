<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ImportSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class MissingArticleImageService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ArticleImageLocator $images,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return list<array{uniqueId: string, code: string, designation: string, brand: string, vehicle: string, modifiedAt: ?string}>
     */
    public function getMissingArticles(): array
    {
        $source = $this->getImportSource();
        $cacheKey = $this->cacheKey($source);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($source): array {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);

            return $this->scan($source?->getDirectoryPath());
        });
    }

    /**
     * @return array{items: list<array{uniqueId: string, code: string, designation: string, brand: string, vehicle: string, modifiedAt: ?string}>, total: int, page: int, pages: int, limit: int}
     */
    public function getPage(int $page, int $limit, string $query = ''): array
    {
        $items = $this->getMissingArticles();
        $query = mb_strtolower(trim($query), 'UTF-8');

        if ($query !== '') {
            $items = array_values(array_filter($items, static function (array $article) use ($query): bool {
                $haystack = mb_strtolower(implode(' ', [
                    $article['code'],
                    $article['designation'],
                    $article['brand'],
                    $article['vehicle'],
                ]), 'UTF-8');

                return str_contains($haystack, $query);
            }));
        }

        $total = count($items);
        $limit = max(10, min(100, $limit));
        $pages = max(1, (int) ceil($total / $limit));
        $page = max(1, min($page, $pages));

        return [
            'items' => array_slice($items, ($page - 1) * $limit, $limit),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'limit' => $limit,
        ];
    }

    /**
     * Returns null when the article does not exist, otherwise its current image state.
     */
    public function check(string $uniqueId): ?bool
    {
        $article = $this->entityManager->find(Article::class, $uniqueId);
        if (!$article instanceof Article) {
            return null;
        }

        $source = $this->getImportSource();
        $detected = $this->images->find($uniqueId, $source?->getDirectoryPath()) !== null;

        if ($detected) {
            $this->cache->delete($this->cacheKey($source));
        }

        return $detected;
    }

    private function getImportSource(): ?ImportSource
    {
        $source = $this->entityManager->getRepository(ImportSource::class)->findOneBy([]);

        return $source instanceof ImportSource ? $source : null;
    }

    private function cacheKey(?ImportSource $source): string
    {
        return sprintf(
            'admin.missing_article_images.%s',
            sha1(implode('|', [
                (string) $source?->getId(),
                (string) $source?->getDirectoryPath(),
                (string) ($source?->getUpdatedAt()?->getTimestamp() ?? ''),
            ]))
        );
    }

    /**
     * @return list<array{uniqueId: string, code: string, designation: string, brand: string, vehicle: string, modifiedAt: ?string}>
     */
    private function scan(?string $importDirectory): array
    {
        $rows = $this->entityManager->getRepository(Article::class)
            ->createQueryBuilder('article')
            ->select([
                'article.uniqueId AS uniqueId',
                'article.id AS code',
                'article.desComClear AS designation',
                'article.xxMarque AS brand',
                'article.xxVehicule AS vehicle',
                'article.sysModifiedDate AS modifiedAt',
            ])
            ->orderBy('article.sysModifiedDate', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $missing = [];
        foreach ($rows as $row) {
            $uniqueId = trim((string) ($row['uniqueId'] ?? ''));
            if ($uniqueId === '' || $this->images->find($uniqueId, $importDirectory) !== null) {
                continue;
            }

            $modifiedAt = $row['modifiedAt'] ?? null;
            $missing[] = [
                'uniqueId' => $uniqueId,
                'code' => trim((string) ($row['code'] ?? '')),
                'designation' => trim((string) ($row['designation'] ?? '')),
                'brand' => trim((string) ($row['brand'] ?? '')),
                'vehicle' => trim((string) ($row['vehicle'] ?? '')),
                'modifiedAt' => $modifiedAt instanceof \DateTimeInterface
                    ? $modifiedAt->format(\DateTimeInterface::ATOM)
                    : (is_string($modifiedAt) ? $modifiedAt : null),
            ];
        }

        return $missing;
    }
}

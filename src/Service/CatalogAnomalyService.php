<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ImportSource;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CatalogAnomalyService
{
    public const TYPE_MISSING_IMAGE = 'missing_image';
    public const TYPE_ZERO_PRICE = 'zero_price';

    private const CACHE_TTL_SECONDS = 300;

    /** @var list<string> */
    private const TYPES = [self::TYPE_MISSING_IMAGE, self::TYPE_ZERO_PRICE];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ArticleImageLocator $images,
        #[Autowire(service: 'cache.app')]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array{total: int, missingImageCount: int, zeroPriceCount: int}
     */
    public function getSummary(): array
    {
        $snapshot = $this->getSnapshot();
        $affectedArticleIds = [];

        foreach (self::TYPES as $type) {
            foreach ($snapshot[$type] as $article) {
                $affectedArticleIds[$article['uniqueId']] = true;
            }
        }

        return [
            'total' => count($affectedArticleIds),
            'missingImageCount' => count($snapshot[self::TYPE_MISSING_IMAGE]),
            'zeroPriceCount' => count($snapshot[self::TYPE_ZERO_PRICE]),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int, limit: int}
     */
    public function getPage(string $type, int $page, int $limit, string $query = ''): array
    {
        if (!$this->supportsType($type)) {
            throw new \InvalidArgumentException(sprintf('Unknown anomaly type "%s".', $type));
        }

        $items = $this->getSnapshot()[$type];
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

    public function supportsType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /**
     * @return array{imageDetected: bool, hasZeroPrice: bool}|null
     */
    public function checkImage(string $uniqueId): ?array
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

        return [
            'imageDetected' => $detected,
            'hasZeroPrice' => $this->hasZeroPrice($article->getSalePriceVatIncluded()),
        ];
    }

    /**
     * Recalculates all anomalies only when an administrator explicitly requests it.
     *
     * @return array{total: int, missingImageCount: int, zeroPriceCount: int}
     */
    public function refresh(): array
    {
        $source = $this->getImportSource();
        $this->cache->delete($this->cacheKey($source));

        return $this->getSummary();
    }

    private function getImportSource(): ?ImportSource
    {
        $source = $this->entityManager->getRepository(ImportSource::class)->findOneBy([]);

        return $source instanceof ImportSource ? $source : null;
    }

    /**
     * @return array{missing_image: list<array<string, mixed>>, zero_price: list<array<string, mixed>>}
     */
    private function getSnapshot(): array
    {
        $source = $this->getImportSource();
        $cacheKey = $this->cacheKey($source);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($source): array {
            $item->expiresAfter(self::CACHE_TTL_SECONDS);

            return $this->scan($source?->getDirectoryPath());
        });
    }

    private function cacheKey(?ImportSource $source): string
    {
        $state = $this->entityManager->getRepository(Article::class)
            ->createQueryBuilder('article')
            ->select('COUNT(article.uniqueId) AS articleCount')
            ->addSelect('MAX(article.sysModifiedDate) AS lastModified')
            ->getQuery()
            ->getSingleResult();

        return sprintf(
            'admin.catalog_anomalies.%s',
            sha1(implode('|', [
                (string) $source?->getId(),
                (string) $source?->getDirectoryPath(),
                (string) ($source?->getUpdatedAt()?->getTimestamp() ?? ''),
                (string) ($state['articleCount'] ?? ''),
                (string) ($state['lastModified'] ?? ''),
            ])),
        );
    }

    /**
     * @return array{missing_image: list<array<string, mixed>>, zero_price: list<array<string, mixed>>}
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
                'article.salePriceVatIncluded AS price',
                'article.sysModifiedDate AS modifiedAt',
            ])
            ->orderBy('article.sysModifiedDate', 'DESC')
            ->addOrderBy('article.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $snapshot = [
            self::TYPE_MISSING_IMAGE => [],
            self::TYPE_ZERO_PRICE => [],
        ];

        foreach ($rows as $row) {
            $uniqueId = trim((string) ($row['uniqueId'] ?? ''));
            if ($uniqueId === '') {
                continue;
            }

            $price = trim((string) ($row['price'] ?? '0.00'));
            $hasZeroPrice = $this->hasZeroPrice($price);
            $hasMissingImage = $this->images->find($uniqueId, $importDirectory) === null;
            $article = $this->normalizeArticle($row, $price, $hasZeroPrice, $hasMissingImage);

            if ($hasMissingImage) {
                $snapshot[self::TYPE_MISSING_IMAGE][] = $article;
            }

            if ($hasZeroPrice) {
                $snapshot[self::TYPE_ZERO_PRICE][] = $article;
            }
        }

        return $snapshot;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{uniqueId: string, code: string, designation: string, brand: string, vehicle: string, price: string, modifiedAt: ?string, hasZeroPrice: bool, hasMissingImage: bool}
     */
    private function normalizeArticle(array $row, string $price, bool $hasZeroPrice, bool $hasMissingImage): array
    {
        $modifiedAt = $row['modifiedAt'] ?? null;

        return [
            'uniqueId' => trim((string) ($row['uniqueId'] ?? '')),
            'code' => trim((string) ($row['code'] ?? '')),
            'designation' => trim((string) ($row['designation'] ?? '')),
            'brand' => trim((string) ($row['brand'] ?? '')),
            'vehicle' => trim((string) ($row['vehicle'] ?? '')),
            'price' => $price,
            'modifiedAt' => $modifiedAt instanceof \DateTimeInterface
                ? $modifiedAt->format(\DateTimeInterface::ATOM)
                : (is_string($modifiedAt) ? $modifiedAt : null),
            'hasZeroPrice' => $hasZeroPrice,
            'hasMissingImage' => $hasMissingImage,
        ];
    }

    private function hasZeroPrice(string $price): bool
    {
        return (float) str_replace(',', '.', $price) === 0.0;
    }
}

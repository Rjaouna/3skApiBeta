<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Service\CatalogImageBaseUrlProvider;
use App\Service\PricingService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ArticleApiController extends AbstractController
{
    public function __construct(
        private readonly PricingService $pricingService,
        private readonly CatalogImageBaseUrlProvider $images,
    ) {
    }

    #[Route('/articles', name: 'articles_list', methods: ['GET'])]
    public function list(
        Request $request,
        EntityManagerInterface $em,
        CartItemRepository $cartItemRepo
    ): JsonResponse {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(1000, (int) $request->query->get('limit', 100)));

        $q = trim((string) $request->query->get('q', ''));
        $family = trim((string) $request->query->get('family', ''));

        $brand = trim((string) $request->query->get('brand', ''));
        $marque = trim((string) $request->query->get('marque', ''));
        $marque = $brand !== '' ? $brand : $marque;

        $vehicle = trim((string) $request->query->get('vehicle', ''));
        $vehicule = trim((string) $request->query->get('vehicule', ''));
        $vehicule = $vehicle !== '' ? $vehicle : $vehicule;

        $anneeFrom = (int) $request->query->get('anneeFrom', 0);
        $anneeTo = (int) $request->query->get('anneeTo', 0);
        $annee = trim((string) $request->query->get('annee', ''));

        $inStock = (string) $request->query->get('inStock', '');

        $onlyNew = (string) $request->query->get('new', '') === '1';
        $newDays = max(1, min(365, (int) $request->query->get('newDays', 5)));
        $sortByCreated = (string) $request->query->get('sort', '') === 'created_desc';

        $needFacets = (string) $request->query->get('facets', '') === '1';

        $qb = $em->getRepository(Article::class)->createQueryBuilder('a');

        $catalogSearchTokens = $this->catalogSearchTokens($q);

        if ($family !== '') {
            $qb->andWhere('a.familyName = :family')->setParameter('family', $family);
        }

        if ($marque !== '') {
            $qb->andWhere('a.xxMarque LIKE :marque')->setParameter('marque', '%' . $marque . '%');
        }

        if ($vehicule !== '') {
            $qb->andWhere('a.xxVehicule LIKE :veh')->setParameter('veh', '%' . $vehicule . '%');
        }

        if ($anneeFrom > 0 || $anneeTo > 0) {
            if ($anneeFrom <= 0) {
                $anneeFrom = $anneeTo;
            }
            if ($anneeTo <= 0) {
                $anneeTo = $anneeFrom;
            }
            if ($anneeFrom > $anneeTo) {
                [$anneeFrom, $anneeTo] = [$anneeTo, $anneeFrom];
            }

            $orX = $qb->expr()->orX();
            for ($y = $anneeFrom; $y <= $anneeTo; $y++) {
                $p = 'y' . $y;
                $orX->add($qb->expr()->like('a.xxAnnee', ':' . $p));
                $qb->setParameter($p, '%' . $y . '%');
            }
            $qb->andWhere($orX);
        } elseif ($annee !== '') {
            $qb->andWhere('a.xxAnnee LIKE :annee')->setParameter('annee', '%' . $annee . '%');
        }

        if ($inStock === '1') {
            $qb->andWhere('a.realStock > 0');
        }

        $cutoff = null;
        if ($onlyNew || $sortByCreated) {
            $cutoff = (new \DateTimeImmutable('now'))->modify('-' . $newDays . ' days');
            $qb->andWhere('a.sysCreatedDate IS NOT NULL');
        }
        if ($onlyNew) {
            $qb->andWhere('a.sysCreatedDate >= :cutoff')
                ->setParameter('cutoff', $cutoff);
        }

        if ($catalogSearchTokens !== []) {
            if ($sortByCreated) {
                $qb->orderBy('a.sysCreatedDate', 'DESC')->addOrderBy('a.uniqueId', 'ASC');
            }
            /** @var Article[] $candidates */
            $candidates = $qb->getQuery()->getResult();
            $items = [];

            foreach ($candidates as $candidate) {
                if ($this->matchesCatalogSearch($candidate, $catalogSearchTokens)) {
                    $items[] = $candidate;
                }
            }

            if (!$sortByCreated) {
                $this->sortLikeLegacyCatalog($items);
            }
            $total = count($items);
            $items = array_slice($items, ($page - 1) * $limit, $limit);
        } else {
            $total = $this->countTotal($qb);

            $qb->orderBy($sortByCreated ? 'a.sysCreatedDate' : 'a.sysModifiedDate', 'DESC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit);
            if ($sortByCreated) {
                $qb->addOrderBy('a.uniqueId', 'ASC');
            }

            /** @var Article[] $items */
            $items = $qb->getQuery()->getResult();
        }

        $currentUser = $this->getUser();
        $currentUser = $currentUser instanceof User ? $currentUser : null;

        $codes = [];
        foreach ($items as $article) {
            if ($article->getId()) {
                $codes[] = $article->getId();
            }
        }

        $pressureMap = $cartItemRepo->getPressureMapForArticleCodes($codes, $currentUser);

        [$yearMin, $yearMax] = $this->getYearBounds($em);

        $facets = null;
        if ($needFacets) {
            $facets = $this->getFacets($em);
        }

        $norm = [];
        foreach ($items as $article) {
            $pricing = $this->pricingService->computeArticlePricing($article, $currentUser);

            $norm[] = $this->normalize(
                $article,
                $cutoff,
                $pressureMap[$article->getId()] ?? null,
                $pricing
            );
        }

        $payload = [
            'ok' => true,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int) ceil($total / $limit),
            'items' => $norm,
            'imagesBaseUrl' => $this->images->baseUrl(),
            'yearMin' => $yearMin,
            'yearMax' => $yearMax,
            'new' => $onlyNew ? 1 : 0,
            'newDays' => $newDays,
        ];

        if ($facets !== null) {
            $payload['facets'] = $facets;
        }

        return $this->json($payload);
    }

    #[Route('/search/suggestions', name: 'search_suggestions', methods: ['GET'])]
    public function suggestions(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));
        $limit = max(4, min(10, (int) $request->query->get('limit', 8)));

        if (mb_strlen($query, 'UTF-8') < 2) {
            return $this->json([
                'ok' => true,
                'query' => $query,
                'items' => [],
                'searchUrl' => $this->generateUrl('articles_page', ['q' => $query]),
            ]);
        }

        $normalizedQuery = $this->normalizeSuggestionText($query);
        $tokens = $this->suggestionTokens($normalizedQuery);
        $fragments = $this->suggestionFragments($query, $tokens);

        if ($normalizedQuery === '' || $fragments === []) {
            return $this->json([
                'ok' => true,
                'query' => $query,
                'items' => [],
                'searchUrl' => $this->generateUrl('articles_page', ['q' => $query]),
            ]);
        }

        $qb = $em->getRepository(Article::class)->createQueryBuilder('a');
        $orX = $qb->expr()->orX();
        $fields = [
            'a.id',
            'a.desComClear',
            'a.uniqueId',
            'a.xxVehicule',
            'a.xxMarque',
            'a.familyName',
            'a.xxPosition',
            'a.xxAnnee',
        ];

        foreach ($fragments as $index => $fragment) {
            $parameter = 'fragment' . $index;
            foreach ($fields as $field) {
                $orX->add($qb->expr()->like($field, ':' . $parameter));
            }
            $qb->setParameter($parameter, '%' . $fragment . '%');
        }

        $candidates = $qb
            ->andWhere($orX)
            ->orderBy('a.sysModifiedDate', 'DESC')
            ->setMaxResults(140)
            ->getQuery()
            ->getResult();

        $scored = [];
        foreach ($candidates as $article) {
            if (!$article instanceof Article || !$article->getId()) {
                continue;
            }

            $score = $this->scoreSuggestionArticle($article, $normalizedQuery, $tokens);
            if ($score < 18) {
                continue;
            }

            $scored[] = [
                'score' => $score,
                'stock' => $article->getRealStock(),
                'article' => $article,
            ];
        }

        usort($scored, static function (array $a, array $b): int {
            if ($a['score'] === $b['score']) {
                return $b['stock'] <=> $a['stock'];
            }

            return $b['score'] <=> $a['score'];
        });

        $items = [];
        foreach (array_slice($scored, 0, $limit) as $row) {
            /** @var Article $article */
            $article = $row['article'];
            $items[] = $this->normalizeSuggestion($article, (int) $row['score']);
        }

        return $this->json([
            'ok' => true,
            'query' => $query,
            'items' => $items,
            'searchUrl' => $this->generateUrl('articles_page', ['q' => $query]),
        ]);
    }

    private function normalize(
        Article $article,
        ?\DateTimeImmutable $cutoffForNew,
        ?array $pressure,
        array $pricing
    ): array {
        $isNew = false;
        if ($cutoffForNew && $article->getSysCreatedDate()) {
            $isNew = $article->getSysCreatedDate() >= $cutoffForNew;
        }

        return [
            'UniqueId' => $article->getUniqueId(),
            'imageUrl' => $this->images->imageUrl((string) $article->getUniqueId(), $article->getSysModifiedDate()),
            'Id' => $article->getId(),
            'DesComClear' => $article->getDesComClear(),
            'RealStock' => $article->getRealStock(),

            'SalePriceVatIncluded' => $article->getSalePriceVatIncluded(),
            'SalePriceVatExcluded' => $article->getSalePriceVatExcluded(),
            'SalePriceVatIncludedFinal' => $pricing['unitPriceFinalTtcFormatted'],
            'SalePriceVatExcludedFinal' => $pricing['unitPriceFinalHtFormatted'],

            'xx_Position' => $article->getXxPosition(),
            'xx_correspondance' => null,
            'xx_OEM' => null,
            'xx_code_web' => null,
            'xx_Annee' => $article->getXxAnnee(),
            'xx_Vehicule' => $article->getXxVehicule(),
            'xx_Marque' => $article->getXxMarque(),

            'sysModifiedDate' => $article->getSysModifiedDate()?->format(\DateTimeInterface::ATOM),
            'sysCreatedDate' => $article->getSysCreatedDate()?->format(\DateTimeInterface::ATOM),
            'FamilyName' => $article->getFamilyName(),
            'ExportStartedAt' => $article->getExportStartedAt()?->format(\DateTimeInterface::ATOM),
            'FtpFinishedAt' => $article->getFtpFinishedAt()?->format(\DateTimeInterface::ATOM),

            'isNew' => $isNew,
            'cartAlert' => $this->buildCartAlertPayload($article, $pressure),

            'reduction' => $pricing['globalReductionFormatted'],
            'baseReduction' => $pricing['baseReduction'] > 0 ? $this->pricingService->formatRate($pricing['baseReduction']) : null,
            'personalReduction' => $pricing['personalReduction'] > 0 ? $this->pricingService->formatRate($pricing['personalReduction']) : null,
            'globalReduction' => $pricing['globalReduction'] > 0 ? $this->pricingService->formatRate($pricing['globalReduction']) : null,
        ];
    }

    private function buildCartAlertPayload(Article $article, ?array $pressure): array
    {
        $stock = max(0, (int) $article->getRealStock());
        $totalReserved = (int) ($pressure['totalReserved'] ?? 0);
        $myQty = (int) ($pressure['myQty'] ?? 0);
        $usersCount = (int) ($pressure['usersCount'] ?? 0);

        $hasConflict = $totalReserved > $stock;
        $showBadge = $myQty > 0 && $usersCount > 1 && $hasConflict;

        return [
            'showBadge' => $showBadge,
            'hasConflict' => $hasConflict,
            'myQty' => $myQty,
            'totalReserved' => $totalReserved,
            'stock' => $stock,
            'missingQty' => max(0, $totalReserved - $stock),
            'usersCount' => $usersCount,
            'message' => $showBadge
                ? sprintf(
                    'Attention : %d article(s) sont déjà dans des paniers pour un stock de %d. Le premier à commander sera servi.',
                    $totalReserved,
                    $stock
                )
                : null,
        ];
    }

    private function countTotal(QueryBuilder $qb): int
    {
        $countQb = clone $qb;
        $countQb->select('COUNT(a.uniqueId)');

        return (int) $countQb->getQuery()->getSingleScalarResult();
    }

    /**
     * Reproduit la recherche du catalogue historique 07052026 :
     * chaque mot saisi doit etre present dans le bloc texte de l'article.
     *
     * @return list<string>
     */
    private function catalogSearchTokens(string $query): array
    {
        $query = mb_strtolower(trim($query), 'UTF-8');
        if ($query === '') {
            return [];
        }

        $tokens = preg_split('/\s+/', $query) ?: [];
        $tokens = array_filter($tokens, static fn (string $token): bool => $token !== '');

        return array_values($tokens);
    }

    /**
     * Meme composition que buildSearchBlob() dans 07052026/index.php.
     */
    private function buildLegacyCatalogSearchBlob(Article $article): string
    {
        return mb_strtolower(implode(' ', [
            (string) $article->getDesComClear(),
            (string) $article->getId(),
            (string) $article->getXxMarque(),
            (string) $article->getXxVehicule(),
            (string) $article->getFamilyName(),
            (string) $article->getXxPosition(),
            (string) $article->getXxAnnee(),
        ]), 'UTF-8');
    }

    /**
     * @param list<string> $tokens
     */
    private function matchesCatalogSearch(Article $article, array $tokens): bool
    {
        $blob = $this->buildLegacyCatalogSearchBlob($article);

        foreach ($tokens as $token) {
            if (!str_contains($blob, $token)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tri "Pertinence" du catalogue historique : stock decroissant puis designation.
     *
     * @param list<Article> $items
     */
    private function sortLikeLegacyCatalog(array &$items): void
    {
        usort($items, static function (Article $a, Article $b): int {
            $stockDiff = $b->getRealStock() <=> $a->getRealStock();
            if ($stockDiff !== 0) {
                return $stockDiff;
            }

            return strcasecmp((string) $a->getDesComClear(), (string) $b->getDesComClear());
        });
    }

    private function getYearBounds(EntityManagerInterface $em): array
    {
        $conn = $em->getConnection();

        $sql = <<<SQL
SELECT
  MIN(CAST(SUBSTRING(xx_Annee, 1, 4) AS UNSIGNED)) AS yMin,
  MAX(CAST(RIGHT(xx_Annee, 4) AS UNSIGNED)) AS yMax
FROM article
WHERE xx_Annee IS NOT NULL
  AND xx_Annee <> ''
  AND xx_Annee REGEXP '^[0-9]{4}'
  AND xx_Annee REGEXP '[0-9]{4}$'
SQL;

        $row = $conn->fetchAssociative($sql) ?: ['yMin' => null, 'yMax' => null];

        $yMin = isset($row['yMin']) ? (int) $row['yMin'] : 0;
        $yMax = isset($row['yMax']) ? (int) $row['yMax'] : 0;

        if ($yMin <= 0 || $yMax <= 0 || $yMin > $yMax) {
            $yMin = 2000;
            $yMax = (int) (new \DateTimeImmutable('now'))->format('Y');
        }

        return [$yMin, $yMax];
    }

    private function getFacets(EntityManagerInterface $em): array
    {
        $conn = $em->getConnection();

        $families = $conn->fetchFirstColumn("
            SELECT DISTINCT FamilyName
            FROM article
            WHERE FamilyName IS NOT NULL AND FamilyName <> ''
            ORDER BY FamilyName ASC
            LIMIT 400
        ");

        $brandRaw = $conn->fetchFirstColumn("
            SELECT DISTINCT xx_Marque
            FROM article
            WHERE xx_Marque IS NOT NULL AND xx_Marque <> ''
            ORDER BY xx_Marque ASC
            LIMIT 600
        ");

        $vehRaw = $conn->fetchFirstColumn("
            SELECT DISTINCT xx_Vehicule
            FROM article
            WHERE xx_Vehicule IS NOT NULL AND xx_Vehicule <> ''
            ORDER BY xx_Vehicule ASC
            LIMIT 600
        ");

        $brandSet = [];
        foreach ($brandRaw as $line) {
            $parts = preg_split('/[,;|]+/', (string) $line);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $brandSet[$p] = true;
                }
            }
        }

        $vehSet = [];
        foreach ($vehRaw as $line) {
            $parts = preg_split('/[,;|]+/', (string) $line);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $vehSet[$p] = true;
                }
            }
        }

        $brands = array_keys($brandSet);
        sort($brands, SORT_NATURAL | SORT_FLAG_CASE);

        $vehicles = array_keys($vehSet);
        sort($vehicles, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'families' => array_values(array_filter(array_map('strval', $families))),
            'brands' => array_values($brands),
            'vehicles' => array_values($vehicles),
        ];
    }

    private function normalizeSuggestion(Article $article, int $score): array
    {
        $code = (string) $article->getId();
        $title = trim((string) $article->getDesComClear()) ?: $code;
        $meta = array_values(array_filter([
            $article->getFamilyName(),
            $article->getXxMarque(),
            $article->getXxVehicule(),
            $article->getXxAnnee(),
        ], static fn (?string $value): bool => trim((string) $value) !== ''));

        return [
            'type' => 'article',
            'code' => $code,
            'title' => $title,
            'meta' => implode(' · ', $meta),
            'stock' => $article->getRealStock(),
            'score' => $score,
            'url' => $this->generateUrl('article_show', ['code' => $code]),
        ];
    }

    private function suggestionFragments(string $query, array $tokens): array
    {
        $fragments = [trim($query)];

        foreach ($tokens as $token) {
            $fragments[] = $token;
            $length = strlen($token);

            if ($length >= 4) {
                $fragments[] = substr($token, 0, 3);
            }

            if ($length >= 6) {
                $fragments[] = substr($token, 0, 4);
            }
        }

        $fragments = array_map(static fn (string $value): string => trim($value), $fragments);
        $fragments = array_filter($fragments, static fn (string $value): bool => mb_strlen($value, 'UTF-8') >= 2);

        return array_values(array_unique($fragments));
    }

    private function suggestionTokens(string $normalizedQuery): array
    {
        $tokens = preg_split('/\s+/', $normalizedQuery) ?: [];
        $tokens = array_filter($tokens, static fn (string $token): bool => strlen($token) >= 2);

        return array_values(array_unique($tokens));
    }

    private function scoreSuggestionArticle(Article $article, string $normalizedQuery, array $tokens): int
    {
        $code = $this->normalizeSuggestionText((string) $article->getId());
        $title = $this->normalizeSuggestionText((string) $article->getDesComClear());
        $haystack = $this->normalizeSuggestionText(implode(' ', [
            $article->getId(),
            $article->getDesComClear(),
            $article->getFamilyName(),
            $article->getXxMarque(),
            $article->getXxVehicule(),
            $article->getXxPosition(),
            $article->getXxAnnee(),
        ]));

        if ($normalizedQuery === '') {
            return 0;
        }

        $score = 0;

        if ($code === $normalizedQuery) {
            $score += 1000;
        } elseif (str_starts_with($code, $normalizedQuery)) {
            $score += 760;
        } elseif (str_contains($code, $normalizedQuery)) {
            $score += 620;
        }

        if ($title === $normalizedQuery) {
            $score += 720;
        } elseif (str_contains($title, $normalizedQuery)) {
            $score += 420;
        } elseif (str_contains($haystack, $normalizedQuery)) {
            $score += 320;
        }

        $words = preg_split('/\s+/', $haystack) ?: [];
        foreach ($tokens as $token) {
            if (str_contains($haystack, $token)) {
                $score += 85;
                continue;
            }

            $score += $this->bestFuzzyTokenScore($token, $words);
        }

        if ($article->getRealStock() > 0) {
            $score += 10;
        }

        return $score;
    }

    private function bestFuzzyTokenScore(string $token, array $words): int
    {
        $best = 0;
        $tokenLength = strlen($token);

        foreach ($words as $word) {
            $word = trim((string) $word);
            $wordLength = strlen($word);

            if ($wordLength < 2) {
                continue;
            }

            if (str_starts_with($word, $token) || str_starts_with($token, $word)) {
                $best = max($best, 58);
                continue;
            }

            $distance = levenshtein($token, $word);
            $maxLength = max($tokenLength, $wordLength);

            if ($maxLength >= 4 && $distance <= 1) {
                $best = max($best, 52);
                continue;
            }

            if ($maxLength >= 5 && $distance <= 2) {
                $best = max($best, 34);
            }

            similar_text($token, $word, $percent);
            if ($percent >= 78) {
                $best = max($best, 30);
            } elseif ($percent >= 68) {
                $best = max($best, 20);
            }
        }

        return $best;
    }

    private function normalizeSuggestionText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($transliterated) && $transliterated !== '') {
            $value = $transliterated;
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}

<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\User;
use App\Repository\CartItemRepository;
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
        private readonly PricingService $pricingService
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

        $needFacets = (string) $request->query->get('facets', '') === '1';

        $qb = $em->getRepository(Article::class)->createQueryBuilder('a');

        if ($q !== '') {
            $qb->andWhere(
                'a.id LIKE :q
                 OR a.desComClear LIKE :q
                 OR a.uniqueId LIKE :q
                 OR a.xxVehicule LIKE :q
                 OR a.xxMarque LIKE :q
                 OR a.familyName LIKE :q'
            )->setParameter('q', '%' . $q . '%');
        }

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
        if ($onlyNew) {
            $cutoff = (new \DateTimeImmutable('now'))->modify('-' . $newDays . ' days');
            $qb->andWhere('a.sysCreatedDate IS NOT NULL')
                ->andWhere('a.sysCreatedDate >= :cutoff')
                ->setParameter('cutoff', $cutoff);
        }

        $total = $this->countTotal($qb);

        $qb->orderBy('a.sysModifiedDate', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        /** @var Article[] $items */
        $items = $qb->getQuery()->getResult();

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
                $onlyNew ? $cutoff : null,
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
            'imagesBaseUrl' => $this->resolveImagesBaseUrl(),
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

    private function resolveImagesBaseUrl(): string
    {
        try {
            $value = (string) $this->getParameter('e_url');
            if ($value !== '') {
                return $value;
            }
        } catch (\Throwable) {
        }

        return (string) ($_ENV['IMAGES_BASE_URL'] ?? getenv('IMAGES_BASE_URL') ?? '/ebpsynch');
    }
}
<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\CategoryReduction;
use App\Repository\CategoryReductionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/category-reductions', name: 'admin_category_reduction_')]
#[IsGranted('ROLE_ADMIN')]
class CategoryReductionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $rows = $em->getConnection()->fetchAllAssociative(<<<SQL
            SELECT
                FamilyName AS family,
                COUNT(*) AS itemsCount,
                MIN(NULLIF(TRIM(reduction), '')) AS minReduction,
                MAX(NULLIF(TRIM(reduction), '')) AS maxReduction,
                SUM(CASE WHEN reduction IS NULL OR TRIM(reduction) = '' THEN 1 ELSE 0 END) AS emptyCount
            FROM article
            WHERE FamilyName IS NOT NULL
              AND TRIM(FamilyName) <> ''
            GROUP BY FamilyName
            ORDER BY FamilyName ASC
        SQL);

        $rules = $this->categoryReductionRepository($em)->findDiscountRateMap();

        $categories = array_map(function (array $row) use ($rules): array {
            $family = (string) ($row['family'] ?? '');
            $configuredReduction = $rules[$this->normalizeFamilyKey($family)] ?? null;

            if ($configuredReduction !== null) {
                return [
                    'family' => $family,
                    'itemsCount' => (int) ($row['itemsCount'] ?? 0),
                    'reduction' => $this->normalizeReduction($configuredReduction),
                    'isMixed' => false,
                ];
            }

            $itemsCount = (int) ($row['itemsCount'] ?? 0);
            $emptyCount = (int) ($row['emptyCount'] ?? 0);
            $minReduction = $row['minReduction'] ?? null;
            $maxReduction = $row['maxReduction'] ?? null;

            $currentReduction = null;
            $isMixed = false;

            if ($itemsCount === 0 || $emptyCount === $itemsCount) {
                $currentReduction = null;
            } elseif ($emptyCount === 0 && (string) $minReduction === (string) $maxReduction) {
                $currentReduction = $minReduction;
            } else {
                $currentReduction = $minReduction ?: $maxReduction;
                $isMixed = true;
            }

            return [
                'family' => $family,
                'itemsCount' => $itemsCount,
                'reduction' => $currentReduction,
                'isMixed' => $isMixed,
            ];
        }, $rows);

        return $this->render('admin/category_reduction/index.html.twig', [
            'categories' => $categories,
            'saveToken' => $this->csrfTokenManager()->getToken('admin_category_reduction_save')->getValue(),
            'bulkToken' => $this->csrfTokenManager()->getToken('admin_category_reduction_bulk')->getValue(),
        ]);
    }

    #[Route('/save', name: 'save', methods: ['POST'])]
    public function save(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = $this->requestPayload($request);

        if (!$this->isCsrfTokenValid('admin_category_reduction_save', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Token CSRF invalide.'], 403);
        }

        $family = trim((string) ($payload['family'] ?? ''));
        if ($family === '') {
            return $this->json(['ok' => false, 'message' => 'Catégorie introuvable.'], 422);
        }

        $normalizedReduction = $this->normalizeReduction((string) ($payload['reduction'] ?? ''));
        $affected = $this->countArticlesInFamily($em, $family);
        $repository = $this->categoryReductionRepository($em);
        $rule = $repository->findOneByFamilyName($family);

        if ($normalizedReduction === null) {
            if ($rule instanceof CategoryReduction) {
                $em->remove($rule);
            }
        } else {
            $rule ??= (new CategoryReduction())->setFamilyName($family);
            $rule->setDiscountRate($normalizedReduction);
            $em->persist($rule);
        }

        // Old per-article values are only a compatibility fallback. The category rule is authoritative.
        $this->clearLegacyReductionForFamily($em, $family);
        $em->flush();

        return $this->json([
            'ok' => true,
            'family' => $family,
            'reduction' => $normalizedReduction,
            'displayReduction' => $this->formatDisplayReduction($normalizedReduction),
            'affected' => $affected,
            'message' => $normalizedReduction !== null
                ? sprintf('Réduction %s%% enregistrée pour %d article(s) de la catégorie.', $normalizedReduction, $affected)
                : sprintf('Réduction supprimée pour %d article(s) de la catégorie.', $affected),
        ]);
    }

    #[Route('/bulk', name: 'bulk', methods: ['POST'])]
    public function bulk(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = $this->requestPayload($request);

        if (!$this->isCsrfTokenValid('admin_category_reduction_bulk', (string) ($payload['_token'] ?? ''))) {
            return $this->json(['ok' => false, 'message' => 'Token CSRF invalide.'], 403);
        }

        $mode = trim((string) ($payload['mode'] ?? 'apply'));
        $normalizedReduction = $mode === 'reset'
            ? null
            : $this->normalizeReduction((string) ($payload['reduction'] ?? ''));

        if ($mode !== 'reset' && $normalizedReduction === null) {
            return $this->json(['ok' => false, 'message' => 'Réduction invalide.'], 422);
        }

        $affected = (int) $em->getRepository(Article::class)->count([]);

        if ($mode === 'reset') {
            $em->createQueryBuilder()
                ->delete(CategoryReduction::class, 'reduction')
                ->getQuery()
                ->execute();
        } else {
            $repository = $this->categoryReductionRepository($em);
            $rulesByFamily = [];
            foreach ($this->getArticleFamilies($em) as $family) {
                $familyKey = $this->normalizeFamilyKey($family);
                $rule = $rulesByFamily[$familyKey] ?? $repository->findOneByFamilyName($family);
                $rule ??= (new CategoryReduction())->setFamilyName($family);
                $rule->setDiscountRate($normalizedReduction);
                $em->persist($rule);
                $rulesByFamily[$familyKey] = $rule;
            }
        }

        $em->createQueryBuilder()
            ->update(Article::class, 'article')
            ->set('article.reduction', 'NULL')
            ->getQuery()
            ->execute();
        $em->flush();

        return $this->json([
            'ok' => true,
            'reduction' => $normalizedReduction,
            'displayReduction' => $this->formatDisplayReduction($normalizedReduction),
            'affected' => $affected,
            'message' => $mode === 'reset'
                ? sprintf('Toutes les réductions ont été supprimées sur %d article(s).', $affected)
                : sprintf('Réduction globale de %s%% enregistrée pour %d article(s).', $normalizedReduction, $affected),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(Request $request): array
    {
        $payload = json_decode((string) $request->getContent(), true);

        return is_array($payload) ? $payload : $request->request->all();
    }

    /**
     * @return list<string>
     */
    private function getArticleFamilies(EntityManagerInterface $em): array
    {
        $families = $em->getConnection()->fetchFirstColumn(<<<SQL
            SELECT DISTINCT FamilyName
            FROM article
            WHERE FamilyName IS NOT NULL
              AND TRIM(FamilyName) <> ''
            ORDER BY FamilyName ASC
        SQL);

        return array_values(array_filter(array_map(static fn ($family): string => trim((string) $family), $families)));
    }

    private function countArticlesInFamily(EntityManagerInterface $em, string $family): int
    {
        return (int) $em->createQueryBuilder()
            ->select('COUNT(article.uniqueId)')
            ->from(Article::class, 'article')
            ->where('UPPER(TRIM(article.familyName)) = :family')
            ->setParameter('family', $this->normalizeFamilyKey($family))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function clearLegacyReductionForFamily(EntityManagerInterface $em, string $family): void
    {
        $em->createQueryBuilder()
            ->update(Article::class, 'article')
            ->set('article.reduction', 'NULL')
            ->where('UPPER(TRIM(article.familyName)) = :family')
            ->setParameter('family', $this->normalizeFamilyKey($family))
            ->getQuery()
            ->execute();
    }

    private function categoryReductionRepository(EntityManagerInterface $em): CategoryReductionRepository
    {
        $repository = $em->getRepository(CategoryReduction::class);
        if (!$repository instanceof CategoryReductionRepository) {
            throw new \LogicException('Le dépôt des réductions par catégorie est indisponible.');
        }

        return $repository;
    }

    private function normalizeReduction(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);
        if (!is_numeric($value)) {
            return null;
        }

        $number = (float) $value;
        if ($number < 0 || $number > 100) {
            return null;
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    private function formatDisplayReduction(?string $reduction): ?string
    {
        return $reduction === null || $reduction === '' ? null : $reduction . '%';
    }

    private function normalizeFamilyKey(string $familyName): string
    {
        $familyName = preg_replace('/\s+/u', ' ', trim($familyName)) ?? '';

        return mb_strtoupper($familyName, 'UTF-8');
    }

    private function csrfTokenManager()
    {
        return $this->container->get('security.csrf.token_manager');
    }
}

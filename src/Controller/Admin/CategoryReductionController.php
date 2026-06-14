<?php

namespace App\Controller\Admin;

use App\Entity\Article;
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

        $categories = array_map(static function (array $row): array {
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
                'family' => (string) ($row['family'] ?? ''),
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
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        if (!$this->isCsrfTokenValid('admin_category_reduction_save', (string) ($payload['_token'] ?? ''))) {
            return $this->json([
                'ok' => false,
                'message' => 'Token CSRF invalide.',
            ], 403);
        }

        $family = trim((string) ($payload['family'] ?? ''));
        $reductionRaw = trim((string) ($payload['reduction'] ?? ''));

        if ($family === '') {
            return $this->json([
                'ok' => false,
                'message' => 'Catégorie introuvable.',
            ], 422);
        }

        $normalizedReduction = $this->normalizeReduction($reductionRaw, true);

        $affected = $em->createQueryBuilder()
            ->update(Article::class, 'a')
            ->set('a.reduction', ':reduction')
            ->where('a.familyName = :family')
            ->setParameter('reduction', $normalizedReduction)
            ->setParameter('family', $family)
            ->getQuery()
            ->execute();

        return $this->json([
            'ok' => true,
            'family' => $family,
            'reduction' => $normalizedReduction,
            'displayReduction' => $this->formatDisplayReduction($normalizedReduction),
            'affected' => (int) $affected,
            'message' => $normalizedReduction !== null && $normalizedReduction !== ''
                ? sprintf('Réduction %s%% appliquée à %d article(s).', $normalizedReduction, (int) $affected)
                : sprintf('Réduction supprimée pour %d article(s).', (int) $affected),
        ]);
    }

    #[Route('/bulk', name: 'bulk', methods: ['POST'])]
    public function bulk(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true);
        if (!is_array($payload)) {
            $payload = $request->request->all();
        }

        if (!$this->isCsrfTokenValid('admin_category_reduction_bulk', (string) ($payload['_token'] ?? ''))) {
            return $this->json([
                'ok' => false,
                'message' => 'Token CSRF invalide.',
            ], 403);
        }

        $mode = trim((string) ($payload['mode'] ?? 'apply'));
        $reductionRaw = trim((string) ($payload['reduction'] ?? ''));

        if ($mode === 'reset') {
            $normalizedReduction = '';
        } else {
            $normalizedReduction = $this->normalizeReduction($reductionRaw, false);

            if ($normalizedReduction === null || $normalizedReduction === '') {
                return $this->json([
                    'ok' => false,
                    'message' => 'Réduction invalide.',
                ], 422);
            }
        }

        $affected = $em->createQueryBuilder()
            ->update(Article::class, 'a')
            ->set('a.reduction', ':reduction')
            ->setParameter('reduction', $normalizedReduction)
            ->getQuery()
            ->execute();

        return $this->json([
            'ok' => true,
            'reduction' => $normalizedReduction,
            'displayReduction' => $this->formatDisplayReduction($normalizedReduction),
            'affected' => (int) $affected,
            'message' => $mode === 'reset'
                ? sprintf('Toutes les réductions ont été remises à 0%% sur %d article(s).', (int) $affected)
                : sprintf('Réduction globale de %s%% appliquée à %d article(s).', $normalizedReduction, (int) $affected),
        ]);
    }

    private function normalizeReduction(string $value, bool $allowEmpty): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return $allowEmpty ? null : null;
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
        if ($reduction === null || $reduction === '') {
            return null;
        }

        return $reduction . '%';
    }

    private function csrfTokenManager()
    {
        return $this->container->get('security.csrf.token_manager');
    }
}
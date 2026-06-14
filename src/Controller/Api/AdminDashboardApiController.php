<?php

namespace App\Controller\Api;

use App\Service\AdminDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/dashboard', name: 'api_admin_dashboard_')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardApiController extends AbstractController
{
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(AdminDashboardService $dashboardService): JsonResponse
    {
        return $this->json([
            'ok' => true,
            ...$dashboardService->getDashboardPayload(),
        ]);
    }
}
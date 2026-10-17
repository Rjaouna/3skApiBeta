<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/anomalies-catalogue', name: 'admin_catalog_anomalies_')]
#[IsGranted('ROLE_ADMIN')]
final class CatalogAnomalyController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        return $this->render('admin/catalog_anomalies/index.html.twig', [
            'actionToken' => $csrfTokenManager->getToken('admin-catalog-anomalies-action')->getValue(),
        ]);
    }
}

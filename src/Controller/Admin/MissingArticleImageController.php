<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/articles/sans-image', name: 'admin_missing_images_')]
#[IsGranted('ROLE_ADMIN')]
final class MissingArticleImageController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        return $this->render('admin/missing_images/index.html.twig', [
            'checkToken' => $csrfTokenManager->getToken('admin-missing-image-check')->getValue(),
        ]);
    }
}

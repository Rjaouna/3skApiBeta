<?php

namespace App\Controller;

use App\Entity\ImportSource;
use App\Service\ArticleImageLocator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ArticleImageController extends AbstractController
{
    #[Route('/ebpsynch/{uniqueId}.png', name: 'article_image', requirements: ['uniqueId' => ArticleImageLocator::UUID_PATTERN], methods: ['GET', 'HEAD'])]
    #[Route('/export_images/{uniqueId}.png', name: 'article_image_export', requirements: ['uniqueId' => ArticleImageLocator::UUID_PATTERN], methods: ['GET', 'HEAD'])]
    #[IsGranted('ROLE_USER')]
    public function show(
        string $uniqueId,
        Request $request,
        EntityManagerInterface $entityManager,
        ArticleImageLocator $images,
    ): BinaryFileResponse {
        $source = $entityManager->getRepository(ImportSource::class)->findOneBy([]);
        $path = $images->find($uniqueId, $source?->getDirectoryPath());
        if ($path === null) {
            throw $this->createNotFoundException('Photo article introuvable.');
        }

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setPrivate();
        $response->setMaxAge(3600);
        $response->setAutoEtag();
        $response->setAutoLastModified();
        $response->isNotModified($request);

        return $response;
    }
}

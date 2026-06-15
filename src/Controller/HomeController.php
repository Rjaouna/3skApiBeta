<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\HeroSlideRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(
        HeroSlideRepository $heroSlideRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $slides = $heroSlideRepository->findForHomepage();

        $catalogStats = $entityManager->getConnection()->fetchAssociative(
            <<<'SQL'
                SELECT
                    COUNT(*) AS total_references,
                    SUM(CASE WHEN RealStock > 0 THEN 1 ELSE 0 END) AS stocked_references,
                    COUNT(DISTINCT NULLIF(xx_Vehicule, '')) AS vehicle_brands,
                    COUNT(DISTINCT NULLIF(xx_Marque, '')) AS product_brands
                FROM article
            SQL
        );

        return $this->render('home/index.html.twig', [
            'slides' => $slides,
            'homeStats' => [
                'totalReferences' => (int) ($catalogStats['total_references'] ?? 0),
                'stockedReferences' => (int) ($catalogStats['stocked_references'] ?? 0),
                'vehicleBrands' => (int) ($catalogStats['vehicle_brands'] ?? 0),
                'productBrands' => (int) ($catalogStats['product_brands'] ?? 0),
            ],
        ]);
    }
}

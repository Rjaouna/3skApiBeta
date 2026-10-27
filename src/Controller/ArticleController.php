<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\User;
use App\Service\PricingService;
use App\Service\CatalogImageBaseUrlProvider;
use App\ViewModel\ArticlePricingView;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    public function __construct(
        private readonly PricingService $pricingService,
        private readonly CatalogImageBaseUrlProvider $images,
    ) {
    }

    #[Route('/articles', name: 'articles_page', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->render('articles/index.html.twig', [
            'initial' => [
                'q' => (string) $request->query->get('q', ''),
                'family' => (string) $request->query->get('family', ''),
                'brand' => (string) ($request->query->get('brand', '') ?: $request->query->get('marque', '')),
                'vehicle' => (string) ($request->query->get('vehicle', '') ?: $request->query->get('vehicule', '')),
                'anneeFrom' => (string) $request->query->get('anneeFrom', ''),
                'anneeTo' => (string) $request->query->get('anneeTo', ''),
                'annee' => (string) $request->query->get('annee', ''),
                'inStock' => (string) $request->query->get('inStock', ''),
                'new' => (string) $request->query->get('new', ''),
                'newDays' => (int) $request->query->get('newDays', 5),
                'sort' => $request->query->get('sort') === 'created_desc' ? 'created_desc' : '',
            ],
        ]);
    }

    #[Route('/articles/{code}', name: 'article_show', methods: ['GET'])]
    public function show(string $code, EntityManagerInterface $em): Response
    {
        $article = $em->getRepository(Article::class)->findOneBy(['id' => $code]);

        if (!$article) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        $currentUser = $this->getUser();
        $currentUser = $currentUser instanceof User ? $currentUser : null;

        $relatedArticlesRaw = [];

        if ($article->getFamilyName()) {
            $relatedArticlesRaw = $em->getRepository(Article::class)
                ->createQueryBuilder('a')
                ->andWhere('a.familyName = :family')
                ->andWhere('a.id != :code')
                ->setParameter('family', $article->getFamilyName())
                ->setParameter('code', $article->getId())
                ->orderBy('a.sysModifiedDate', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
        }

        $articleView = new ArticlePricingView(
            $article,
            $this->pricingService->computeArticlePricing($article, $currentUser)['globalReductionFormatted']
        );

        $relatedArticles = [];
        $relatedImageUrls = [];
        foreach ($relatedArticlesRaw as $related) {
            if (!$related instanceof Article) {
                continue;
            }

            $uniqueId = $related->getUniqueId();
            if ($uniqueId !== null && $uniqueId !== '') {
                $relatedImageUrls[$uniqueId] = $this->images->imageUrl($uniqueId, $related->getSysModifiedDate());
            }

            $relatedArticles[] = new ArticlePricingView(
                $related,
                $this->pricingService->computeArticlePricing($related, $currentUser)['globalReductionFormatted']
            );
        }

        return $this->render('articles/show.html.twig', [
            'article' => $articleView,
            'relatedArticles' => $relatedArticles,
            'imageUrl' => $this->images->imageUrl((string) $article->getUniqueId(), $article->getSysModifiedDate()),
            'relatedImageUrls' => $relatedImageUrls,
        ]);
    }
}

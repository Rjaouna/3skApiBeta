<?php

namespace App\Tests\Controller;

use App\Controller\Api\ArticleApiController;
use App\Entity\Article;
use App\Repository\CartItemRepository;
use App\Repository\UserFamilyDiscountRepository;
use App\Service\PricingService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

final class ArticleLatestListTest extends TestCase
{
    private EntityManager $orm;
    private EntityManagerInterface $entityManager;
    private CartItemRepository $cartItems;
    private ArticleApiController $controller;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('This isolated database test requires pdo_sqlite.');
        }
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $configuration = ORMSetup::createAttributeMetadataConfiguration([dirname(__DIR__, 2) . '/src/Entity'], true);
        $this->orm = new EntityManager($connection, $configuration);
        $metadata = $this->orm->getClassMetadata(Article::class);
        $metadata->customRepositoryClassName = EntityRepository::class;
        (new SchemaTool($this->orm))->createSchema([$metadata]);

        // The controller's MySQL year-bound query is unrelated to article ordering.
        $yearBounds = $this->createStub(Connection::class);
        $yearBounds->method('fetchAssociative')->willReturn(['yMin' => 1976, 'yMax' => 2028]);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')->willReturn($this->orm->getRepository(Article::class));
        $this->entityManager->method('getConnection')->willReturn($yearBounds);
        $this->cartItems = $this->createStub(CartItemRepository::class);
        $this->cartItems->method('getPressureMapForArticleCodes')->willReturn([]);

        $this->controller = new ArticleApiController(new PricingService($this->createStub(UserFamilyDiscountRepository::class)));
        $container = new Container();
        $container->set('security.token_storage', new TokenStorage());
        $this->controller->setContainer($container);
    }

    protected function tearDown(): void
    {
        if (isset($this->orm)) {
            $this->orm->close();
            $this->orm->getConnection()->close();
        }
    }

    public function testLatestArticlesRemainVisibleWhenAllDatesAreOlderThanNinetyDays(): void
    {
        for ($index = 1; $index <= 6; $index++) {
            $this->article($index, '-' . (100 + $index) . ' days', '-' . (7 - $index) . ' days');
        }

        $recent = $this->listing(['new' => 1, 'newDays' => 90, 'limit' => 5]);
        self::assertSame(0, $recent['total']);
        $latest = $this->listing(['sort' => 'created_desc', 'newDays' => 90, 'limit' => 5]);
        self::assertSame(6, $latest['total']);
        self::assertSame(['REF-1', 'REF-2', 'REF-3', 'REF-4', 'REF-5'], array_column($latest['items'], 'Id'));
        self::assertSame([false], array_values(array_unique(array_column($latest['items'], 'isNew'))));
    }

    public function testCreationDateTiesHaveStablePagination(): void
    {
        foreach ([3, 1, 2] as $index) {
            $this->article($index, '-200 days', '-' . $index . ' days');
        }
        $first = $this->listing(['sort' => 'created_desc', 'limit' => 2, 'page' => 1]);
        $second = $this->listing(['sort' => 'created_desc', 'limit' => 2, 'page' => 2]);

        self::assertSame(['REF-1', 'REF-2'], array_column($first['items'], 'Id'));
        self::assertSame(['REF-3'], array_column($second['items'], 'Id'));
        self::assertSame(2, $first['pages']);
    }

    public function testUndatedArticlesAreNotInventedAsTheLatest(): void
    {
        $this->article(1, null, '-1 day');
        self::assertSame([], $this->listing(['sort' => 'created_desc'])['items']);
        self::assertSame(1, $this->listing([])['total']);
    }

    public function testDefaultCatalogKeepsItsModificationDateOrder(): void
    {
        $this->article(1, '-200 days', '-1 day');
        $this->article(2, '-100 days', '-30 days');

        self::assertSame(['REF-1', 'REF-2'], array_column($this->listing([])['items'], 'Id'));
        self::assertSame(['REF-2', 'REF-1'], array_column($this->listing(['sort' => 'created_desc'])['items'], 'Id'));
        self::assertSame(['REF-1', 'REF-2'], array_column($this->listing(['sort' => 'unsupported'])['items'], 'Id'));
    }

    public function testSearchPreservesTheRequestedOrderAndExistingStockFilter(): void
    {
        $this->article(1, '-200 days', '-1 day', 50);
        $this->article(2, '-100 days', '-30 days', 0);

        self::assertSame(['REF-1', 'REF-2'], array_column($this->listing(['q' => 'piece'])['items'], 'Id'));
        self::assertSame(['REF-2', 'REF-1'], array_column($this->listing(['q' => 'piece', 'sort' => 'created_desc'])['items'], 'Id'));
        self::assertSame(['REF-1'], array_column($this->listing(['q' => 'piece', 'sort' => 'created_desc', 'inStock' => 1])['items'], 'Id'));
    }

    public function testNewBadgeUsesTheActualDateNotTheLatestListMembership(): void
    {
        $this->article(1, '-200 days', '-1 day');
        $this->article(2, '-1 day', '-2 days');
        $latest = $this->listing(['sort' => 'created_desc', 'newDays' => 90]);

        self::assertSame([true, false], array_column($latest['items'], 'isNew'));
        self::assertSame(['REF-2'], array_column($this->listing(['new' => 1, 'newDays' => 90])['items'], 'Id'));
    }

    private function article(int $index, ?string $created, string $modified, int $stock = 0): void
    {
        $article = (new Article())
            ->setUniqueId(sprintf('%08d-0000-0000-0000-000000000000', $index))
            ->setId('REF-' . $index)
            ->setDesComClear('Piece REF-' . $index)
            ->setRealStock($stock)
            ->setSysCreatedDate($created === null ? null : new \DateTimeImmutable($created))
            ->setSysModifiedDate(new \DateTimeImmutable($modified));
        $this->orm->persist($article);
        $this->orm->flush();
    }

    private function listing(array $parameters): array
    {
        $response = $this->controller->list(Request::create('/api/articles', 'GET', $parameters), $this->entityManager, $this->cartItems);
        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}

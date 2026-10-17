<?php

namespace App\Tests\Service;

use App\Repository\ArticleRepository;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\CategoryReductionRepository;
use App\Repository\CustomerOrderLineRepository;
use App\Repository\CustomerOrderRepository;
use App\Service\AdminDashboardService;
use PHPUnit\Framework\TestCase;

final class AdminDashboardCategoryReductionTest extends TestCase
{
    public function testStockValuationUsesTheCategoryReductionInsteadOfTheLegacyArticleField(): void
    {
        $categories = $this->createStub(CategoryReductionRepository::class);
        $categories->method('findDiscountRateMap')->willReturn(['FREINAGE' => '35.00']);

        $dashboard = new AdminDashboardService(
            $this->createStub(CartRepository::class),
            $this->createStub(CartItemRepository::class),
            $this->createStub(CustomerOrderRepository::class),
            $this->createStub(CustomerOrderLineRepository::class),
            $this->createStub(ArticleRepository::class),
            $categories,
        );

        $stats = $this->invokePrivate($dashboard, 'buildStockStats', [[
            [
                'familyName' => 'Freinage',
                'realStock' => 2,
                'salePriceVatIncluded' => '100.00',
                'salePriceVatExcluded' => '80.00',
                'reduction' => null,
            ],
        ]]);

        self::assertSame(200.0, $stats['summary']['valueTtcWithoutReduction']);
        self::assertSame(130.0, $stats['summary']['valueTtcWithReduction']);
    }

    private function invokePrivate(object $object, string $method, array $arguments): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);

        return $reflection->invokeArgs($object, $arguments);
    }
}

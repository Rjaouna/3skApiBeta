<?php

namespace App\Tests\Service;

use App\Entity\Article;
use App\Repository\CategoryReductionRepository;
use App\Repository\UserFamilyDiscountRepository;
use App\Service\PricingService;
use PHPUnit\Framework\TestCase;

final class PricingServiceCategoryReductionTest extends TestCase
{
    public function testCategoryRuleAppliesToImportedArticlesWithoutAReductionField(): void
    {
        $pricing = $this->pricing(['FREINAGE' => '35.00']);
        $article = (new Article())
            ->setFamilyName('Freinage')
            ->setSalePriceVatIncluded('100.00')
            ->setSalePriceVatExcluded('80.00');

        $result = $pricing->computeArticlePricing($article);

        self::assertSame(35.0, $result['baseReduction']);
        self::assertSame('65.00', $result['unitPriceFinalTtcFormatted']);
    }

    public function testCategoryRuleTakesPriorityOverLegacyArticleReduction(): void
    {
        $pricing = $this->pricing(['FREINAGE' => '35.00']);
        $article = (new Article())
            ->setFamilyName('Freinage')
            ->setReduction('10')
            ->setSalePriceVatIncluded('100.00')
            ->setSalePriceVatExcluded('80.00');

        $result = $pricing->computeArticlePricing($article);

        self::assertSame(35.0, $result['baseReduction']);
        self::assertSame('35', $result['globalReductionFormatted']);
    }

    public function testLegacyArticleReductionRemainsACompatibilityFallback(): void
    {
        $pricing = $this->pricing([]);
        $article = (new Article())
            ->setFamilyName('Freinage')
            ->setReduction('15')
            ->setSalePriceVatIncluded('100.00')
            ->setSalePriceVatExcluded('80.00');

        self::assertSame(15.0, $pricing->computeArticlePricing($article)['baseReduction']);
    }

    /**
     * @param array<string, string> $categoryRates
     */
    private function pricing(array $categoryRates): PricingService
    {
        $categories = $this->createStub(CategoryReductionRepository::class);
        $categories->method('findDiscountRateMap')->willReturn($categoryRates);

        return new PricingService(
            $this->createStub(UserFamilyDiscountRepository::class),
            $categories,
        );
    }
}

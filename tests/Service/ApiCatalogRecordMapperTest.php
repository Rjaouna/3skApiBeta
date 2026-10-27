<?php

namespace App\Tests\Service;

use App\Entity\Article;
use App\Exception\ApiReceptionException;
use App\Service\ApiCatalogFamilyKey;
use App\Service\ApiCatalogRecordMapper;
use PHPUnit\Framework\TestCase;

final class ApiCatalogRecordMapperTest extends TestCase
{
    public function testItProjectsTheApiRecordWithoutOverwritingALocalReduction(): void
    {
        $mapper = new ApiCatalogRecordMapper();
        $row = $mapper->normalize([
            'data' => [
                'Id' => ' REF-1 ',
                'UniqueId' => '11111111-1111-1111-1111-111111111111',
                'Caption' => 'Pare-chocs avant',
                'RealStock' => '12',
                'SalePriceVatIncluded' => '259,90',
                'SalePriceVatExcluded' => '216.58',
                'FamilyId' => 'CARROSSERIE',
                'sysModifiedDate' => '2026-10-18T10:00:00+00:00',
            ],
        ], ['CARROSSERIE' => 'Carrosserie']);

        $article = (new Article())
            ->setUniqueId($row['UniqueId'])
            ->setReduction('18');
        $mapper->apply($article, $row);

        self::assertSame('REF-1', $article->getId());
        self::assertSame('Pare-chocs avant', $article->getDesComClear());
        self::assertSame(12, $article->getRealStock());
        self::assertSame('259.90', $article->getSalePriceVatIncluded());
        self::assertSame('18', $article->getReduction());
        self::assertSame('Carrosserie', $article->getFamilyName());
        self::assertTrue($mapper->matches($article, $row));
    }

    public function testItExplainsWhichRequiredProfileFieldIsMissing(): void
    {
        $this->expectException(ApiReceptionException::class);
        $this->expectExceptionMessage('SalePriceVatExcluded');

        (new ApiCatalogRecordMapper())->normalize([
            'data' => [
                'Id' => 'REF-1',
                'UniqueId' => '11111111-1111-1111-1111-111111111111',
                'RealStock' => 1,
                'SalePriceVatIncluded' => 10,
            ],
        ], []);
    }

    public function testItPlacesAnArticleWithAMissingFamilyInTheOtherCategory(): void
    {
        $row = (new ApiCatalogRecordMapper())->normalize([
            'data' => [
                'Id' => 'REF-1',
                'UniqueId' => '11111111-1111-1111-1111-111111111111',
                'RealStock' => 1,
                'SalePriceVatIncluded' => 10,
                'SalePriceVatExcluded' => 8.33,
                'FamilyId' => 'FREINAGE',
            ],
        ], []);

        self::assertSame('FREINAGE', $row['FamilyId']);
        self::assertSame('Autres', $row['FamilyName']);
    }

    public function testItPlacesAnArticleWithoutAnyFamilyInTheOtherCategory(): void
    {
        $row = (new ApiCatalogRecordMapper())->normalize([
            'data' => [
                'Id' => 'REF-1',
                'UniqueId' => '11111111-1111-1111-1111-111111111111',
                'RealStock' => 1,
                'SalePriceVatIncluded' => 10,
                'SalePriceVatExcluded' => 8.33,
                'FamilyId' => null,
            ],
        ], []);

        self::assertNull($row['FamilyId']);
        self::assertSame('Autres', $row['FamilyName']);
    }

    public function testItMatchesANumericFamilyIdentifierWithLeadingZeros(): void
    {
        $row = (new ApiCatalogRecordMapper())->normalize([
            'data' => [
                'Id' => 'REF-1',
                'UniqueId' => '11111111-1111-1111-1111-111111111111',
                'RealStock' => 1,
                'SalePriceVatIncluded' => 10,
                'SalePriceVatExcluded' => 8.33,
                'FamilyId' => '011',
            ],
        ], [ApiCatalogFamilyKey::numericAlias('11') => 'Freinage']);

        self::assertSame('Freinage', $row['FamilyName']);
    }

    public function testItUsesTheSourceSpecificFamilyKeyForMergedEbpDatabases(): void
    {
        $sourceFamilyId = 'aaaaaaaaaaaaaaaa:FREINAGE';
        $row = (new ApiCatalogRecordMapper())->normalize([
            'data' => [
                'Id' => 'REF-1',
                'UniqueId' => '11111111-1111-1111-1111-111111111111',
                'RealStock' => 1,
                'SalePriceVatIncluded' => 10,
                'SalePriceVatExcluded' => 8.33,
                'FamilyId' => 'FREINAGE',
                '__apilocalFamilySourceId' => $sourceFamilyId,
            ],
        ], ['FREINAGE' => 'Freinage principal', $sourceFamilyId => 'Freinage seconde société']);

        self::assertSame($sourceFamilyId, $row['FamilyId']);
        self::assertSame('Freinage seconde société', $row['FamilyName']);
    }
}

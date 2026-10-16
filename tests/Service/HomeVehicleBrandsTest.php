<?php

namespace App\Tests\Service;

use App\Service\HomeVehicleBrands;
use PHPUnit\Framework\TestCase;

final class HomeVehicleBrandsTest extends TestCase
{
    public function testOnlyRealRecognizedCatalogBrandsAreShown(): void
    {
        $items = (new HomeVehicleBrands())->fromCatalog([
            ['vehicle' => 'PEUGEOT 308,CITROËN C3'], ['vehicle' => 'Renault Clio'],
            ['vehicle' => 'PEUGEOT'], ['vehicle' => 'NOUVEAUTE'], ['vehicle' => null],
        ]);
        self::assertSame(['Renault', 'Peugeot', 'Citroën'], array_column($items, 'label'));
        self::assertSame('Renault', $items[0]['filter']);
        self::assertSame('img/vehicle-brands/peugeot.webp', $items[1]['logo']);
    }

    public function testAliasesAndMultipleWordBrandsDoNotDuplicateLogos(): void
    {
        $items = (new HomeVehicleBrands())->fromCatalog([
            ['vehicle' => 'VW TOUAREG;MERCEDES-BENZ C200'],
            ['vehicle' => 'VOLKSWAGEN,DC LOGAN,LAND ROVER'],
        ]);
        self::assertSame(['Volkswagen', 'Mercedes-Benz', 'Dacia', 'Land Rover'], array_column($items, 'label'));
        self::assertSame(['VW', 'MERCEDES-BENZ', 'DC', 'LAND ROVER'], array_column($items, 'filter'));
    }

    public function testEmptyCatalogDoesNotInventAnyBrands(): void
    {
        self::assertSame([], (new HomeVehicleBrands())->fromCatalog([]));
    }
}

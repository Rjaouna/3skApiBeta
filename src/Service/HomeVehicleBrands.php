<?php

namespace App\Service;

final class HomeVehicleBrands
{
    private const LOGOS = [
        'RENAULT' => ['Renault', 'renault'],
        'PEUGEOT' => ['Peugeot', 'peugeot'],
        'CITROEN' => ['Citroën', 'citroen'],
        'VOLKSWAGEN' => ['Volkswagen', 'volkswagen'],
        'AUDI' => ['Audi', 'audi'],
        'BMW' => ['BMW', 'bmw'],
        'MERCEDES' => ['Mercedes-Benz', 'mercedes'],
        'TOYOTA' => ['Toyota', 'toyota'],
        'HYUNDAI' => ['Hyundai', 'hyundai'],
        'KIA' => ['Kia', 'kia'],
        'FORD' => ['Ford', 'ford'],
        'NISSAN' => ['Nissan', 'nissan'],
        'DACIA' => ['Dacia', 'dacia'],
        'FIAT' => ['Fiat', 'fiat'],
        'SEAT' => ['SEAT', 'seat'],
        'SKODA' => ['Škoda', 'skoda'],
        'OPEL' => ['Opel', 'opel'],
        'HONDA' => ['Honda', 'honda'],
        'SUZUKI' => ['Suzuki', 'suzuki'],
        'MITSUBISHI' => ['Mitsubishi', 'mitsubishi'],
        'CHEVROLET' => ['Chevrolet', 'chevrolet'],
        'ALFA ROMEO' => ['Alfa Romeo', 'alfa_romeo'],
        'LAND ROVER' => ['Land Rover', 'land_rover'],
        'RANGE ROVER' => ['Range Rover', 'range_rover'],
        'VOLVO' => ['Volvo', 'volvo'],
        'MINI' => ['MINI', 'mini'],
        'JEEP' => ['Jeep', 'jeep'],
        'ISUZU' => ['Isuzu', 'isuzu'],
        'IVECO' => ['Iveco', 'iveco'],
        'JAGUAR' => ['Jaguar', 'jaguar'],
        'PORSCHE' => ['Porsche', 'porsche'],
        'DAIHATSU' => ['Daihatsu', 'daihatsu'],
        'LANCIA' => ['Lancia', 'lancia'],
        'SSANGYONG' => ['SsangYong', 'ssangyong'],
        'BYD' => ['BYD', 'byd'],
        'CUPRA' => ['Cupra', 'cupra'],
    ];

    /** @param array<array{vehicle: ?string}> $rows */
    public function fromCatalog(array $rows): array
    {
        $aliases = ['MERCEDES BENZ' => 'MERCEDES', 'VW' => 'VOLKSWAGEN', 'DC' => 'DACIA'];
        $prefixes = array_merge($aliases, array_combine(array_keys(self::LOGOS), array_keys(self::LOGOS)));
        uksort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        $present = [];

        foreach ($rows as $row) {
            foreach (preg_split('/[,;\/|]+/', (string) ($row['vehicle'] ?? '')) as $vehicle) {
                $normalized = mb_strtoupper(trim($vehicle));
                $normalized = strtr($normalized, ['Ë' => 'E', 'É' => 'E', 'È' => 'E', 'Š' => 'S', '_' => ' ', '-' => ' ']);
                $normalized = preg_replace('/\s+/', ' ', $normalized);

                foreach ($prefixes as $prefix => $key) {
                    if ($normalized !== $prefix && !str_starts_with($normalized, $prefix . ' ')) {
                        continue;
                    }

                    // Keep a literal catalog prefix so the existing vehicle filter remains valid.
                    $filter = trim(mb_substr(trim($vehicle), 0, mb_strlen($prefix)));
                    $present[$key] ??= [
                        'label' => self::LOGOS[$key][0],
                        'logo' => 'img/vehicle-brands/' . self::LOGOS[$key][1] . '.webp',
                        'filter' => $filter,
                    ];
                    break;
                }
            }
        }

        return array_values(array_replace(array_intersect_key(self::LOGOS, $present), $present));
    }
}

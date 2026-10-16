<?php

// Format and size conversion only; keep the original vehicle logos intact.
$root = dirname(__DIR__);

function webp(string $source, string $target, int $maxWidth, int $quality): void
{
    $image = imagecreatefromstring(file_get_contents($source));
    if (!$image) {
        throw new RuntimeException('Invalid image: ' . $source);
    }
    if (imagesx($image) > $maxWidth) {
        $scaled = imagescale($image, $maxWidth);
        imagedestroy($image);
        $image = $scaled;
    }
    imagepalettetotruecolor($image);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0775, true);
    }
    if (!imagewebp($image, $target, $quality)) {
        throw new RuntimeException('Unable to save: ' . $target);
    }
    imagedestroy($image);
}

$logos = ['renault', 'peugeot', 'citroen', 'volkswagen', 'audi', 'bmw', 'mercedes', 'toyota',
    'hyundai', 'kia', 'ford', 'nissan', 'dacia', 'fiat', 'seat', 'skoda', 'opel', 'honda',
    'suzuki', 'mitsubishi', 'chevrolet', 'alfa_romeo', 'land_rover', 'range_rover', 'volvo',
    'mini', 'jeep', 'isuzu', 'iveco', 'jaguar', 'porsche', 'daihatsu', 'lancia', 'ssangyong', 'byd', 'cupra'];
foreach ($logos as $logo) {
    webp($root . '/public/img/' . $logo . '.png', $root . '/public/img/vehicle-brands/' . $logo . '.webp', 150, 85);
}
echo 'Optimized ' . count($logos) . ' existing vehicle logos.' . PHP_EOL;

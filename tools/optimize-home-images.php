<?php

declare(strict_types=1);

$projectDir = dirname(__DIR__);
$publicDir = $projectDir . '/public';

$jobs = [];

$addFile = static function (string $relativePath, int $maxWidth, int $quality = 76) use (&$jobs, $publicDir): void {
    $absolutePath = $publicDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (!is_file($absolutePath)) {
        return;
    }

    $jobs[$relativePath] = [$relativePath, $maxWidth, $quality];
};

$addDirectory = static function (string $relativeDirectory, int $maxWidth, int $quality = 76) use (&$jobs, $publicDir): void {
    $absoluteDirectory = $publicDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

    if (!is_dir($absoluteDirectory)) {
        return;
    }

    $iterator = new DirectoryIterator($absoluteDirectory);
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            continue;
        }

        $relativePath = trim($relativeDirectory, '/') . '/' . $file->getFilename();
        $jobs[$relativePath] = [$relativePath, $maxWidth, $quality];
    }
};

$addFile('img/logo-3sk-partsauto-transparent.png', 420, 82);
$addFile('img/bg-section/stock-entrepot-pieces-carrosserie-automobile-3sk-parts-auto.jpeg', 1200, 76);
$addFile('img/bg-section/stock-cartons-depo-pieces-carrosserie-3sk-parts-auto.png', 1200, 76);
$addFile('img/bg-section/services-stock.jpg', 1200, 76);
$addFile('img/bg-section/contact-cover.jpg', 900, 76);
$addFile('img/home-public/about-company-3sk.png', 1100, 76);
$addFile('img/home-public/contact-cover.jpg', 900, 76);

$addDirectory('img/slides', 1600, 76);
$addDirectory('img/bloc', 900, 74);
$addDirectory('img/logo-marques-home-hors-ligne', 280, 80);

$loadImage = static function (string $path): GdImage|false {
    $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    return match ($extension) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($path),
        'png' => @imagecreatefrompng($path),
        'webp' => @imagecreatefromwebp($path),
        default => false,
    };
};

$optimizedCount = 0;
$originalBytes = 0;
$optimizedBytes = 0;

foreach ($jobs as [$relativePath, $maxWidth, $quality]) {
    $sourcePath = $publicDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    $extension = strtolower((string) pathinfo($sourcePath, PATHINFO_EXTENSION));

    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        continue;
    }

    $image = $loadImage($sourcePath);
    if (!$image instanceof GdImage) {
        fwrite(STDERR, "Image ignorée : {$relativePath}\n");
        continue;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $targetWidth = min($width, $maxWidth);
    $targetHeight = (int) max(1, round($height * ($targetWidth / $width)));

    $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
    imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

    $directory = str_replace('\\', '/', (string) pathinfo($relativePath, PATHINFO_DIRNAME));
    $filename = (string) pathinfo($relativePath, PATHINFO_FILENAME);

    if (str_starts_with($directory, 'img/')) {
        $targetRelativePath = 'img/optimized/' . substr($directory, 4) . '/' . $filename . '.webp';
    } else {
        $targetRelativePath = 'img/optimized/' . $filename . '.webp';
    }

    $targetPath = $publicDir . '/' . str_replace('/', DIRECTORY_SEPARATOR, $targetRelativePath);
    $targetDirectory = dirname($targetPath);

    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
        throw new RuntimeException('Impossible de créer le dossier ' . $targetDirectory);
    }

    imagewebp($canvas, $targetPath, $quality);

    imagedestroy($image);
    imagedestroy($canvas);

    $sourceSize = filesize($sourcePath) ?: 0;
    $targetSize = filesize($targetPath) ?: 0;

    $optimizedCount++;
    $originalBytes += $sourceSize;
    $optimizedBytes += $targetSize;

    printf(
        "%s -> %s (%s -> %s)\n",
        $relativePath,
        $targetRelativePath,
        number_format($sourceSize / 1024, 1, ',', ' ') . ' Ko',
        number_format($targetSize / 1024, 1, ',', ' ') . ' Ko'
    );
}

printf(
    "\n%d images optimisées. Total : %s -> %s.\n",
    $optimizedCount,
    number_format($originalBytes / 1024 / 1024, 2, ',', ' ') . ' Mo',
    number_format($optimizedBytes / 1024 / 1024, 2, ',', ' ') . ' Mo'
);

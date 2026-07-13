<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class OptimizedAssetExtension extends AbstractExtension
{
    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private readonly Packages $packages,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('optimized_asset', $this->optimizedAsset(...)),
        ];
    }

    public function optimizedAsset(string $path, ?string $packageName = null): string
    {
        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
        $optimizedPath = $this->buildOptimizedPath($normalizedPath);

        if ($optimizedPath !== null && is_file($this->projectDir . '/public/' . $optimizedPath)) {
            return $this->packages->getUrl($optimizedPath, $packageName);
        }

        return $this->packages->getUrl($normalizedPath, $packageName);
    }

    private function buildOptimizedPath(string $path): ?string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if (!in_array($extension, self::IMAGE_EXTENSIONS, true)) {
            return null;
        }

        $directory = str_replace('\\', '/', (string) pathinfo($path, PATHINFO_DIRNAME));
        $filename = (string) pathinfo($path, PATHINFO_FILENAME);

        if ($directory === '.' || $directory === '') {
            return 'img/optimized/' . $filename . '.webp';
        }

        if (str_starts_with($directory, 'img/')) {
            return 'img/optimized/' . substr($directory, 4) . '/' . $filename . '.webp';
        }

        return null;
    }
}

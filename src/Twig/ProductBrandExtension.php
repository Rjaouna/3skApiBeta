<?php

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ProductBrandExtension extends AbstractExtension
{
    private const DIRECTORY = 'img/brand-logos';
    private const EXTENSIONS = ['webp', 'png', 'jpg', 'jpeg', 'svg'];

    public function __construct(
        private readonly Packages $packages,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('product_brand_logos', $this->logos(...))];
    }

    public function logos(): array
    {
        $directory = $this->projectDir . '/public/' . self::DIRECTORY;
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (new \DirectoryIterator($directory) as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }
            $slug = $file->getBasename('.' . $file->getExtension());
            $extension = $file->getExtension();
            if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)
                && in_array($extension, self::EXTENSIONS, true)) {
                $files[$extension][$slug] = $file->getFilename();
            }
        }

        $logos = [];
        foreach (self::EXTENSIONS as $extension) {
            foreach ($files[$extension] ?? [] as $slug => $filename) {
                if (!isset($logos[$slug])) {
                    $url = $this->packages->getUrl(self::DIRECTORY . '/' . $filename);
                    $logos[$slug] = $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($directory . '/' . $filename);
                }
            }
        }
        ksort($logos);

        return $logos;
    }
}

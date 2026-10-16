<?php

namespace App\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class HeroSlideTemplateTest extends TestCase
{
    public function testFirstSlideUsesTheApprovedIntroWithoutReplacingItsImage(): void
    {
        $html = $this->render([$this->slide()]);
        $page = new Crawler($html);

        self::assertSame('3SK PARTS AUTO', trim($page->filter('h1.showcase-title')->text()));
        self::assertSame('PARTS', $page->filter('h1.showcase-title span')->text());
        self::assertStringContainsString('Votre partenaire en pièces et accessoires automobiles', $page->filter('.showcase-description')->text());
        self::assertCount(3, $page->filter('.showcase-benefits li'));
        self::assertSame('/articles', $page->filter('.showcase-action--primary')->attr('href'));
        self::assertSame('#marques', $page->filter('.showcase-action--secondary')->attr('href'));
        self::assertSame('/img/slides/original.webp', $page->filter('.showcase-hero__media img')->attr('src'));
        self::assertSame('high', $page->filter('.showcase-hero__media img')->attr('fetchpriority'));
        self::assertCount(0, $page->filter('.showcase-admin-actions, .showcase-controls'));
        self::assertSame('6500', $page->filter('#heroCarousel')->attr('data-bs-interval'));
    }

    public function testSubsequentConfiguredTitlesAreEscaped(): void
    {
        $title = 'Un titre configure <script>alert(1)</script>';
        $page = new Crawler($this->render([$this->slide(), $this->slide(['id' => 13, 'title' => $title])]));

        self::assertSame($title, $page->filter('h2.showcase-managed-title')->text());
        self::assertSame('Texte configure', $page->filter('.carousel-item')->eq(1)->filter('.showcase-description')->text());
        self::assertCount(0, $page->filter('script:not([src])'));
    }

    public function testOnlyFullyConfiguredLinksAreDisplayed(): void
    {
        $page = (new Crawler($this->render([$this->slide(), $this->slide([
            'id' => 13,
            'primaryLabel' => '  Catalogue  ', 'primaryUrl' => ' /articles ',
            'secondaryLabel' => 'Sans destination',
        ])])))->filter('.carousel-item')->eq(1);

        self::assertCount(1, $page->filter('.showcase-actions a'));
        self::assertSame('Catalogue', $page->filter('.showcase-action--primary')->text());
        self::assertSame('/articles', $page->filter('.showcase-action--primary')->attr('href'));
        self::assertCount(0, $page->filter('.showcase-action--secondary'));
    }

    public function testAdminActionsKeepTheirMethodsAndCsrfIdentifiers(): void
    {
        $page = new Crawler($this->render([$this->slide()], true));

        self::assertCount(2, $page->filter('.showcase-admin-actions form[method="post"]'));
        self::assertSame('bump_slide_12', $page->filter('input[name="_token"]')->eq(0)->attr('value'));
        self::assertSame('delete_slide_12', $page->filter('input[name="_token"]')->eq(1)->attr('value'));
        self::assertSame('/admin_hero_slide_edit/12', $page->filter('.showcase-admin-actions > a')->attr('href'));
        self::assertStringContainsString('return confirm', $page->filter('.showcase-admin-actions form')->eq(1)->attr('onsubmit'));
    }

    public function testMultipleSlidesKeepLazyMediaAndNavigation(): void
    {
        $page = new Crawler($this->render([
            $this->slide(),
            $this->slide(['id' => 13, 'image' => 'second.webp', 'title' => 'Deuxieme slide']),
            $this->slide(['id' => 14, 'image' => 'video.mp4', 'title' => 'Video']),
        ]));

        self::assertCount(3, $page->filter('.carousel-item'));
        self::assertCount(1, $page->filter('.carousel-item.active'));
        self::assertCount(3, $page->filter('.showcase-pagination button'));
        self::assertCount(2, $page->filter('.showcase-controls button[data-bs-target="#heroCarousel"]'));
        self::assertSame('/img/slides/second.webp', $page->filter('img[data-hero-lazy]')->attr('data-src'));
        self::assertSame('/img/slides/video.mp4', $page->filter('video[data-hero-lazy-video] source')->attr('data-src'));
        self::assertSame('video/mp4', $page->filter('video source')->attr('type'));
    }

    public function testEmptySliderDoesNotInventAnyContent(): void
    {
        $page = new Crawler($this->render([]));
        self::assertCount(0, $page->filter('.carousel'));
        self::assertSame('Aucun slide', trim($page->filter('.alert')->text()));
    }

    private function render(array $slides, bool $admin = false): string
    {
        $twig = new Environment(new FilesystemLoader(dirname(__DIR__, 2) . '/templates'), ['strict_variables' => true, 'autoescape' => 'html']);
        $twig->addFunction(new TwigFunction('asset', static fn (string $path): string => '/' . $path));
        $twig->addFunction(new TwigFunction('optimized_asset', static fn (string $path): string => '/' . $path));
        $twig->addFunction(new TwigFunction('path', static fn (string $route, array $parameters = []): string => $route === 'articles_page' ? '/articles' : '/' . $route . '/' . ($parameters['id'] ?? '')));
        $twig->addFunction(new TwigFunction('is_granted', static fn (string $role): bool => $admin && $role === 'ROLE_ADMIN'));
        $twig->addFunction(new TwigFunction('csrf_token', static fn (string $id): string => $id));

        return $twig->render('home/sections/_hero.html.twig', ['slides' => $slides, 'hero_full_width' => true]);
    }

    private function slide(array $values = []): array
    {
        return $values + [
            'id' => 12, 'image' => 'original.webp', 'title' => '3SK Parts Auto',
            'subtitle' => 'Texte configure', 'primaryLabel' => null, 'primaryUrl' => null,
            'secondaryLabel' => null, 'secondaryUrl' => null,
        ];
    }
}

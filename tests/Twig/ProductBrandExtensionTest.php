<?php

namespace App\Tests\Twig;

use App\Twig\ProductBrandExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

final class ProductBrandExtensionTest extends TestCase
{
    private string $project;
    private string $directory;

    protected function setUp(): void
    {
        $this->project = sys_get_temp_dir() . '/3sk-brand-test-' . bin2hex(random_bytes(8));
        $this->directory = $this->project . '/public/img/brand-logos';
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->directory);
        rmdir(dirname($this->directory));
        rmdir(dirname($this->directory, 2));
        rmdir($this->project);
    }

    private function extension(): ProductBrandExtension
    {
        return new ProductBrandExtension(new Packages(new Package(new EmptyVersionStrategy())), $this->project);
    }

    public function testNewFilesAreDetectedWithoutAMappingAndWithVersionedUrls(): void
    {
        $extension = $this->extension();
        self::assertSame([], $extension->logos());
        file_put_contents($this->directory . '/nouvelle-marque.png', 'test fixture');
        touch($this->directory . '/nouvelle-marque.png', 1700000000);
        self::assertSame(['nouvelle-marque' => 'img/brand-logos/nouvelle-marque.png?v=1700000000'], $extension->logos());
    }

    public function testOnlyCanonicalImageNamesArePublished(): void
    {
        foreach (['README.md', 'Brand.png', 'avec_espace.png', 'bad--slug.jpg', '-prefix.png', 'valid-name.php', 'suffix-.png', 'logo.PNG', 'valide.svg'] as $name) {
            file_put_contents($this->directory . '/' . $name, 'test fixture');
        }
        self::assertSame(['valide'], array_keys($this->extension()->logos()));
    }

    public function testFormatPriorityIsStable(): void
    {
        foreach (['png', 'webp', 'jpg', 'jpeg', 'svg'] as $extension) {
            file_put_contents($this->directory . '/phira.' . $extension, 'test fixture');
        }
        $logos = $this->extension()->logos();
        self::assertCount(1, $logos);
        self::assertStringContainsString('phira.webp?v=', $logos['phira']);
    }
}

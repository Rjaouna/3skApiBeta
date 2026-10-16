<?php

namespace App\Tests\Service;

use App\Service\ArticleImageLocator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ArticleImageLocatorTest extends TestCase
{
    private const ID = '4043FBEB-473B-4079-9D7A-0002AF44A1FC';
    private const PNG = "\x89PNG\r\n\x1a\nfixture";
    private string $root;
    private ArticleImageLocator $images;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/3sk-photo-test-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/project/public/ebpsynch', 0777, true);
        mkdir($this->root . '/project/export_images', 0777, true);
        mkdir($this->root . '/configured-export');
        $this->images = new ArticleImageLocator($this->root . '/project');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->root);
    }

    public function testImagesAreReadFromTheConfiguredImportDirectoryWithoutMovingThem(): void
    {
        $path = $this->root . '/configured-export/' . self::ID . '.png';
        file_put_contents($path, self::PNG);
        file_put_contents($this->root . '/configured-export/items.csv', 'private csv');
        self::assertSame(realpath($path), $this->images->find(self::ID, $this->root . '/configured-export'));
        self::assertSame('private csv', file_get_contents($this->root . '/configured-export/items.csv'));
    }

    public function testExistingLegacyImagesKeepPriority(): void
    {
        $path = $this->root . '/project/public/ebpsynch/' . self::ID . '.png';
        file_put_contents($path, self::PNG);
        file_put_contents($this->root . '/configured-export/' . self::ID . '.png', self::PNG);
        self::assertSame(realpath($path), $this->images->find(self::ID, $this->root . '/configured-export'));
    }

    public function testProjectExportDirectoryAndFilenameCaseAreSupported(): void
    {
        $path = $this->root . '/project/export_images/' . strtolower(self::ID) . '.PNG';
        file_put_contents($path, self::PNG);
        self::assertSame(realpath($path), $this->images->find(self::ID, '/missing-directory'));
    }

    public function testArbitraryNamesTraversalAndRenamedCsvAreRejected(): void
    {
        foreach (['items.csv', '../items', '../' . self::ID, self::ID . '.png', 'not-a-uuid'] as $name) {
            self::assertNull($this->images->find($name, $this->root . '/configured-export'));
        }
        file_put_contents($this->root . '/configured-export/' . self::ID . '.png', 'private csv content');
        self::assertNull($this->images->find(self::ID, $this->root . '/configured-export'));
    }

    public function testMissingImagesReturnNull(): void
    {
        self::assertNull($this->images->find(self::ID, null));
    }

    public function testSymlinkCannotEscapeTheConfiguredDirectory(): void
    {
        $outside = $this->root . '/outside.png';
        file_put_contents($outside, self::PNG);
        if (!@symlink($outside, $this->root . '/configured-export/' . self::ID . '.png')) {
            self::markTestSkipped('Symlinks are unavailable on this platform.');
        }
        self::assertNull($this->images->find(self::ID, $this->root . '/configured-export'));
    }
}

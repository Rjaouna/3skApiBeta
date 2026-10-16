<?php

namespace App\Tests\Controller;

use App\Controller\ArticleImageController;
use App\Entity\ImportSource;
use App\Service\ArticleImageLocator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ArticleImageControllerTest extends TestCase
{
    private const ID = '4043FBEB-473B-4079-9D7A-0002AF44A1FC';
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScLttAAAAABJRU5ErkJggg==';
    private string $root;
    private string $path;
    private EntityManagerInterface $entityManager;
    private ArticleImageLocator $images;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/3sk-photo-http-test-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/export_images', 0777, true);
        $this->path = $this->root . '/export_images/' . self::ID . '.png';
        file_put_contents($this->path, base64_decode(self::PNG, true));
        file_put_contents($this->root . '/export_images/items.csv', 'private csv');

        $source = (new ImportSource())->setDirectoryPath($this->root . '/export_images');
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($source);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->entityManager->method('getRepository')->willReturn($repository);
        $this->images = new ArticleImageLocator($this->root . '/project');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->root);
    }

    public function testResponseSendsTheOriginalPngWithPrivateCaching(): void
    {
        $response = $this->respond(Request::create('/ebpsynch/' . self::ID . '.png'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('image/png', $response->headers->get('Content-Type'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
        self::assertSame(3600, $response->getMaxAge());
        self::assertNotNull($response->getEtag());
        self::assertNotNull($response->getLastModified());
        self::assertSame(base64_decode(self::PNG, true), $this->body($response));
        self::assertSame('private csv', file_get_contents($this->root . '/export_images/items.csv'));
    }

    public function testHeadReturnsImageHeadersWithoutTheBody(): void
    {
        $response = $this->respond(Request::create('/ebpsynch/' . self::ID . '.png', 'HEAD'));

        self::assertSame(200, $response->getStatusCode());
        self::assertSame((string) filesize($this->path), $response->headers->get('Content-Length'));
        self::assertSame('', $this->body($response));
    }

    public function testEtagRevalidationReturnsNotModified(): void
    {
        $url = '/ebpsynch/' . self::ID . '.png';
        $original = $this->respond(Request::create($url));
        $request = Request::create($url);
        $request->headers->set('If-None-Match', $original->getEtag());
        $response = $this->respond($request);

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('', $this->body($response));
    }

    public function testLastModifiedRevalidationReturnsNotModified(): void
    {
        $url = '/export_images/' . self::ID . '.png';
        $original = $this->respond(Request::create($url));
        $request = Request::create($url);
        $request->headers->set('If-Modified-Since', $original->headers->get('Last-Modified'));
        $response = $this->respond($request);

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('', $this->body($response));
    }

    public function testMissingImageReturnsNotFound(): void
    {
        unlink($this->path);
        $this->expectException(NotFoundHttpException::class);
        $this->respond(Request::create('/ebpsynch/' . self::ID . '.png'));
    }

    public function testCsvCannotBeReturnedAsAnImage(): void
    {
        $this->expectException(NotFoundHttpException::class);
        (new ArticleImageController())->show(
            'items.csv',
            Request::create('/export_images/items.csv'),
            $this->entityManager,
            $this->images,
        );
    }

    private function respond(Request $request): BinaryFileResponse
    {
        return (new ArticleImageController())->show(
            self::ID,
            $request,
            $this->entityManager,
            $this->images,
        )->prepare($request);
    }

    private function body(BinaryFileResponse $response): string
    {
        ob_start();
        try {
            $response->sendContent();
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}

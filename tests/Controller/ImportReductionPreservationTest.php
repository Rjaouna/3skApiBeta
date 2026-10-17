<?php

namespace App\Tests\Controller;

use App\Controller\ImportController;
use App\Entity\Article;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ImportReductionPreservationTest extends TestCase
{
    public function testImportDoesNotCompareOrOverwriteALocalReduction(): void
    {
        $controller = new ImportController(
            $this->createStub(CsrfTokenManagerInterface::class),
            $this->createStub(LoggerInterface::class),
        );
        $normalized = $this->invokePrivate($controller, 'buildNormalizedRow', [
            ['REF-1', '11111111-1111-1111-1111-111111111111'],
            ['Id' => 0, 'UniqueId' => 1],
        ]);

        self::assertArrayNotHasKey('reduction', $normalized);

        $article = (new Article())
            ->setUniqueId($normalized['UniqueId'])
            ->setId($normalized['Id'])
            ->setReduction('35');

        self::assertTrue($this->invokePrivate($controller, 'isSameAsDb', [$article, $normalized]));

        $this->invokePrivate($controller, 'applyRow', [$article, $normalized]);

        self::assertSame('35', $article->getReduction());
    }

    private function invokePrivate(object $object, string $method, array $arguments): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);

        return $reflection->invokeArgs($object, $arguments);
    }
}

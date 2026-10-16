<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class ArticleImageLocator
{
    public const UUID_PATTERN = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    public function find(string $uniqueId, ?string $importDirectory): ?string
    {
        if (!preg_match('/^' . self::UUID_PATTERN . '$/D', $uniqueId)) {
            return null;
        }

        $directories = [
            $this->projectDir . '/public/ebpsynch',
            $importDirectory,
            $this->projectDir . '/export_images',
        ];
        $names = array_unique([$uniqueId, strtolower($uniqueId), strtoupper($uniqueId)]);

        foreach (array_unique(array_filter($directories)) as $directory) {
            $root = realpath($directory);
            if ($root === false || !is_dir($root)) {
                continue;
            }
            $prefix = rtrim($root, '/\\') . DIRECTORY_SEPARATOR;

            foreach ($names as $name) {
                foreach (['png', 'PNG'] as $extension) {
                    $path = realpath($prefix . $name . '.' . $extension);
                    if ($path === false || !str_starts_with($path, $prefix) || !is_file($path) || !is_readable($path)) {
                        continue;
                    }

                    // Never expose a CSV or another file renamed with a .png suffix.
                    $stream = @fopen($path, 'rb');
                    if ($stream === false) {
                        continue;
                    }
                    $signature = fread($stream, 8);
                    fclose($stream);
                    if ($signature === "\x89PNG\r\n\x1a\n") {
                        return $path;
                    }
                }
            }
        }

        return null;
    }
}

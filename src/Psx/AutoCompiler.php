<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Psx;

use Polidog\UsePhp\Psx\CompileCommand;
use RuntimeException;

/**
 * Compiles a directory of .psx components into the PSX cache (with manifest),
 * reusing usePHP's own CLI compiler so `vendor/bin/usephp compile` and the
 * dev auto-compile populate the same cache.
 */
final class AutoCompiler
{
    public static function manifestPath(string $cacheDir): string
    {
        return rtrim($cacheDir, '/') . '/' . CompileCommand::MANIFEST_FILENAME;
    }

    /**
     * Compile when the manifest is missing or any .psx is newer than it.
     * Returns true when a compile ran.
     */
    public static function compileIfStale(string $componentsDir, string $cacheDir): bool
    {
        if (!is_dir($componentsDir)) {
            return false;
        }

        $manifest = self::manifestPath($cacheDir);
        $manifestTime = is_file($manifest) ? (filemtime($manifest) ?: 0) : 0;

        if ($manifestTime > 0 && !self::hasNewerSource($componentsDir, $manifestTime)) {
            return false;
        }

        self::compile($componentsDir, $cacheDir);

        return true;
    }

    public static function compile(string $componentsDir, string $cacheDir): void
    {
        // The compiler prints a progress line per file; swallow it so web
        // requests stay clean. Failures still surface through the exit code.
        ob_start();
        try {
            $exitCode = (new CompileCommand())->run(
                [$componentsDir, '--cache=' . $cacheDir],
                $componentsDir,
            );
        } finally {
            ob_end_clean();
        }

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "PSX compile failed (exit {$exitCode}). Run `php artisan usephp:compile` to see the error.",
            );
        }
    }

    private static function hasNewerSource(string $componentsDir, int $since): bool
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($componentsDir, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'psx' && $file->getMTime() > $since) {
                return true;
            }
        }

        return false;
    }
}

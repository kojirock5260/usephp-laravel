<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Tests;

use Illuminate\Filesystem\Filesystem;
use Kojirock5260\UsePhpLaravel\UsePhpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected string $cacheDir;

    protected function getPackageProviders($app): array
    {
        return [UsePhpServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $this->cacheDir = sys_get_temp_dir().'/usephp-laravel-tests-'.getmypid();

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('usephp.components_path', __DIR__.'/fixtures/Components');
        $app['config']->set('usephp.cache_path', $this->cacheDir);
        $app['config']->set('usephp.auto_compile', true);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->cacheDir);
        parent::tearDown();
    }
}

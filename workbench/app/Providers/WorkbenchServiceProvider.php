<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;

use function Orchestra\Testbench\workbench_path;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Point the bridge at the sandbox's components. In a real app the
        // defaults (app/Components, storage/framework/psx) apply.
        config([
            'usephp.components_path' => workbench_path('app/Components'),
            'usephp.cache_path' => storage_path('framework/psx'),
            'usephp.auto_compile' => true,
        ]);
    }

    public function boot(): void {}
}

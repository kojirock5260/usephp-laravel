<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Kojirock5260\UsePhpLaravel\Console\CompileCommand;
use Kojirock5260\UsePhpLaravel\Http\ActionHandler;
use Kojirock5260\UsePhpLaravel\Psx\AutoCompiler;
use Kojirock5260\UsePhpLaravel\Psx\ComponentRenderer;
use Polidog\UsePhp\UsePHP;

final class UsePhpServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/usephp.php', 'usephp');

        $this->app->singleton(UsePHP::class, function (): UsePHP {
            /** @var array{components_path: string, cache_path: string, auto_compile: bool, snapshot_secret: ?string, defer_prefix: string} $config */
            $config = $this->config()->get('usephp');

            $app = (new UsePHP())
                ->disableRouter()               // Laravel owns routing
                ->setDeferPrefix($config['defer_prefix'])
                ->setSnapshotSecret($this->snapshotSecret($config));

            $components = $config['components_path'];
            $cache = $config['cache_path'];

            if ($config['auto_compile']) {
                AutoCompiler::compileIfStale($components, $cache);
            }

            $manifest = AutoCompiler::manifestPath($cache);
            if (is_file($manifest)) {
                $app->loadComponentManifest($manifest);
            }

            return $app;
        });

        $this->app->singleton(ComponentRenderer::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/usephp.php');
        $this->registerBlade();
        $this->registerRouteMacro();

        if ($this->app->runningInConsole()) {
            $this->commands([CompileCommand::class]);

            $this->publishes([
                __DIR__ . '/../config/usephp.php' => config_path('usephp.php'),
            ], 'usephp-config');

            $this->publishes([
                $this->usePhpPackageDir() . '/public/usephp.js' => public_path('vendor/usephp/usephp.js'),
            ], 'usephp-assets');
        }
    }

    private function registerBlade(): void
    {
        // @usephp(\App\Components\Counter::class, ['initial' => 0])
        Blade::directive('usephp', static fn (string $expression): string =>
            "<?php echo app(\\Kojirock5260\\UsePhpLaravel\\Psx\\ComponentRenderer::class)->render({$expression}); ?>");

        // @usephpScript — loads the published usephp.js (progressive enhancement layer)
        Blade::directive('usephpScript', static fn (): string =>
            "<?php echo \\Polidog\\UsePhp\\UsePHP::renderClientScript((string) config('usephp.script_path')); ?>");
    }

    private function registerRouteMacro(): void
    {
        $action = static fn (string $uri, string $fqcn, array $props = []) => Route::post(
            $uri,
            static fn (Request $request) => app(ActionHandler::class)->handle($request, $fqcn, $props),
        );

        // Route::usephp('/counter', Counter::class, ['initial' => 0]);
        // GET renders the component; POST applies its form actions.
        Route::macro('usephp', static function (string $uri, string $fqcn, array $props = []) use ($action) {
            $action($uri, $fqcn, $props);

            return Route::get(
                $uri,
                static fn () => response(app(ComponentRenderer::class)->render($fqcn, $props)),
            );
        });

        // Route::usephpAction('/', Counter::class, ['initial' => 0]);
        // POST only: for a component embedded in a Blade page served by another route.
        Route::macro('usephpAction', $action);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function snapshotSecret(array $config): string
    {
        $explicit = $config['snapshot_secret'] ?? null;
        if (is_string($explicit) && trim($explicit) !== '') {
            return trim($explicit);
        }

        // Derive a dedicated key from APP_KEY so Snapshot storage works
        // with zero extra configuration, without reusing the key directly.
        $appKey = (string) $this->config()->get('app.key', '');
        if (str_starts_with($appKey, 'base64:')) {
            $appKey = (string) base64_decode(substr($appKey, 7), true);
        }

        return hash_hmac('sha256', 'usephp-snapshot', $appKey);
    }

    private function config(): Repository
    {
        return $this->app->make(Repository::class);
    }

    private function usePhpPackageDir(): string
    {
        return dirname((new \ReflectionClass(UsePHP::class))->getFileName() ?: '', 2);
    }
}

<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Psx;

use Kojirock5260\UsePhpLaravel\Http\CsrfTokenInjector;
use Polidog\UsePhp\Runtime\ComponentState;
use Polidog\UsePhp\Runtime\RenderContext;
use Polidog\UsePhp\Storage\StorageFactory;
use Polidog\UsePhp\UsePHP;

/**
 * Renders a compiled PSX component (by FQCN) to an HTML string.
 *
 * This is one of the few classes that talks to usePHP directly, so that
 * upstream API changes stay contained here.
 */
final class ComponentRenderer
{
    public function __construct(private readonly UsePHP $app) {}

    public function app(): UsePHP
    {
        return $this->app;
    }

    /**
     * @param  class-string|string  $fqcn  e.g. App\Components\Counter
     * @param  array<string, mixed>  $props
     */
    public function render(string $fqcn, array $props = []): string
    {
        // Nested <Component /> tags in compiled PSX resolve through the
        // ambient app, so it must be set before the component runs.
        RenderContext::setApp($this->app);
        try {
            RenderContext::beginRender();
            $element = $this->app->renderPsxComponent($fqcn, $props);

            return CsrfTokenInjector::inject($this->app->renderElement($element));
        } finally {
            RenderContext::clearApp();
            // usePHP keeps component state in process-wide statics; drop it
            // so nothing leaks into the next render (or the next request
            // under Octane).
            ComponentState::clearInstances();
            StorageFactory::reset();
        }
    }
}

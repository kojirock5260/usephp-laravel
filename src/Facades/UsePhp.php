<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Facades;

use Illuminate\Support\Facades\Facade;
use Kojirock5260\UsePhpLaravel\Psx\ComponentRenderer;

/**
 * @method static string render(string $fqcn, array<string, mixed> $props = [])
 * @method static \Polidog\UsePhp\UsePHP app()
 *
 * @see ComponentRenderer
 */
final class UsePhp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ComponentRenderer::class;
    }
}

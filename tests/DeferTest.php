<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Tests;

use Kojirock5260\UsePhpLaravel\Facades\UsePhp;

final class DeferTest extends TestCase
{
    public function test_page_renders_a_placeholder_with_the_defer_url(): void
    {
        $html = UsePhp::render('App\Components\DeferPage');

        $this->assertStringContainsString('data-usephp-defer-url="/_defer/time?when=now"', $html);
        $this->assertStringContainsString('<p>loading</p>', $html);
        $this->assertStringNotContainsString('It is now', $html);
    }

    public function test_defer_endpoint_renders_the_fragment_with_its_cache_header(): void
    {
        UsePhp::render('App\Components\DeferPage');   // boots usePHP + registry

        $response = $this->get('/_defer/time?when=now')
            ->assertOk()
            ->assertSee('It is now');

        // Symfony normalises directive order, so compare as a set.
        $directives = array_map('trim', explode(',', (string) $response->headers->get('Cache-Control')));
        sort($directives);
        $this->assertSame(['no-store', 'private'], $directives);
    }

    public function test_unknown_deferred_name_is_404(): void
    {
        $this->get('/_defer/nope')->assertNotFound();
    }

    public function test_non_scalar_param_is_400(): void
    {
        $this->get('/_defer/time?when[]=1')->assertStatus(400);
    }
}

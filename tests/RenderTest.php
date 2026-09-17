<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Tests;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Kojirock5260\UsePhpLaravel\Facades\UsePhp;

final class RenderTest extends TestCase
{
    public function test_renders_a_stateless_component(): void
    {
        $html = UsePhp::render('App\Components\Hello', ['name' => 'Laravel']);

        $this->assertStringContainsString('Hello, Laravel', $html);
        $this->assertStringContainsString('class="hello"', $html);
    }

    public function test_resolves_nested_component_tags_through_the_manifest(): void
    {
        $html = UsePhp::render('App\Components\Greeting', ['name' => 'Nested']);

        $this->assertStringContainsString('class="greeting"', $html);
        $this->assertStringContainsString('Hello, Nested', $html);
    }

    public function test_stateful_component_embeds_a_signed_snapshot(): void
    {
        $html = UsePhp::render('App\Components\Counter', ['initial' => 3]);

        $this->assertStringContainsString('data-usephp=', $html);
        $this->assertStringContainsString('data-usephp-snapshot=', $html);
        $this->assertStringContainsString('Count: 3', $html);
    }

    public function test_blade_directive_renders_a_component(): void
    {
        $html = Blade::render("@usephp('App\\Components\\Hello', ['name' => 'Blade'])");

        $this->assertStringContainsString('Hello, Blade', $html);
    }

    public function test_script_directive_points_at_the_published_asset(): void
    {
        $html = Blade::render('@usephpScript');

        $this->assertStringContainsString('src="/vendor/usephp/usephp.js"', $html);
    }

    public function test_route_macro_serves_a_component(): void
    {
        Route::usephp('/hello', 'App\Components\Hello', ['name' => 'Route']);

        $this->get('/hello')->assertOk()->assertSee('Hello, Route');
    }

    public function test_compile_command_runs(): void
    {
        $this->artisan('usephp:compile')->assertExitCode(0);

        $this->assertFileExists($this->cacheDir.'/manifest.php');
    }
}

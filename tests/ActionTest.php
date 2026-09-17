<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Tests;

use Illuminate\Support\Facades\Route;

final class ActionTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(static function (): void {
            Route::usephp('/counter', 'App\Components\Counter', ['initial' => 3]);
        });
    }

    public function test_rendered_forms_carry_laravels_csrf_token(): void
    {
        $html = $this->get('/counter')->assertOk()->getContent();

        $this->assertStringContainsString('name="_token"', $html);
    }

    public function test_partial_post_applies_the_action_and_returns_the_fragment(): void
    {
        $form = $this->firstForm();

        $response = $this->withHeader('X-UsePHP-Partial', '1')->post('/counter', $form)->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('Count: 4', $html);
        $this->assertStringContainsString('data-usephp-snapshot-update', $html);
        $this->assertStringNotContainsString('data-usephp="FC', $html);   // no wrapper: innerHTML only
        $this->assertStringContainsString('name="_token"', $html);        // re-rendered forms are protected too
    }

    public function test_plain_post_returns_the_whole_component(): void
    {
        $html = $this->post('/counter', $this->firstForm())->assertOk()->getContent();

        $this->assertStringContainsString('data-usephp="FC', $html);
        $this->assertStringContainsString('Count: 4', $html);
    }

    public function test_post_without_csrf_token_is_rejected(): void
    {
        $form = $this->firstForm();
        unset($form['_token']);

        // Laravel skips CSRF checks while the env is "testing"; pretend otherwise.
        $this->app['env'] = 'local';

        $this->post('/counter', $form)->assertStatus(419);
    }

    public function test_tampered_snapshot_is_rejected(): void
    {
        $form = $this->firstForm();
        $form['_usephp_snapshot'] = str_replace('"state":[3]', '"state":[999]', $form['_usephp_snapshot']);

        $this->post('/counter', $form)->assertStatus(400);
    }

    /**
     * Fetch the page and pull the hidden fields out of the first usePHP form (the "+" button).
     *
     * @return array<string, string>
     */
    private function firstForm(): array
    {
        $html = $this->get('/counter')->getContent();
        preg_match('/<form method="post" data-usephp-form[^>]*>(.*?)<\/form>/s', $html, $form);
        preg_match_all('/name="([^"]+)" value="([^"]*)"/', $form[1], $fields, PREG_SET_ORDER);

        $data = [];
        foreach ($fields as [, $name, $value]) {
            $data[$name] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        }

        return $data;
    }
}

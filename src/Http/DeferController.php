<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Polidog\UsePhp\Runtime\ComponentState;
use Polidog\UsePhp\Storage\StorageFactory;
use Polidog\UsePhp\UsePHP;

/**
 * Serves `GET {defer_prefix}/{name}` by delegating to usePHP's deferred
 * endpoint. usePHP emits headers and status codes directly, so both are
 * captured here and copied onto a Laravel response.
 */
final class DeferController
{
    public function __construct(private readonly UsePHP $app) {}

    public function __invoke(Request $request): Response
    {
        /** @var list<string> $captured */
        $captured = [];
        $this->app->withHeaderEmitter(static function (string $header) use (&$captured): void {
            $captured[] = $header;
        });

        http_response_code(200);   // baseline; usePHP overrides it on 4xx

        try {
            $html = $this->app->handleDeferred(RequestContextFactory::fromLaravel($request));
        } finally {
            $this->app->withHeaderEmitter(null);
            ComponentState::clearInstances();
            StorageFactory::reset();
        }

        $status = http_response_code();
        http_response_code(200);

        if ($html === null) {
            abort(404);
        }

        $response = new Response(CsrfTokenInjector::inject($html), is_int($status) && $status >= 100 ? $status : 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);

        foreach ($captured as $line) {
            [$name, $value] = array_pad(explode(':', $line, 2), 2, '');
            $response->header(trim($name), trim($value));
        }

        return $response;
    }
}

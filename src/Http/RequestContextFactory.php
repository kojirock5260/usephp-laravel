<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Http;

use Illuminate\Http\Request;
use Polidog\UsePhp\Router\RequestContext;

/**
 * Converts a Laravel request into usePHP's RequestContext.
 */
final class RequestContextFactory
{
    public static function fromLaravel(Request $request): RequestContext
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[strtolower((string) $name)] = implode(', ', array_map('strval', $values));
        }

        return new RequestContext(
            method: $request->getMethod(),
            path: '/'.ltrim($request->getPathInfo(), '/'),
            queryString: $request->getQueryString() ?? '',
            query: $request->query->all(),
            post: $request->request->all(),
            headers: $headers,
        );
    }
}

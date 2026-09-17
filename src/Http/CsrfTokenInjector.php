<?php

declare(strict_types=1);

namespace Kojirock5260\UsePhpLaravel\Http;

/**
 * usePHP renders its own <form> elements for onClick handlers and has no hook
 * for extra hidden fields. Laravel's VerifyCsrfToken expects `_token`, so it
 * is spliced into every usePHP form after rendering. That keeps Laravel's
 * standard CSRF middleware in charge instead of excluding routes from it.
 */
final class CsrfTokenInjector
{
    private const FORM_OPEN = '<form method="post" data-usephp-form style="display:inline;">';

    public static function inject(string $html): string
    {
        if (!str_contains($html, self::FORM_OPEN)) {
            return $html;
        }

        $token = self::token();
        if ($token === null) {
            return $html;
        }

        $field = '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" />';

        return str_replace(self::FORM_OPEN, self::FORM_OPEN . $field, $html);
    }

    private static function token(): ?string
    {
        if (!app()->bound('session')) {
            return null;
        }

        $token = app('session')->token();

        return is_string($token) && $token !== '' ? $token : null;
    }
}

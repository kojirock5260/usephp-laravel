<?php

declare(strict_types=1);

return [
    // Directory that holds your .psx components. Each file declares a PHP
    // namespace and returns a component callable; the file name is the
    // component's short name (app/Components/Counter.psx → App\Components\Counter).
    'components_path' => env('USEPHP_COMPONENTS_PATH', base_path('app/Components')),

    // Where compiled .psx files and the manifest are written.
    'cache_path' => env('USEPHP_CACHE_PATH', storage_path('framework/psx')),

    // Recompile stale .psx files on boot. Defaults to on in the local
    // environment; production should pre-compile with `php artisan usephp:compile`.
    'auto_compile' => env('USEPHP_AUTO_COMPILE', env('APP_ENV') === 'local'),

    // HMAC key for Snapshot storage. Derived from APP_KEY when null.
    'snapshot_secret' => env('USEPHP_SNAPSHOT_SECRET'),

    // URL prefix usePHP uses for deferred component fetches.
    'defer_prefix' => env('USEPHP_DEFER_PREFIX', '/_defer'),

    // Middleware for the defer endpoint. 'web' gives deferred components
    // access to the session (user-specific fragments).
    'defer_middleware' => ['web'],

    // Public URL of usephp.js (publish it with `artisan vendor:publish --tag=usephp-assets`).
    'script_path' => env('USEPHP_SCRIPT_PATH', '/vendor/usephp/usephp.js'),
];

<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kojirock5260\UsePhpLaravel\Http\DeferController;
use Polidog\UsePhp\UsePHP;

// GET /_defer/{name}?prop=value — serves deferred component fragments.
Route::middleware(config('usephp.defer_middleware', ['web']))
    ->get(rtrim((string) config('usephp.defer_prefix', '/_defer'), '/') . '/{name}', DeferController::class)
    ->where('name', UsePHP::DEFER_NAME_PATTERN)
    ->name('usephp.defer');

<?php

use Illuminate\Support\Facades\Route;
use Polidog\UsePhp\UsePHP;

Route::get('/', fn () => view('demo'));
Route::usephpAction('/', 'Workbench\App\Components\Counter', ['initial' => 5]);   // the Counter embedded in the Blade page

// Route macro: a PSX component as a whole response
Route::usephp('/counter', 'Workbench\App\Components\Counter', ['initial' => 10]);
Route::usephp('/hello', 'Workbench\App\Components\Hello', ['name' => 'route']);

// Sandbox-only: serve usephp.js straight from the vendor package
// (a real app runs `artisan vendor:publish --tag=usephp-assets`).
Route::get('/vendor/usephp/usephp.js', function () {
    $file = dirname((new ReflectionClass(UsePHP::class))->getFileName(), 2) . '/public/usephp.js';

    return response()->file($file, ['Content-Type' => 'application/javascript']);
});

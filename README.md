# usephp-laravel

Laravel bridge for [polidog/use-php](https://github.com/polidog/usePHP): write
[PSX](https://github.com/polidog/usePHP/blob/main/docs/PSX.md) components
(JSX-style syntax in PHP) and render them from Laravel routes and Blade views.

Requires PHP 8.5 and Laravel 12.

[日本語](README.ja.md)

## Install

```bash
composer require kojirock5260/usephp-laravel
php artisan vendor:publish --tag=usephp-assets   # public/vendor/usephp/usephp.js
php artisan vendor:publish --tag=usephp-config   # optional: config/usephp.php
```

## Write a component

`app/Components/Counter.psx`:

```php
<?php

namespace App\Components;

use Polidog\UsePhp\Html\H;
use Polidog\UsePhp\Storage\StorageType;

use function Polidog\UsePhp\Runtime\fc;
use function Polidog\UsePhp\Runtime\useState;

return fc(function (array $props) {
    [$count, $setCount] = useState($props['initial'] ?? 0);

    return (
        <div className="counter">
            <span>Count: {$count}</span>
            <button onClick={fn () => $setCount($count + 1)}>+</button>
        </div>
    );
}, 'counter', StorageType::Snapshot);
```

The file name is the component name; together with the `namespace` at the
top it is referenced as `App\Components\Counter`.

## Render it

From a route:

```php
Route::usephp('/counter', 'App\Components\Counter', ['initial' => 0]);
```

From Blade:

```blade
@usephp('App\Components\Counter', ['initial' => 0])
@usephpScript
```

Or anywhere:

```php
use Kojirock5260\UsePhpLaravel\Facades\UsePhp;

$html = UsePhp::render('App\Components\Counter', ['initial' => 0]);
```

## Form actions (partial updates)

`onClick={fn () => $setCount($count + 1)}` renders as a form that posts back
to the page. `Route::usephp()` registers that POST for you; for a component
embedded in a Blade page, add the POST yourself:

```php
Route::get('/', fn () => view('dashboard'));
Route::usephpAction('/', 'App\Components\Counter', ['initial' => 0]);
```

The handler verifies the signed snapshot, applies the action, and returns the
re-rendered fragment (with JavaScript) or the whole component (without).
Laravel's own CSRF middleware stays on: the package injects `_token` into
every usePHP form.

## Deferred components

Wrap a component with `fc(..., defer: new Defer(name: 'time'))` and use it
with a fallback; the page ships the fallback and `usephp.js` fetches the real
fragment from `GET /_defer/time?...` after load. The package registers that
route (under the `web` middleware group, so the session is available) and
copies usePHP's `Cache-Control` onto the Laravel response.

## Compile

In `local` the components are compiled on demand. Everywhere else, compile
at build time:

```bash
php artisan usephp:compile          # writes storage/framework/psx
php artisan usephp:compile --check  # CI: fail if the cache is stale
```

Cache file names derive from each `.psx` file's absolute path, so build and
runtime must see the project at the same path.

## Scope

- Stateless components, Snapshot-storage `useState` components, form actions
  and deferred components work today.
- Props are for the first render only; a partial update re-renders with the
  props given to the route, so keep per-request data in state.
- Session-storage state is not supported yet; it needs the session abstraction
  planned upstream.

Relayer-specific features (file routing, layouts, server actions, HTTP cache,
islands) are out of scope: Laravel already has its own.

## Editor support

There is no PSX language server or JetBrains plugin yet, so completion and
type hints are not available. Syntax highlighting is: usePHP ships a TextMate
grammar that PhpStorm can load directly.

```
Settings → Editor → TextMate Bundles → + → vendor/polidog/use-php/editors/vscode
```

For VS Code and Neovim, see
[usePHP's editors/ directory](https://github.com/polidog/usePHP/tree/main/editors).

## Sandbox

A Testbench workbench with sample components lives in `workbench/`.

```bash
vendor/bin/testbench serve        # http://127.0.0.1:8000
```

Or in Docker (PHP 8.5 included):

```bash
docker compose up --build         # http://localhost:8000
```

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>usephp-laravel sandbox</title>
    <style>
        body { font: 16px/1.5 system-ui, sans-serif; max-width: 40rem; margin: 3rem auto; padding: 0 1rem; }
        .counter button { font-size: 1.25rem; padding: .25rem .75rem; margin-left: .5rem; }
        .count { font-weight: 600; }
        code { background: #f3f3f3; padding: .1em .3em; }
        section { margin-top: 2rem; }
    </style>
</head>
<body>
    <h1>usephp-laravel sandbox</h1>
    <p>PSX components rendered inside a Blade view.</p>

    <section>
        <h2>@@usephp directive</h2>
        @usephp('Workbench\App\Components\Counter', ['initial' => 5])
        <p><small>Buttons post back to this URL; usephp.js swaps in the fragment the action handler returns.</small></p>
    </section>

    @usephp('Workbench\App\Components\Greeting', ['name' => 'Blade'])

    <section>
        <h2>Deferred component</h2>
        @usephp('Workbench\App\Components\DeferDemo')
        <p><small>The page ships a fallback; usephp.js fetches <code>/_defer/slow-greeting?name=deferred</code> after load.</small></p>
    </section>

    <section>
        <h2>Route macro</h2>
        <ul>
            <li><a href="/counter">/counter</a> — <code>Route::usephp('/counter', Counter::class, ['initial' => 10])</code></li>
            <li><a href="/hello">/hello</a> — <code>Route::usephp('/hello', Hello::class, ['name' => 'route'])</code></li>
        </ul>
    </section>

    @usephpScript
</body>
</html>

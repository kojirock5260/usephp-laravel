# usephp-laravel

[polidog/use-php](https://github.com/polidog/usePHP) を Laravel で使うためのブリッジです。
[PSX](https://github.com/polidog/usePHP/blob/main/docs/PSX.md)（PHP に JSX を書く構文）でコンポーネントを書き、
Laravel のルートや Blade から描画できます。

PHP 8.5 以上、Laravel 12 が必要です。

[English](README.md)

## インストール

```bash
composer require kojirock5260/usephp-laravel
php artisan vendor:publish --tag=usephp-assets   # public/vendor/usephp/usephp.js を配置
php artisan vendor:publish --tag=usephp-config   # 任意: config/usephp.php を配置
```

## コンポーネントを書く

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

ファイル名がコンポーネント名、ファイル先頭の `namespace` と合わせて
`App\Components\Counter` として参照します。

## 描画する

ルートから:

```php
Route::usephp('/counter', 'App\Components\Counter', ['initial' => 0]);
```

Blade から:

```blade
@usephp('App\Components\Counter', ['initial' => 0])
@usephpScript
```

どこからでも:

```php
use Kojirock5260\UsePhpLaravel\Facades\UsePhp;

$html = UsePhp::render('App\Components\Counter', ['initial' => 0]);
```

`UsePhp::render()` は HTML 文字列を返すだけなので、コントローラから
そのまま返しても、`response()` で包んでも構いません。

## フォームアクション（部分更新）

`onClick={fn () => $setCount($count + 1)}` は、同じ URL に POST するフォームとして
描画されます。`Route::usephp()` はその POST も一緒に登録します。Blade に埋め込んだ
コンポーネントの場合は、ページの URL に POST を自分で足します。

```php
Route::get('/', fn () => view('dashboard'));
Route::usephpAction('/', 'App\Components\Counter', ['initial' => 0]);
```

ハンドラは署名付きの snapshot を検証し、アクションを適用し、描き直した断片を返します
（JavaScript が無い場合はコンポーネント全体を返します）。
Laravel の CSRF ミドルウェアはそのまま有効です。usePHP が生成する各フォームに
パッケージが `_token` を差し込みます。

## 遅延描画（Defer）

`fc(..., defer: new Defer(name: 'time'))` で包んだコンポーネントを fallback 付きで
使うと、ページにはまず fallback が入り、読み込み後に `usephp.js` が
`GET /_defer/time?...` から本物の断片を取りに行きます。パッケージはそのルートを
`web` ミドルウェアグループで登録し（セッションが使えます）、usePHP が出す
`Cache-Control` を Laravel のレスポンスに写します。

## コンパイル

`local` 環境ではリクエスト時に必要な分だけ自動でコンパイルされます。
それ以外の環境ではビルド時にコンパイルしてください。

```bash
php artisan usephp:compile          # storage/framework/psx に書き出す
php artisan usephp:compile --check  # CI 用: キャッシュが古ければ失敗する
```

キャッシュのファイル名は `.psx` の絶対パスから決まるため、ビルド時と実行時で
プロジェクトのパスを揃えてください。

## 対応範囲

- state を持たないコンポーネント、Snapshot 型の `useState`、フォームアクション、
  遅延描画が動きます。
- props は初回描画用です。部分更新ではルートに渡した props で描き直すので、
  リクエストごとに変わるデータは state に持たせてください。
- `StorageType::Session` はまだ使えません。usePHP にセッションを差し替える口
  （SessionInterface）が無く、Laravel のセッションに state を載せられないためです。
  上流で追加される予定なので、それを待っています。
- CSRF 対応は暫定です。usePHP にはフォームへ hidden を足すフックが無いため、
  描画後の HTML に文字列置換で `_token` を差し込んでいます（`CsrfTokenInjector`）。
  上流にフックが入ったら置き換えます。

Relayer 固有の機能（ファイルベースルーティング、レイアウト、サーバーアクション、
HTTP キャッシュ、アイランド）は対象外です。Laravel 側に同等のものがあります。

## エディタ対応

PSX の言語サーバーや JetBrains プラグインはまだ無いため、補完や型ヒントは効きません。
シンタックスハイライトだけは入れられます。usePHP に同梱の TextMate 文法を
PhpStorm がそのまま読めます。

```
Settings → Editor → TextMate Bundles → 「+」→ vendor/polidog/use-php/editors/vscode
```

VS Code と Neovim は
[usePHP の editors/ ディレクトリ](https://github.com/polidog/usePHP/tree/main/editors)
を参照してください。

## サンドボックス

サンプルコンポーネント入りの Testbench workbench が `workbench/` にあります。

```bash
vendor/bin/testbench serve        # http://127.0.0.1:8000
```

Docker で動かす場合（PHP 8.5 同梱）:

```bash
docker compose up --build         # http://localhost:8000
```

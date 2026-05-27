# アーキテクチャ

## 概要

PHP Array JSON Converter は、PHP配列リテラルとJSONを相互変換するローカルWebツールです。

画面は静的HTMLとして提供し、PHPはJSON APIだけを担当します。ユーザー入力のPHPコードは実行せず、`token_get_all()` で字句解析したうえで、許可した配列リテラル構文だけを独自パーサーで読み取ります。

## 全体構成

```text
public/index.html
  UI structure

public/assets/style.css
  UI styling

public/assets/app.js
  fetch / focus / copy

public/index.php
  PHP built-in server / FrankenPHP 共通の入口
  /api/* だけ PHP アプリケーションへ渡す

Caddyfile
  FrankenPHP 用のルーティング
  /api/* は PHP へ渡し、それ以外は静的ファイルとして配信する

src/Application.php
  HTTP method + path の dispatch

src/Controller/ConvertController.php
  JSON request を読む
  mode を見て Converter を呼ぶ
  ConversionResult を JSON response に変換する

src/Converter.php
  arrayToJson()
  jsonToArray()
  HTTP を知らない変換ユースケース

src/ConversionResult.php
  phpArray / json / error を持つ変換結果

src/ArrayLiteralParser.php
src/ArrayLiteralFormatter.php
src/JsonFormatter.php
  変換ロジックの低レベル部品

src/Http/JsonResponseFactory.php
  JSON response の生成
```

## リクエストの流れ

### 画面表示

```text
GET /
  -> public/index.html
```

`public/index.html` は静的ファイルです。CSSは `public/assets/style.css`、JavaScriptは `public/assets/app.js` に分けています。PHPはHTMLを生成しません。

### 変換API

```text
POST /api/convert
Content-Type: application/json
```

`public/assets/app.js` が `/api/convert` にJSONを送ります。

PHP側の流れ:

```text
public/index.php
  -> Application::handle()
  -> ConvertController::convert()
  -> Converter::arrayToJson() または Converter::jsonToArray()
  -> JsonResponseFactory::create()
```

## API

### PHP配列リテラルからJSON

Request:

```json
{
  "mode": "array_to_json",
  "php_array": "['name' => 'shimabox']"
}
```

Response:

```json
{
  "php_array": "['name' => 'shimabox']",
  "json": "{\n    \"name\": \"shimabox\"\n}",
  "error": null
}
```

### JSONからPHP配列リテラル

Request:

```json
{
  "mode": "json_to_array",
  "json": "{\"name\":\"shimabox\"}"
}
```

Response:

```json
{
  "php_array": "[\n    'name' => 'shimabox',\n]",
  "json": "{\"name\":\"shimabox\"}",
  "error": null
}
```

## HTTPステータス

```text
200  変換成功
400  リクエストJSON不正、または未対応mode
404  未定義APIパス
422  入力値の変換エラー
```

変換エラーはHTTPとしては処理できたリクエストなので、`422` を返します。

## 責務

### `public/index.html`

- 画面表示
- 初期サンプル入力の保持

### `public/assets/style.css`

- 画面スタイル
- レスポンシブレイアウト
- エラー表示とcopyボタンの状態表示

### `public/assets/app.js`

- `/api/convert` の呼び出し
- 変換結果の反映
- エラー表示
- 変換後のフォーカス制御
- copyボタン

### `public/index.php`

- PHP built-in server / FrankenPHP 共通の入口
- `/api/*` 以外は静的ファイル配信へ戻す
- `Application` を呼び出し、HTTP responseを出力する
- HTMLは生成しない

### `Caddyfile`

- FrankenPHP 用のルーティング
- `/api/*` は `public/index.php` にrewriteしてPHPに渡す
- それ以外は `public/index.html` と `public/assets/*` を静的ファイルとして配信する

### `Application`

- HTTP method と path の dispatch
- 現在は `POST /api/convert` のみを `ConvertController` に渡す
- 未定義APIにはJSONで `404` を返す

ルーティングライブラリはまだ使いません。APIが増えて、ルート定義を一覧化したくなった時点で `nikic/fast-route` などを検討します。

### `ConvertController`

- JSON request body のdecode
- `mode` の判定
- `Converter` の呼び出し
- `ConversionResult` をAPIレスポンスへ変換

HTTP境界の責務を持ち、変換ロジック本体は持ちません。

### `Converter`

- `arrayToJson()`
- `jsonToArray()`

HTTPを知らず、変換結果を `ConversionResult` として返します。

### `ConversionResult`

- `phpArray`
- `json`
- `error`

`succeeded()` で成功/失敗を判定します。

### `ArrayLiteralParser`

PHP配列リテラル文字列をPHPの値へ変換します。

ユーザー入力は実行しません。`token_get_all()` で字句解析し、許可した構文だけを読み取ります。

入力全体は短縮配列リテラルである必要があります。

```php
[
    'name' => 'shimabox',
]
```

`<?php` 開始タグと末尾のセミコロンも許可します。

```php
<?php

[
    'name' => 'shimabox',
];
```

対応している値:

- string
- int
- float
- bool
- null
- ネストした配列
- リスト配列
- 連想配列
- trailing comma

文字列はシングルクォートと、変数展開を含まないダブルクォートに対応しています。ダブルクォート文字列で対応しているエスケープは `\n`, `\r`, `\t`, `\v`, `\e`, `\f`, `\\`, `\"`, `\$` です。

対応しているキー:

- string key
- int key

対応していないPHP構文:

- `array()` 構文
- 関数呼び出し
- 変数
- 定数
- クラス定数
- enum
- `new`
- 文字列連結
- 算術式
- spread演算子
- heredoc / nowdoc
- 変数展開を含むダブルクォート文字列

対応していない構文が含まれている場合は、PHPコードとして実行せずエラーにします。

### `ArrayLiteralFormatter`

PHPの値をPHP短縮配列リテラル文字列へ整形します。

### `JsonFormatter`

PHPの値を整形済みJSON文字列へ変換します。

## 単体バイナリ配布

Web UIを維持した単体配布は、FrankenPHPのstatic binaryで検証します。

詳細は [docs/static-binary.md](static-binary.md) を参照してください。

## StaticPHP への考慮

- Node系ツールチェーンは導入していません
- Composer依存は開発ツール中心です
- viewは `public/index.html`、`public/assets/style.css`、`public/assets/app.js` として静的に分離しています
- PHP APIはフレームワークなしで小さく保っています

StaticPHP / FrankenPHP で単体バイナリ化する場合も、静的ファイルとPHP APIの境界が分かれているため、組み込み対象を整理しやすい構成です。

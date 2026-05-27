# PHP Array JSON Converter

PHP配列リテラルとJSONを相互変換するローカルWebツールです。

Repository / package name: `php-array-json-converter`

静的なHTMLビューからPHPのJSON APIを呼び出して、以下の変換を行います。

- PHP配列リテラルからJSON
- JSONからPHP短縮配列リテラル

ユーザー入力のPHPコードは実行しません。`eval()`、`include`、`require` は使わず、`token_get_all()` で字句解析したうえで、対応している配列リテラル構文だけをパースします。

PHP側は変換APIだけを担当し、画面は `public/index.html` の静的ファイルとして分けています。

設計の詳細は [docs/architecture.md](docs/architecture.md) にまとめています。

## セットアップ

依存関係をインストールします。

```bash
docker compose run --rm app composer install
```

## 起動

Dockerで起動します。

```bash
docker compose up app
```

ブラウザで開きます。

```text
http://localhost:8080
```

`public/index.html` は静的ファイルとして配信され、`/api/*` だけ `public/router.php` からPHPアプリケーションへ渡します。

## 開発コマンド

テストを実行します。

```bash
docker compose run --rm app composer test
```

PHPStanを実行します。

```bash
docker compose run --rm app composer analyse
```

フォーマットを確認します。

```bash
docker compose run --rm app composer format-check
```

フォーマットを適用します。

```bash
docker compose run --rm app composer format
```

CI相当のチェックをまとめて実行します。

```bash
docker compose run --rm app composer ci
```

`composer ci` は以下を順に実行します。

```text
format-check
analyse
test
```

GitHub Actionsでも、pushとpull requestに対して `composer validate --strict` と `composer ci` を実行します。

## API

変換API:

```text
POST /api/convert
Content-Type: application/json
```

PHP配列リテラルからJSON:

```json
{
    "mode": "array_to_json",
    "php_array": "['name' => 'shimabox']"
}
```

成功レスポンス:

```json
{
    "php_array": "['name' => 'shimabox']",
    "json": "{\n    \"name\": \"shimabox\"\n}",
    "error": null
}
```

JSONからPHP配列リテラル:

```json
{
    "mode": "json_to_array",
    "json": "{\"name\":\"shimabox\"}"
}
```

成功レスポンス:

```json
{
    "php_array": "[\n    'name' => 'shimabox',\n]",
    "json": "{\"name\":\"shimabox\"}",
    "error": null
}
```

成功時は `200`、変換エラー時は `422`、リクエスト形式エラー時は `400` を返します。

## 構成

```text
public/index.html
  UI / fetch / focus / copy

public/router.php
  PHP built-in server 用の入口
  /api/* だけ PHP アプリケーションへ渡す

src/Application.php
  HTTP method + path の dispatch

src/Controller/ConvertController.php
  JSON request を読み、Converter を呼び、JSON response を返す

src/Converter.php
  HTTP を知らない変換ユースケース

src/ArrayLiteralParser.php
src/ArrayLiteralFormatter.php
src/JsonFormatter.php
  変換ロジックの低レベル部品
```

## 変換例

PHP配列リテラル:

```php
[
    'name' => 'shimabox',
    'age' => 40,
    'active' => true,
    'skills' => ['PHP', 'Go'],
]
```

JSON:

```json
{
    "name": "shimabox",
    "age": 40,
    "active": true,
    "skills": [
        "PHP",
        "Go"
    ]
}
```

## 対応しているPHP配列リテラル

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

## 対応していないPHP構文

以下は初期版では対応しません。

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

## 設計方針

アプリケーションは小さく保ちます。

- フレームワークは使わない
- 外部APIは使わない
- PHPは変換APIに集中させ、viewは静的HTMLとして分ける
- ルーティングライブラリは、APIが増えるまで導入しない
- DBは使わない
- `.env` は使わない
- ユーザー入力をPHPコードとして実行しない

StaticPHP / FrankenPHPで単体バイナリ化することを見据えていますが、まずは通常のPHP Webアプリとして動く状態を優先します。

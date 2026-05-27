# PHP Array JSON Converter

PHP配列リテラルとJSONを相互変換するローカルWebツールです。

Repository / package name: `php-array-json-converter`

設計の詳細は [docs/architecture.md](docs/architecture.md) を参照してください。

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

# PHP Array JSON Converter

PHP配列リテラルとJSONを相互変換するローカルWebツールです。

Repository / package name: `php-array-json-converter`

設計の詳細は [docs/architecture.md](docs/architecture.md) を参照してください。
単体バイナリ配布については [docs/static-binary.md](docs/static-binary.md) を参照してください。

## セットアップ

依存関係をインストールします。

```bash
docker compose run --rm app composer install
```

## Dockerで起動

Dockerで起動します。

```bash
docker compose up app
```

ブラウザで開きます。

```text
http://localhost:8080
```

## 単体バイナリで起動

GitHub Actionsの `Static Binary` workflowで生成したartifactを使います。

macOS arm64:

```bash
xattr -d com.apple.quarantine php-array-json-converter-macos-arm64
chmod +x php-array-json-converter-macos-arm64
./php-array-json-converter-macos-arm64 php-server
```

Linux x86_64:

```bash
chmod +x php-array-json-converter-linux-x86_64
./php-array-json-converter-linux-x86_64 php-server
```

macOS上でLinux artifactだけ確認したい場合は、DockerでLinuxコンテナ内から起動します。

```bash
chmod +x php-array-json-converter-linux-x86_64
docker run --rm --platform linux/amd64 -p 8080:8080 -v "$PWD":/work -w /work ubuntu:24.04 ./php-array-json-converter-linux-x86_64 php-server --listen :8080
```

Windows native binaryはまだ生成していません。Windowsで確認する場合は、WSL2上でLinux x86_64 artifactを使います。

`8080` が使われている場合は、別ポートで起動します。

```bash
./php-array-json-converter-macos-arm64 php-server --listen :8081
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

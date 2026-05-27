# 単体バイナリ配布

## 方針

Web UIを維持したまま単体配布するため、FrankenPHPのstatic binaryを使います。

FrankenPHPのstatic binaryは、PHPランタイム、Caddy、アプリケーションのソースと静的アセットを1つの実行ファイルに埋め込みます。StaticPHPを直接使ってPHP CLIだけを配るより、このアプリのようにWeb UIを含むツールには向いています。

## 構成

```text
Caddyfile
  FrankenPHP用のルーティング。static binaryに埋め込む
  /api/* は public/index.php に渡す
  それ以外は public/index.html と assets を静的配信する

public/index.php
  PHP built-in server / FrankenPHP共通の入口
  /api/* だけPHPアプリケーションに渡す

static-build.Dockerfile
  Linux向けstatic binaryのbuild定義
```

## 通常の動作確認

開発中はこれまで通りDocker Composeで確認します。

```bash
docker compose up app
```

```text
http://localhost:8080
```

## FrankenPHPでの動作確認

FrankenPHPコンテナで、static binary化する前のCaddyfileを確認できます。

```bash
docker run --rm -p 8080:8080 -v "$PWD":/app -w /app dunglas/frankenphp frankenphp run --config Caddyfile
```

これは開発中のCaddyfile確認用です。単体バイナリ化後の起動は `php-server` を使います。

## Linux向けstatic binaryのbuild

```bash
docker build -t php-array-json-converter-static -f static-build.Dockerfile .
```

build後、生成されたバイナリを取り出します。

```bash
docker cp "$(docker create --name php-array-json-converter-static-tmp php-array-json-converter-static):/go/src/app/dist/frankenphp-linux-x86_64" php-array-json-converter
docker rm php-array-json-converter-static-tmp
chmod +x php-array-json-converter
```

起動します。

```bash
./php-array-json-converter php-server
```

`php-server` は、埋め込まれたアプリケーションと `Caddyfile` を使ってWeb UIを起動します。

## 注意点

- `vendor/` はバイナリに含める必要があります。`static-build.Dockerfile` ではbuild用stageでproduction依存をインストールしてから埋め込みます。
- static binaryではPHP拡張をあとから動的ロードできません。現時点ではPHP拡張として `tokenizer` をbuild時に含めます。`json` はPHP 8では組み込みのため、拡張指定からは外しています。
- 現時点のアプリはComposer依存が開発ツールのみなので、runtimeの依存は最小です。

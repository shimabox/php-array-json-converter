import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';

const repoRoot = fileURLToPath(new URL('..', import.meta.url));
const webRoot = fileURLToPath(new URL('.', import.meta.url));

// php-bridge.js で読み込む PHP のバージョンと合わせる。
const PHP_PACKAGE_VERSION = '8-5';

const UNUSED_RUNTIME = '\0php-wasm-unused-runtime';
const UNUSED_ASSET = '\0php-wasm-unused-asset';

// php-wasm は import('./php.wasm') でファイルの URL を受け取る前提なので、
// .wasm は ?url として解決する。
// 使わない PHP バージョンと intl 拡張（ICU データ）は同梱しない。
// ICU データは 1 ファイル 25MiB という Cloudflare の上限を超えるため。
const phpWasmAssets = {
    name: 'php-wasm-assets',
    enforce: 'pre',
    async resolveId(source, importer) {
        const runtime = source.match(/^@php-wasm\/web-(\d+-\d+)$/);

        if (runtime && runtime[1] !== PHP_PACKAGE_VERSION) {
            return UNUSED_RUNTIME;
        }

        if (/\.(dat|so)(\?url)?$/.test(source)) {
            return UNUSED_ASSET;
        }

        if (/\.wasm$/.test(source)) {
            const resolved = await this.resolve(source, importer, { skipSelf: true });

            return resolved ? `${resolved.id}?url` : null;
        }

        return null;
    },
    load(id) {
        if (id === UNUSED_RUNTIME) {
            return [
                'const unsupported = () => { throw new Error("This PHP version is not bundled."); };',
                'export const getPHPLoaderModule = unsupported;',
                'export const getIntlExtensionPath = unsupported;',
            ].join('\n');
        }

        if (id === UNUSED_ASSET) {
            return 'export default "data:,";';
        }

        return null;
    },
};

// バイナリ版と同じ public/index.html に、ブラウザ内 PHP の起動スクリプトを足す。
const injectPhpBridge = {
    name: 'inject-php-bridge',
    transformIndexHtml: {
        order: 'pre',
        handler(html, ctx) {
            // 開発サーバーはルート（public/）の外のファイルを /@fs/ の絶対パスで配信する。
            const src = ctx.server
                ? `/@fs${webRoot}src/php-bridge.js`
                : '../web/src/php-bridge.js';

            return html.replace(
                '</head>',
                `    <script type="module" src="${src}"></script>\n</head>`,
            );
        },
    },
};

export default defineConfig({
    root: repoRoot + 'public',
    publicDir: webRoot + 'static',
    // 既定ではルート（public/）の下にキャッシュができるので、web/ の下へ移す。
    cacheDir: webRoot + 'node_modules/.vite',
    plugins: [phpWasmAssets, injectPhpBridge],
    optimizeDeps: {
        // .wasm の import を php-wasm-assets で解決するため、事前バンドルから外す。
        exclude: ['@php-wasm/web'],
    },
    server: {
        fs: { allow: [repoRoot] },
    },
    build: {
        outDir: webRoot + 'dist',
        emptyOutDir: true,
        target: 'es2022',
    },
});

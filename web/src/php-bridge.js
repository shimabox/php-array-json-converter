// ブラウザ内の WebAssembly 版 PHP で public/index.php を実行し、
// /api/convert への fetch をサーバーではなくそこへ渡す。
// 変換ロジックはバイナリ版と同じ src/ の PHP コードをそのまま使う。
import { PHP } from '@php-wasm/universal';
import { loadWebRuntime } from '@php-wasm/web';

const PHP_VERSION = '8.5';
const API_PATH = '/api/convert';
const APP_ROOT = '/app';

const phpSources = import.meta.glob(['../../src/**/*.php', '../../public/index.php'], {
    query: '?raw',
    import: 'default',
    eager: true,
});

// Composer の autoload の代わりに、src/ だけを読む PSR-4 オートローダーを置く。
const autoload = `<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'PhpArrayJsonConverter\\\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = '${APP_ROOT}/src/' . str_replace('\\\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($path)) {
        require $path;
    }
});
`;

function writeFile(php, path, contents) {
    php.mkdir(path.slice(0, path.lastIndexOf('/')));
    php.writeFile(path, contents);
}

async function bootPhp() {
    const php = new PHP(await loadWebRuntime(PHP_VERSION));

    for (const [source, contents] of Object.entries(phpSources)) {
        writeFile(php, `${APP_ROOT}/${source.replace(/^(\.\.\/)+/, '')}`, contents);
    }

    writeFile(php, `${APP_ROOT}/vendor/autoload.php`, autoload);

    return php;
}

function setConvertButtonsDisabled(disabled) {
    document.querySelectorAll('#array-to-json, #json-to-array').forEach((button) => {
        button.disabled = disabled;
    });
}

// PHP の読み込み中は変換ボタンを押せないようにする。
setConvertButtonsDisabled(true);

const phpReady = bootPhp();

phpReady.then(
    () => setConvertButtonsDisabled(false),
    (error) => {
        console.error('Failed to start PHP runtime.', error);
        setConvertButtonsDisabled(false);
    },
);

const originalFetch = window.fetch.bind(window);

window.fetch = async (input, init = {}) => {
    const url = new URL(input instanceof Request ? input.url : String(input), location.href);

    if (url.origin !== location.origin || url.pathname !== API_PATH) {
        return originalFetch(input, init);
    }

    const php = await phpReady;
    const body = init.body ?? '';
    const response = await php.run({
        scriptPath: `${APP_ROOT}/public/index.php`,
        relativeUri: url.pathname + url.search,
        method: init.method ?? 'GET',
        headers: Object.fromEntries(new Headers(init.headers)),
        body: typeof body === 'string' ? new TextEncoder().encode(body) : body,
    });

    return new Response(response.bytes, {
        status: response.httpStatusCode,
        headers: response.headers,
    });
};

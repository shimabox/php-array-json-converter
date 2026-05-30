<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    /**
     * `POST /api/convert` が変換APIへdispatchされることを確認します。
     * Application層がroutingだけを担当し、正常なJSON responseを返せることをカバーします。
     */
    public function testDispatchesConvertApi(): void
    {
        $app = new Application();

        $response = $app->handle('POST', '/api/convert', json_encode([
            'mode' => 'array_to_json',
            'php_array' => "['name' => 'shimabox']",
        ], JSON_THROW_ON_ERROR));
        $payload = $this->decodeJson($response->body);

        self::assertSame(200, $response->statusCode);
        self::assertSame("['name' => 'shimabox']", $payload['php_array']);
        self::assertSame("{\n    \"name\": \"shimabox\"\n}", $payload['json']);
    }

    /**
     * 未定義のAPI pathがJSON形式の404として返ることを確認します。
     */
    public function testReturnsNotFoundForUnknownApiPath(): void
    {
        $app = new Application();

        $response = $app->handle('GET', '/api/missing');
        $payload = $this->decodeJson($response->body);

        self::assertSame(404, $response->statusCode);
        self::assertSame('application/json; charset=utf-8', $response->headers['Content-Type']);
        self::assertSame('Not Found', $payload['error']);
    }

    /**
     * 定義済みpathでもHTTP methodが一致しなければ404にすることを確認します。
     */
    public function testReturnsNotFoundWhenConvertApiMethodDoesNotMatch(): void
    {
        $app = new Application();

        $response = $app->handle('GET', '/api/convert');
        $payload = $this->decodeJson($response->body);

        self::assertSame(404, $response->statusCode);
        self::assertSame('Not Found', $payload['error']);
    }

    /**
     * request bodyが上限を超えた場合に413を返すことを確認します。
     * 大きすぎる入力による処理時間やメモリ使用量の増加を入口で止めるための境界テストです。
     */
    public function testReturnsPayloadTooLargeWhenBodyExceedsLimit(): void
    {
        $app = new Application();

        $response = $app->handle('POST', '/api/convert', str_repeat('a', Application::MAX_BODY_BYTES + 1));
        $payload = $this->decodeJson($response->body);

        self::assertSame(413, $response->statusCode);
        self::assertSame('Payload Too Large', $payload['error']);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        /** @var array<string, mixed> $payload */
        return $payload;
    }
}

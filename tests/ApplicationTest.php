<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
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

    public function testReturnsNotFoundForUnknownApiPath(): void
    {
        $app = new Application();

        $response = $app->handle('GET', '/api/missing');
        $payload = $this->decodeJson($response->body);

        self::assertSame(404, $response->statusCode);
        self::assertSame('application/json; charset=utf-8', $response->headers['Content-Type']);
        self::assertSame('Not Found', $payload['error']);
    }

    public function testReturnsNotFoundWhenConvertApiMethodDoesNotMatch(): void
    {
        $app = new Application();

        $response = $app->handle('GET', '/api/convert');
        $payload = $this->decodeJson($response->body);

        self::assertSame(404, $response->statusCode);
        self::assertSame('Not Found', $payload['error']);
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

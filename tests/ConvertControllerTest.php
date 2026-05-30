<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\Controller\ConvertController;
use PHPUnit\Framework\TestCase;

final class ConvertControllerTest extends TestCase
{
    /**
     * `json_to_array` modeのHTTP境界で正常レスポンスを返すことを確認します。
     * Controllerがrequest JSONを読み、Converterの結果をAPI payloadに写す契約をカバーします。
     */
    public function testConvertsJsonToArrayLiteral(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert(json_encode([
            'mode' => 'json_to_array',
            'json' => '{"name":"shimabox","skills":["PHP","Go"]}',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->decodeJson($response->body);

        $expected = <<<'PHP'
[
    'name' => 'shimabox',
    'skills' => [
        'PHP',
        'Go',
    ],
]
PHP;

        self::assertSame(200, $response->statusCode);
        self::assertSame($expected, $payload['php_array']);
        self::assertSame('{"name":"shimabox","skills":["PHP","Go"]}', $payload['json']);
        self::assertNull($payload['error']);
    }

    /**
     * JSON変換エラーをHTTP 422として返すことを確認します。
     * request自体は処理できているため400ではなく422にする仕様を固定します。
     */
    public function testShowsJsonParseError(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert(json_encode([
            'mode' => 'json_to_array',
            'json' => '{"name":',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->decodeJson($response->body);

        self::assertSame(422, $response->statusCode);
        self::assertSame('', $payload['php_array']);
        self::assertSame('{"name":', $payload['json']);
        self::assertIsString($payload['error']);
        self::assertStringContainsString('JSON parse error:', $payload['error']);
    }

    /**
     * `array_to_json` modeのHTTP境界で正常レスポンスを返すことを確認します。
     */
    public function testConvertsArrayLiteralToJson(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert(json_encode([
            'mode' => 'array_to_json',
            'php_array' => "['name' => 'shimabox', 'skills' => ['PHP', 'Go']]",
        ], JSON_THROW_ON_ERROR));
        $payload = $this->decodeJson($response->body);

        $expected = <<<'JSON'
{
    "name": "shimabox",
    "skills": [
        "PHP",
        "Go"
    ]
}
JSON;

        self::assertSame(200, $response->statusCode);
        self::assertSame("['name' => 'shimabox', 'skills' => ['PHP', 'Go']]", $payload['php_array']);
        self::assertSame($expected, $payload['json']);
        self::assertNull($payload['error']);
    }

    /**
     * PHP配列リテラルの変換エラーをHTTP 422として返すことを確認します。
     */
    public function testShowsArrayParseError(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert(json_encode([
            'mode' => 'array_to_json',
            'php_array' => "['name' => getenv('USER')]",
        ], JSON_THROW_ON_ERROR));
        $payload = $this->decodeJson($response->body);

        self::assertSame(422, $response->statusCode);
        self::assertSame("['name' => getenv('USER')]", $payload['php_array']);
        self::assertSame('', $payload['json']);
        self::assertIsString($payload['error']);
        self::assertStringContainsString('Unsupported PHP syntax', $payload['error']);
    }

    /**
     * API request body自体が不正なJSONの場合にHTTP 400を返すことを確認します。
     */
    public function testRejectsInvalidJsonRequest(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert('{"mode":');
        $payload = $this->decodeJson($response->body);

        self::assertSame(400, $response->statusCode);
        self::assertIsString($payload['error']);
        self::assertStringContainsString('Invalid JSON request:', $payload['error']);
    }

    /**
     * request bodyがJSON arrayの場合にHTTP 400を返すことを確認します。
     * API requestはobjectであるという境界仕様を固定します。
     */
    public function testRejectsJsonArrayRequest(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert('[]');
        $payload = $this->decodeJson($response->body);

        self::assertSame(400, $response->statusCode);
        self::assertSame('Invalid JSON request: request body must be a JSON object.', $payload['error']);
    }

    /**
     * request bodyがJSON scalarの場合にHTTP 400を返すことを確認します。
     */
    public function testRejectsJsonScalarRequest(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert('"array_to_json"');
        $payload = $this->decodeJson($response->body);

        self::assertSame(400, $response->statusCode);
        self::assertSame('Invalid JSON request: request body must be a JSON object.', $payload['error']);
    }

    /**
     * 未知の変換modeをHTTP 400で拒否することを確認します。
     */
    public function testRejectsUnknownMode(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert(json_encode([
            'mode' => 'unknown',
        ], JSON_THROW_ON_ERROR));
        $payload = $this->decodeJson($response->body);

        self::assertSame(400, $response->statusCode);
        self::assertSame('Unknown conversion mode.', $payload['error']);
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

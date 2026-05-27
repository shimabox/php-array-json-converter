<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\Controller\ConvertController;
use PHPUnit\Framework\TestCase;

final class ConvertControllerTest extends TestCase
{
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

    public function testRejectsInvalidJsonRequest(): void
    {
        $controller = new ConvertController();

        $response = $controller->convert('{"mode":');
        $payload = $this->decodeJson($response->body);

        self::assertSame(400, $response->statusCode);
        self::assertIsString($payload['error']);
        self::assertStringContainsString('Invalid JSON request:', $payload['error']);
    }

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

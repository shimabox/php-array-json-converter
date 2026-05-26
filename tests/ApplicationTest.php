<?php

declare(strict_types=1);

namespace PhpArrayJson\Tests;

use PhpArrayJson\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    public function testHomeRendersConverterUi(): void
    {
        $app = new Application();

        $response = $app->handle('GET', '/');

        self::assertSame(200, $response->statusCode);
        self::assertStringContainsString('PHP Array JSON Converter', $response->body);
        self::assertStringContainsString('name="php_array"', $response->body);
        self::assertStringContainsString('name="json"', $response->body);
        self::assertStringContainsString('Array &rarr; JSON', $response->body);
        self::assertStringContainsString('JSON &rarr; Array', $response->body);
    }

    public function testConvertsJsonToArrayLiteral(): void
    {
        $app = new Application();

        $response = $app->handle('POST', '/convert', [
            'mode' => 'json_to_php',
            'json' => '{"name":"shimabox","skills":["PHP","Go"]}',
        ]);

        self::assertSame(200, $response->statusCode);
        $expected = <<<'PHP'
[
    'name' => 'shimabox',
    'skills' => [
        'PHP',
        'Go',
    ],
]
PHP;

        self::assertStringContainsString(
            htmlspecialchars($expected, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $response->body,
        );
    }

    public function testShowsJsonParseError(): void
    {
        $app = new Application();

        $response = $app->handle('POST', '/convert', [
            'mode' => 'json_to_php',
            'json' => '{"name":',
        ]);

        self::assertSame(200, $response->statusCode);
        self::assertStringContainsString('JSON parse error:', $response->body);
    }

    public function testConvertsArrayLiteralToJson(): void
    {
        $app = new Application();

        $response = $app->handle('POST', '/convert', [
            'mode' => 'php_to_json',
            'php_array' => "['name' => 'shimabox', 'skills' => ['PHP', 'Go']]",
        ]);

        self::assertSame(200, $response->statusCode);

        $expected = <<<'JSON'
{
    "name": "shimabox",
    "skills": [
        "PHP",
        "Go"
    ]
}
JSON;

        self::assertStringContainsString(
            htmlspecialchars($expected, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $response->body,
        );
    }
}

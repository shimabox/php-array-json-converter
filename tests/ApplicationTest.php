<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Tests;

use PhpArrayJsonConverter\Application;
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
        self::assertStringContainsString('formaction="/convert#focus-json"', $response->body);
        self::assertStringContainsString('formaction="/convert#focus-php-array"', $response->body);
        self::assertStringContainsString('class="app-shell"', $response->body);
        self::assertStringContainsString('class="converter-grid"', $response->body);
        self::assertStringContainsString('class="editor-panel"', $response->body);
        self::assertStringContainsString('data-copy-target="php-array-input"', $response->body);
        self::assertStringContainsString('data-copy-target="json-input"', $response->body);
        self::assertStringContainsString('id="php-array-input"', $response->body);
        self::assertStringContainsString('id="json-input"', $response->body);
        self::assertStringContainsString('php-array-json-converter', $response->body);
        self::assertStringContainsString('Chiba', $response->body);
        self::assertStringContainsString('quote: &quot; double, slash: \\', $response->body);
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
        self::assertStringNotContainsString('focus_target', $response->body);
        self::assertStringNotContainsString('data-focus-target', $response->body);
        self::assertStringNotContainsString('sessionStorage', $response->body);
        self::assertStringNotContainsString('autofocus', $response->body);
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
        self::assertStringContainsString('role="alert"', $response->body);
        self::assertLessThan(
            strpos($response->body, '<form'),
            strpos($response->body, 'role="alert"'),
        );
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
        self::assertStringNotContainsString('focus_target', $response->body);
        self::assertStringNotContainsString('data-focus-target', $response->body);
        self::assertStringNotContainsString('sessionStorage', $response->body);
        self::assertStringNotContainsString('autofocus', $response->body);
    }
}

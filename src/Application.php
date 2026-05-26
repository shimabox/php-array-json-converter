<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final class Application
{
    private const DEFAULT_PHP_ARRAY = <<<'PHP'
[
    'project' => 'php-array-json-converter',
    'version' => 1,
    'active' => true,
    'owner' => [
        'name' => 'shimabox',
        'location' => 'Chiba',
        'roles' => [
            'developer',
            'maintainer',
        ],
    ],
    'features' => [
        [
            'name' => 'array_to_json',
            'enabled' => true,
            'limits' => [
                'supports_nested_arrays' => true,
                'supports_eval' => false,
                'max_depth' => null,
            ],
        ],
        [
            'name' => 'json_to_array',
            'enabled' => true,
            'formats' => [
                'pretty',
                'short_array_syntax',
            ],
        ],
    ],
    'sample_values' => [
        'string' => 'hello',
        'integer' => 123,
        'float' => 12.34,
        'boolean' => false,
        'null_value' => null,
        'unicode' => 'こんにちは',
        'escaped' => 'quote: " double, slash: \\',
    ],
    'matrix' => [
        [
            1,
            2,
            3,
        ],
        [
            4,
            5,
            6,
        ],
        [
            7,
            8,
            9,
        ],
    ],
    'metadata' => [
        'created_at' => '2026-05-26T22:30:00+09:00',
        'tags' => [
            'php',
            'json',
            'converter',
            'local-tool',
        ],
    ],
]
PHP;

    public function __construct(
        private readonly HtmlRenderer $renderer = new HtmlRenderer(),
        private readonly ArrayLiteralFormatter $arrayLiteralFormatter = new ArrayLiteralFormatter(),
        private readonly ArrayLiteralParser $arrayLiteralParser = new ArrayLiteralParser(),
        private readonly JsonFormatter $jsonFormatter = new JsonFormatter(),
    ) {
    }

    /**
     * @param array<string, string> $post
     */
    public function handle(string $method, string $path, array $post = []): Response
    {
        if ($method === 'GET' && $path === '/') {
            return new Response(
                200,
                ['Content-Type' => 'text/html; charset=utf-8'],
                $this->renderer->render(self::DEFAULT_PHP_ARRAY),
            );
        }

        if ($method === 'POST' && $path === '/convert') {
            if (($post['mode'] ?? '') === 'json_to_php') {
                try {
                    $value = json_decode($post['json'] ?? '', true, flags: JSON_THROW_ON_ERROR);
                    $phpArray = $this->arrayLiteralFormatter->format($value);

                    return new Response(
                        200,
                        ['Content-Type' => 'text/html; charset=utf-8'],
                        $this->renderer->render($phpArray, $post['json'] ?? ''),
                    );
                } catch (\JsonException $error) {
                    return new Response(
                        200,
                        ['Content-Type' => 'text/html; charset=utf-8'],
                        $this->renderer->render('', $post['json'] ?? '', 'JSON parse error: ' . $error->getMessage()),
                    );
                } catch (ConversionError $error) {
                    return new Response(
                        200,
                        ['Content-Type' => 'text/html; charset=utf-8'],
                        $this->renderer->render('', $post['json'] ?? '', $error->getMessage()),
                    );
                }
            }

            if (($post['mode'] ?? '') === 'php_to_json') {
                try {
                    $value = $this->arrayLiteralParser->parse($post['php_array'] ?? '');
                    $json = $this->jsonFormatter->format($value);

                    return new Response(
                        200,
                        ['Content-Type' => 'text/html; charset=utf-8'],
                        $this->renderer->render($post['php_array'] ?? '', $json),
                    );
                } catch (ConversionError $error) {
                    return new Response(
                        200,
                        ['Content-Type' => 'text/html; charset=utf-8'],
                        $this->renderer->render($post['php_array'] ?? '', '', $error->getMessage()),
                    );
                }
            }
        }

        return new Response(
            404,
            ['Content-Type' => 'text/plain; charset=utf-8'],
            'Not Found',
        );
    }
}

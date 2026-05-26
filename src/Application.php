<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final class Application
{
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
                $this->renderer->render(),
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

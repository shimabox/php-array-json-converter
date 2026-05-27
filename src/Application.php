<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final class Application
{
    public function __construct(
        private readonly ArrayLiteralFormatter $arrayLiteralFormatter = new ArrayLiteralFormatter(),
        private readonly ArrayLiteralParser $arrayLiteralParser = new ArrayLiteralParser(),
        private readonly JsonFormatter $jsonFormatter = new JsonFormatter(),
    ) {
    }

    public function handle(string $method, string $path, string $body = ''): Response
    {
        if ($method === 'POST' && $path === '/api/convert') {
            return $this->convert($body);
        }

        return $this->jsonResponse(404, [
            'error' => 'Not Found',
        ]);
    }

    private function convert(string $body): Response
    {
        try {
            $request = $this->decodeJsonObject($body);
        } catch (ConversionError $error) {
            return $this->jsonResponse(400, [
                'error' => $error->getMessage(),
            ]);
        }

        return match ($this->stringValue($request['mode'] ?? null)) {
            'array_to_json' => $this->convertArrayToJson($this->stringValue($request['php_array'] ?? null)),
            'json_to_array' => $this->convertJsonToArray($this->stringValue($request['json'] ?? null)),
            default => $this->jsonResponse(400, [
                'error' => 'Unknown conversion mode.',
            ]),
        };
    }

    private function convertArrayToJson(string $phpArray): Response
    {
        try {
            $value = $this->arrayLiteralParser->parse($phpArray);
            $json = $this->jsonFormatter->format($value);

            return $this->jsonResponse(200, [
                'php_array' => $phpArray,
                'json' => $json,
                'error' => null,
            ]);
        } catch (ConversionError $error) {
            return $this->jsonResponse(422, [
                'php_array' => $phpArray,
                'json' => '',
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function convertJsonToArray(string $json): Response
    {
        try {
            $value = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $phpArray = $this->arrayLiteralFormatter->format($value);

            return $this->jsonResponse(200, [
                'php_array' => $phpArray,
                'json' => $json,
                'error' => null,
            ]);
        } catch (\JsonException $error) {
            return $this->jsonResponse(422, [
                'php_array' => '',
                'json' => $json,
                'error' => 'JSON parse error: ' . $error->getMessage(),
            ]);
        } catch (ConversionError $error) {
            return $this->jsonResponse(422, [
                'php_array' => '',
                'json' => $json,
                'error' => $error->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $body): array
    {
        try {
            $value = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new ConversionError('Invalid JSON request: ' . $error->getMessage(), previous: $error);
        }

        if (!is_array($value)) {
            throw new ConversionError('Invalid JSON request: request body must be a JSON object.');
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(int $statusCode, array $payload): Response
    {
        try {
            $body = json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $error) {
            throw new \RuntimeException('Failed to encode JSON response.', previous: $error);
        }

        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json; charset=utf-8'],
            $body . "\n",
        );
    }
}

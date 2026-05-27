<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Controller;

use PhpArrayJsonConverter\ArrayLiteralFormatter;
use PhpArrayJsonConverter\ArrayLiteralParser;
use PhpArrayJsonConverter\ConversionError;
use PhpArrayJsonConverter\Http\JsonResponseFactory;
use PhpArrayJsonConverter\JsonFormatter;
use PhpArrayJsonConverter\Response;

final class ConvertController
{
    public function __construct(
        private readonly JsonResponseFactory $responseFactory = new JsonResponseFactory(),
        private readonly ArrayLiteralFormatter $arrayLiteralFormatter = new ArrayLiteralFormatter(),
        private readonly ArrayLiteralParser $arrayLiteralParser = new ArrayLiteralParser(),
        private readonly JsonFormatter $jsonFormatter = new JsonFormatter(),
    ) {
    }

    public function convert(string $body): Response
    {
        try {
            $request = $this->decodeJsonObject($body);
        } catch (ConversionError $error) {
            return $this->responseFactory->create(400, [
                'error' => $error->getMessage(),
            ]);
        }

        return match ($this->stringValue($request['mode'] ?? null)) {
            'array_to_json' => $this->arrayToJson($this->stringValue($request['php_array'] ?? null)),
            'json_to_array' => $this->jsonToArray($this->stringValue($request['json'] ?? null)),
            default => $this->responseFactory->create(400, [
                'error' => 'Unknown conversion mode.',
            ]),
        };
    }

    private function arrayToJson(string $phpArray): Response
    {
        try {
            $value = $this->arrayLiteralParser->parse($phpArray);
            $json = $this->jsonFormatter->format($value);

            return $this->responseFactory->create(200, [
                'php_array' => $phpArray,
                'json' => $json,
                'error' => null,
            ]);
        } catch (ConversionError $error) {
            return $this->responseFactory->create(422, [
                'php_array' => $phpArray,
                'json' => '',
                'error' => $error->getMessage(),
            ]);
        }
    }

    private function jsonToArray(string $json): Response
    {
        try {
            $value = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
            $phpArray = $this->arrayLiteralFormatter->format($value);

            return $this->responseFactory->create(200, [
                'php_array' => $phpArray,
                'json' => $json,
                'error' => null,
            ]);
        } catch (\JsonException $error) {
            return $this->responseFactory->create(422, [
                'php_array' => '',
                'json' => $json,
                'error' => 'JSON parse error: ' . $error->getMessage(),
            ]);
        } catch (ConversionError $error) {
            return $this->responseFactory->create(422, [
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
}

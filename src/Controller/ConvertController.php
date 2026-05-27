<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Controller;

use PhpArrayJsonConverter\ConversionError;
use PhpArrayJsonConverter\ConversionResult;
use PhpArrayJsonConverter\Converter;
use PhpArrayJsonConverter\Http\JsonResponseFactory;
use PhpArrayJsonConverter\Response;

final class ConvertController
{
    public function __construct(
        private readonly JsonResponseFactory $responseFactory = new JsonResponseFactory(),
        private readonly Converter $converter = new Converter(),
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
            'array_to_json' => $this->conversionResponse(
                $this->converter->arrayToJson($this->stringValue($request['php_array'] ?? null)),
            ),
            'json_to_array' => $this->conversionResponse(
                $this->converter->jsonToArray($this->stringValue($request['json'] ?? null)),
            ),
            default => $this->responseFactory->create(400, [
                'error' => 'Unknown conversion mode.',
            ]),
        };
    }

    private function conversionResponse(ConversionResult $result): Response
    {
        return $this->responseFactory->create($result->succeeded() ? 200 : 422, [
            'php_array' => $result->phpArray,
            'json' => $result->json,
            'error' => $result->error,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $body): array
    {
        try {
            $value = json_decode($body, false, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new ConversionError('Invalid JSON request: ' . $error->getMessage(), previous: $error);
        }

        if (!$value instanceof \stdClass) {
            throw new ConversionError('Invalid JSON request: request body must be a JSON object.');
        }

        $request = get_object_vars($value);

        /** @var array<string, mixed> $request */
        return $request;
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}

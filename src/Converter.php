<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final class Converter
{
    private const int MAX_DEPTH = 128;

    public function __construct(
        private readonly ArrayLiteralFormatter $arrayLiteralFormatter = new ArrayLiteralFormatter(),
        private readonly ArrayLiteralParser $arrayLiteralParser = new ArrayLiteralParser(),
        private readonly JsonFormatter $jsonFormatter = new JsonFormatter(),
    ) {
    }

    public function arrayToJson(string $phpArray): ConversionResult
    {
        try {
            $value = $this->arrayLiteralParser->parse($phpArray);
            $json = $this->jsonFormatter->format($value);

            return new ConversionResult(
                phpArray: $phpArray,
                json: $json,
                error: null,
            );
        } catch (ConversionError $error) {
            return new ConversionResult(
                phpArray: $phpArray,
                json: '',
                error: $error->getMessage(),
            );
        }
    }

    public function jsonToArray(string $json): ConversionResult
    {
        try {
            $value = json_decode($json, true, self::MAX_DEPTH, JSON_THROW_ON_ERROR);

            if (!is_array($value)) {
                throw new ConversionError('Top-level JSON value must be an object or array.');
            }

            $phpArray = $this->arrayLiteralFormatter->format($value);

            return new ConversionResult(
                phpArray: $phpArray,
                json: $json,
                error: null,
            );
        } catch (\JsonException $error) {
            return new ConversionResult(
                phpArray: '',
                json: $json,
                error: 'JSON parse error: ' . $error->getMessage(),
            );
        } catch (ConversionError $error) {
            return new ConversionResult(
                phpArray: '',
                json: $json,
                error: $error->getMessage(),
            );
        }
    }
}

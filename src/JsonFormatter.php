<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

use JsonException;

final class JsonFormatter
{
    public function format(mixed $value): string
    {
        try {
            return json_encode(
                $value,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $error) {
            throw new ConversionError('JSON encode error: ' . $error->getMessage(), previous: $error);
        }
    }
}

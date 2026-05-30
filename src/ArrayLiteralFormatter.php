<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final class ArrayLiteralFormatter
{
    private const int MAX_DEPTH = 128;

    public function format(mixed $value): string
    {
        return $this->formatValue($value, 0);
    }

    private function formatValue(mixed $value, int $depth): string
    {
        return match (true) {
            is_array($value) => $this->formatArray($value, $depth),
            is_string($value) => $this->formatString($value),
            is_int($value) => (string) $value,
            is_float($value) => $this->formatFloat($value),
            is_bool($value) => $value ? 'true' : 'false',
            $value === null => 'null',
            default => throw new ConversionError('Unsupported value type.'),
        };
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function formatArray(array $value, int $depth): string
    {
        if ($depth >= self::MAX_DEPTH) {
            throw new ConversionError('PHP array format error: maximum array depth exceeded.');
        }

        if ($value === []) {
            return '[]';
        }

        $nextDepth = $depth + 1;
        $lines = ['['];
        $isList = array_is_list($value);

        foreach ($value as $key => $item) {
            $line = $this->indent($nextDepth);

            if (!$isList) {
                $line .= $this->formatKey($key) . ' => ';
            }

            $line .= $this->formatValue($item, $nextDepth) . ',';
            $lines[] = $line;
        }

        $lines[] = $this->indent($depth) . ']';

        return implode("\n", $lines);
    }

    private function formatKey(int|string $key): string
    {
        if (is_int($key)) {
            return (string) $key;
        }

        return $this->formatString($key);
    }

    private function formatString(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }

    private function formatFloat(float $value): string
    {
        if (!is_finite($value)) {
            throw new ConversionError('Unsupported value type.');
        }

        $literal = (string) $value;

        if (!str_contains($literal, '.') && !str_contains($literal, 'E') && !str_contains($literal, 'e')) {
            return $literal . '.0';
        }

        return $literal;
    }

    private function indent(int $depth): string
    {
        return str_repeat(' ', $depth * 4);
    }
}

<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final class ArrayLiteralParser
{
    private const int MAX_DEPTH = 128;

    /** @var list<Token> */
    private array $tokens = [];

    private int $position = 0;

    public function parse(string $input): mixed
    {
        $this->tokens = $this->tokenize($input);
        $this->position = 0;

        $this->skipOpenTag();

        if (!$this->current()?->matches('[')) {
            throw new ConversionError('Unsupported PHP syntax: root value must be a short array literal.');
        }

        $value = $this->parseArray(0);
        $this->consumeIf(';');

        if (!$this->isEnd()) {
            throw new ConversionError('PHP array parse error: unexpected token ' . $this->describe($this->current()) . '.');
        }

        return $value;
    }

    /**
     * @return list<Token>
     */
    private function tokenize(string $input): array
    {
        $code = str_starts_with(ltrim($input), '<?php') ? $input : '<?php ' . $input;
        $tokens = [];

        foreach (token_get_all($code) as $token) {
            if (is_array($token)) {
                [$id, $text, $line] = $token;

                if (in_array($id, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                $tokens[] = new Token($id, $text, $line);
                continue;
            }

            $tokens[] = new Token(null, $token, null);
        }

        return $tokens;
    }

    private function skipOpenTag(): void
    {
        if ($this->current()?->id === T_OPEN_TAG) {
            $this->position++;
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private function parseArray(int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            throw new ConversionError('PHP array parse error: maximum array depth exceeded.');
        }

        $this->consume('[');

        $items = [];
        $nextIntegerKey = null;

        while (!$this->consumeIf(']')) {
            $first = $this->parseValue($depth);

            if ($this->consumeIf(T_DOUBLE_ARROW)) {
                if (!is_int($first) && !is_string($first)) {
                    throw new ConversionError('PHP array parse error: array key must be string or int.');
                }

                $items[$first] = $this->parseValue($depth);

                if (is_int($first)) {
                    $nextIntegerKey = $this->advanceNextIntegerKey($nextIntegerKey, $first);
                }
            } else {
                $key = $nextIntegerKey ?? 0;
                $items[$key] = $first;
                $nextIntegerKey = $this->advanceNextIntegerKey($nextIntegerKey, $key);
            }

            if ($this->consumeIf(']')) {
                break;
            }

            if (!$this->current()?->matches(',')) {
                throw new ConversionError('Unsupported PHP syntax: unexpected token ' . $this->describe($this->current()) . '.');
            }

            $this->consume(',');

            if ($this->consumeIf(']')) {
                break;
            }
        }

        return $items;
    }

    private function advanceNextIntegerKey(?int $nextIntegerKey, int $usedKey): int
    {
        if ($nextIntegerKey === null || $usedKey >= $nextIntegerKey) {
            if ($usedKey === PHP_INT_MAX) {
                throw new ConversionError('PHP array parse error: integer key is too large.');
            }

            return $usedKey + 1;
        }

        return $nextIntegerKey;
    }

    private function parseValue(int $depth): mixed
    {
        $token = $this->current();

        if ($token === null) {
            throw new ConversionError('PHP array parse error: unexpected end of input.');
        }

        if ($token->matches('[')) {
            return $this->parseArray($depth + 1);
        }

        if ($token->id === T_CONSTANT_ENCAPSED_STRING) {
            $this->position++;

            return $this->parseString($token->text);
        }

        if ($token->id === T_LNUMBER) {
            $this->position++;

            return $this->parseIntegerLiteral($token->text);
        }

        if ($token->id === T_DNUMBER) {
            $this->position++;

            return (float) $token->text;
        }

        if ($token->matches('-')) {
            $this->position++;

            return $this->parseNegativeNumber();
        }

        if ($token->id === T_STRING) {
            $this->position++;

            return match (strtolower($token->text)) {
                'true' => true,
                'false' => false,
                'null' => null,
                default => throw new ConversionError('Unsupported PHP syntax: ' . $token->text . ' is not allowed.'),
            };
        }

        throw new ConversionError('Unsupported PHP syntax: unexpected token ' . $this->describe($token) . '.');
    }

    private function parseNegativeNumber(): int|float
    {
        $token = $this->current();

        if ($token?->id === T_LNUMBER) {
            $this->position++;

            return -$this->parseIntegerLiteral($token->text);
        }

        if ($token?->id === T_DNUMBER) {
            $this->position++;

            return -(float) $token->text;
        }

        throw new ConversionError('Unsupported PHP syntax: unexpected token ' . $this->describe($token) . '.');
    }

    private function parseIntegerLiteral(string $literal): int
    {
        if (!preg_match('/^[0-9]+$/', $literal)) {
            throw new ConversionError('Unsupported PHP syntax: unsupported integer literal.');
        }

        $value = filter_var($literal, FILTER_VALIDATE_INT);

        if ($value === false) {
            throw new ConversionError('Unsupported PHP syntax: integer literal is too large.');
        }

        return $value;
    }

    private function parseString(string $literal): string
    {
        if ($literal[0] === "'") {
            return $this->parseSingleQuotedString($literal);
        }

        if ($literal[0] === '"') {
            return $this->parseDoubleQuotedString($literal);
        }

        throw new ConversionError('Unsupported PHP syntax: unsupported string literal.');
    }

    private function parseSingleQuotedString(string $literal): string
    {
        $body = substr($literal, 1, -1);

        return str_replace(['\\\\', "\\'"], ['\\', "'"], $body);
    }

    private function parseDoubleQuotedString(string $literal): string
    {
        $body = substr($literal, 1, -1);
        $result = '';
        $length = strlen($body);

        for ($index = 0; $index < $length; $index++) {
            $char = $body[$index];

            if ($char !== '\\') {
                $result .= $char;
                continue;
            }

            $index++;

            if ($index >= $length) {
                throw new ConversionError('Unsupported PHP syntax: invalid escape sequence in double-quoted string.');
            }

            $result .= match ($body[$index]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'v' => "\v",
                'e' => "\x1B",
                'f' => "\f",
                '\\' => '\\',
                '$' => '$',
                '"' => '"',
                default => throw new ConversionError(
                    'Unsupported PHP syntax: unsupported escape sequence \\' . $body[$index] . ' in double-quoted string.',
                ),
            };
        }

        return $result;
    }

    private function consume(int|string $expected): Token
    {
        $token = $this->current();

        if ($token === null || !$token->matches($expected)) {
            $expectedText = is_int($expected) ? token_name($expected) : $expected;

            throw new ConversionError('PHP array parse error: expected ' . $expectedText . '.');
        }

        $this->position++;

        return $token;
    }

    /**
     * @phpstan-impure
     */
    private function consumeIf(int|string $expected): bool
    {
        $token = $this->current();

        if ($token === null || !$token->matches($expected)) {
            return false;
        }

        $this->position++;

        return true;
    }

    private function current(): ?Token
    {
        return $this->tokens[$this->position] ?? null;
    }

    private function isEnd(): bool
    {
        return $this->current() === null;
    }

    private function describe(?Token $token): string
    {
        if ($token === null) {
            return 'end of input';
        }

        if ($token->id !== null) {
            return token_name($token->id) . ' "' . $token->text . '"';
        }

        return '"' . $token->text . '"';
    }
}

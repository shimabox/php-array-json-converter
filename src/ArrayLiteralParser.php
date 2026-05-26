<?php

declare(strict_types=1);

namespace PhpArrayJson;

final class ArrayLiteralParser
{
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

        $value = $this->parseArray();
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
    private function parseArray(): array
    {
        $this->consume('[');

        $items = [];
        $nextIndex = 0;

        while (!$this->consumeIf(']')) {
            $first = $this->parseValue();

            if ($this->consumeIf(T_DOUBLE_ARROW)) {
                if (!is_int($first) && !is_string($first)) {
                    throw new ConversionError('PHP array parse error: array key must be string or int.');
                }

                $items[$first] = $this->parseValue();
            } else {
                $items[$nextIndex] = $first;
                $nextIndex++;
            }

            if ($this->consumeIf(']')) {
                break;
            }

            $this->consume(',');

            if ($this->consumeIf(']')) {
                break;
            }
        }

        return $items;
    }

    private function parseValue(): mixed
    {
        $token = $this->current();

        if ($token === null) {
            throw new ConversionError('PHP array parse error: unexpected end of input.');
        }

        if ($token->matches('[')) {
            return $this->parseArray();
        }

        if ($token->id === T_CONSTANT_ENCAPSED_STRING) {
            $this->position++;

            return $this->parseString($token->text);
        }

        if ($token->id === T_LNUMBER) {
            $this->position++;

            return (int) $token->text;
        }

        if ($token->id === T_DNUMBER) {
            $this->position++;

            return (float) $token->text;
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

    private function parseString(string $literal): string
    {
        if ($literal[0] !== "'") {
            throw new ConversionError('Unsupported PHP syntax: double-quoted strings are not supported yet.');
        }

        $body = substr($literal, 1, -1);

        return str_replace(["\\\\", "\\'"], ["\\", "'"], $body);
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

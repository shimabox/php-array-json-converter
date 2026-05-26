<?php

declare(strict_types=1);

namespace PhpArrayJson;

final readonly class Token
{
    public function __construct(
        public ?int $id,
        public string $text,
        public ?int $line,
    ) {
    }

    public function matches(int|string $expected): bool
    {
        if (is_int($expected)) {
            return $this->id === $expected;
        }

        return $this->id === null && $this->text === $expected;
    }
}

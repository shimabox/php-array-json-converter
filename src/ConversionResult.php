<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final readonly class ConversionResult
{
    public function __construct(
        public string $phpArray,
        public string $json,
        public ?string $error,
    ) {
    }

    public function succeeded(): bool
    {
        return $this->error === null;
    }
}

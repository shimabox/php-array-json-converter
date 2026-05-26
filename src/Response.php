<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

final readonly class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
    ) {
    }
}

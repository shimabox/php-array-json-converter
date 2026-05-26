<?php

declare(strict_types=1);

namespace PhpArrayJson;

/**
 * @param array<string, string> $headers
 */
final readonly class Response
{
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
    ) {
    }
}

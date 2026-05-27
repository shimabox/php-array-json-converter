<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter\Http;

use PhpArrayJsonConverter\Response;

final class JsonResponseFactory
{
    /**
     * @param array<string, mixed> $payload
     */
    public function create(int $statusCode, array $payload): Response
    {
        try {
            $body = json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $error) {
            throw new \RuntimeException('Failed to encode JSON response.', previous: $error);
        }

        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json; charset=utf-8'],
            $body . "\n",
        );
    }
}

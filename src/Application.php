<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

use PhpArrayJsonConverter\Controller\ConvertController;
use PhpArrayJsonConverter\Http\JsonResponseFactory;

final class Application
{
    public const int MAX_BODY_BYTES = 1_048_576;

    public function __construct(
        private readonly ConvertController $convertController = new ConvertController(),
        private readonly JsonResponseFactory $responseFactory = new JsonResponseFactory(),
    ) {
    }

    public function handle(string $method, string $path, string $body = ''): Response
    {
        if (strlen($body) > self::MAX_BODY_BYTES) {
            return $this->responseFactory->create(413, [
                'error' => 'Payload Too Large',
            ]);
        }

        if ($method === 'POST' && $path === '/api/convert') {
            return $this->convertController->convert($body);
        }

        return $this->responseFactory->create(404, [
            'error' => 'Not Found',
        ]);
    }
}

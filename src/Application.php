<?php

declare(strict_types=1);

namespace PhpArrayJsonConverter;

use PhpArrayJsonConverter\Controller\ConvertController;
use PhpArrayJsonConverter\Http\JsonResponseFactory;

final class Application
{
    public function __construct(
        private readonly ConvertController $convertController = new ConvertController(),
        private readonly JsonResponseFactory $responseFactory = new JsonResponseFactory(),
    ) {
    }

    public function handle(string $method, string $path, string $body = ''): Response
    {
        if ($method === 'POST' && $path === '/api/convert') {
            return $this->convertController->convert($body);
        }

        return $this->responseFactory->create(404, [
            'error' => 'Not Found',
        ]);
    }
}

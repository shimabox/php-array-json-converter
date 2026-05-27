<?php

declare(strict_types=1);

use PhpArrayJsonConverter\Application;

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

if (!is_string($requestUri)) {
    $requestUri = '/';
}

$path = parse_url($requestUri, PHP_URL_PATH);

if (!is_string($path) || $path === '') {
    $path = '/';
}

if ($path === '/') {
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/index.html');

    return true;
}

if (!str_starts_with($path, '/api/')) {
    return false;
}

require __DIR__ . '/../vendor/autoload.php';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!is_string($requestMethod)) {
    $requestMethod = 'GET';
}

$body = file_get_contents('php://input');

if ($body === false) {
    $body = '';
}

$app = new Application();
$response = $app->handle($requestMethod, $path, $body);

http_response_code($response->statusCode);

foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}

echo $response->body;

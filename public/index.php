<?php

declare(strict_types=1);

use PhpArrayJsonConverter\Application;

require __DIR__ . '/../vendor/autoload.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (!is_string($path) || $path === '') {
    $path = '/';
}

$app = new Application();
$response = $app->handle($_SERVER['REQUEST_METHOD'] ?? 'GET', $path, $_POST);

http_response_code($response->statusCode);

foreach ($response->headers as $name => $value) {
    header($name . ': ' . $value);
}

echo $response->body;

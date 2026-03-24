<?php

$router = require __DIR__ . '/../src/bootstrap.php';

try {
    $router->dispatch(Request::method(), Request::uri());
} catch (Throwable $e) {
    Response::error('Server error: ' . $e->getMessage(), 500);
}
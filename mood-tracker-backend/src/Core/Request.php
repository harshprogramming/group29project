<?php

class Request
{
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path, '/') ?: '/';
    }

    public static function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    public static function body(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            $decoded = json_decode($input, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (self::method() === 'PUT' || self::method() === 'DELETE') {
            $input = file_get_contents('php://input');
            parse_str($input, $parsed);
            return $parsed;
        }

        return $_POST;
    }
}
<?php

namespace App\core;

class Response
{
    public static function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success(array $data = [], string $message = 'OK', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    public static function error(string $message, array $errors = [], int $statusCode = 400): void
    {
        self::json([
            'success' => false,
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }

    public static function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }
}

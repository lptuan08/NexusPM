<?php

namespace App\core;

class Request
{
    private array $bodyCache = [];
    private array $queryCache = [];

    /**
     * Returns sanitized request data for the current method.
     *
     * Kept for backward compatibility: GET reads query params, POST reads form body.
     */
    public function getBody(): array
    {
        if ($this->isGet()) {
            return $this->getQuery();
        }

        return $this->post();
    }

    public function post(): array
    {
        if ($this->bodyCache !== []) {
            return $this->bodyCache;
        }

        $this->bodyCache = $this->sanitizeInput($_POST);

        return $this->bodyCache;
    }

    public function getQuery(): array
    {
        if ($this->queryCache !== []) {
            return $this->queryCache;
        }

        $this->queryCache = $this->sanitizeInput($_GET);

        return $this->queryCache;
    }

    private function sanitizeInput(array $input): array
    {
        $data = [];
        foreach ($input as $key => $value) {
            $data[$key] = $this->sanitize($value);
        }

        return $data;
    }

    private function sanitize($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->sanitize($val);
            }

            return $value;
        }

        return is_string($value) ? trim($value) : $value;
    }

    public static function uri(): string
    {
        $url = '/';
        if (!empty($_SERVER['PATH_INFO'])) {
            $url = '/' . trim($_SERVER['PATH_INFO'], '/');
        }

        return $url;
    }

    public function input(string $key, $default = null)
    {
        $body = $this->getBody();

        return $body[$key] ?? $default;
    }

    public static function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return self::getMethod() === 'POST';
    }

    public function isGet(): bool
    {
        return self::getMethod() === 'GET';
    }
}

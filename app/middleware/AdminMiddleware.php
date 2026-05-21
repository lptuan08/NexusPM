<?php

namespace App\middleware;

use App\interfaces\MiddlewareInterface;
use App\core\Session;
use Exception;

class AdminMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ((Session::get('user')['role'] ?? null) !== 'admin') {
            throw new Exception('Bạn không có quyền truy cập khu vực quản trị.', 403);
        }
    }
}

<?php

namespace App\middleware;

use App\interfaces\MiddlewareInterface;

class AdminMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        if ($_SESSION['role'] !== 'admin') {
            die("Lỗi 403: Không có quyền Admin!");
        }
    }
}

<?php
namespace App\middleware;

use App\interfaces\MiddlewareInterface;
use App\core\Response;

/**
 * Middleware AuthMiddleware
 * Kiểm tra trạng thái đăng nhập của người dùng trước khi truy cập các tài nguyên bảo mật.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
            Response::redirect(URLROOT . '/login');
        }
        
    }
}

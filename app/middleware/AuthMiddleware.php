<?php

namespace App\middleware;

use App\interfaces\MiddlewareInterface;
use App\core\Response;
use App\core\Session;

/**
 * Middleware AuthMiddleware
 * Kiểm tra trạng thái đăng nhập của người dùng trước khi truy cập các tài nguyên bảo mật.
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
            Response::redirect(URLROOT . '/login');
        }

        $now = time();
        $loginAt = (int) ($_SESSION['login_at'] ?? 0);
        $lastActivity = (int) ($_SESSION['last_activity'] ?? 0);

        $idleTimeout = defined('SESSION_IDLE_TIMEOUT') ? SESSION_IDLE_TIMEOUT : 3600;
        $absoluteTimeout = defined('SESSION_ABSOLUTE_TIMEOUT') ? SESSION_ABSOLUTE_TIMEOUT : 28800;

        $isExpired =
            $loginAt <= 0 ||
            $lastActivity <= 0 ||
            ($now - $lastActivity) > $idleTimeout ||
            ($now - $loginAt) > $absoluteTimeout;

        if ($isExpired) {
            Session::destroy();
            Response::redirect(URLROOT . '/login');
        }

        $_SESSION['last_activity'] = $now;
    }
}

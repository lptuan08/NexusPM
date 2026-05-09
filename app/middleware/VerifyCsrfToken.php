<?php
namespace App\middleware;

use App\interfaces\MiddlewareInterface;

class VerifyCsrfToken implements MiddlewareInterface
{
    public function handle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $requestToken = $_POST['csrf_token'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';

            if ($requestToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $requestToken)) {
                http_response_code(419);
                die("Lỗi bảo mật: Yêu cầu không hợp lệ (CSRF detected).");
            }
        }
    }
}

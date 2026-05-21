<?php
namespace App\middleware;

use App\interfaces\MiddlewareInterface;
use Exception;

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
                throw new Exception('Yêu cầu không hợp lệ hoặc token bảo mật đã hết hạn.', 419);
            }
        }
    }
}

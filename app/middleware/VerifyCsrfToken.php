<?php
namespace App\middleware;

use App\interfaces\MiddlewareInterface;

class VerifyCsrfToken{
    public function handle(){
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                die("Lỗi bảo mật: Yêu cầu không hợp lệ (CSRF detected).");
            }
        }
    }
}
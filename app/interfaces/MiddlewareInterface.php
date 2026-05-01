<?php
namespace App\interfaces;

/**
 * Interface MiddlewareInterface
 * Định nghĩa cấu trúc bắt buộc cho các lớp Middleware trong hệ thống.
 */
// app/Interfaces/MiddlewareInterface.php
interface MiddlewareInterface
{
    public function handle();
}

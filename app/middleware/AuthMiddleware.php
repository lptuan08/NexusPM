<?php
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

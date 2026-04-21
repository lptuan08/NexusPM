<?php
class ErrorHandler
{
    public static function handle($e)
    {
        // Debug mode (dev)
        if (defined('APP_DEBUG') && APP_DEBUG) {
            // require DIR_ROOT . 'app/views/errors/debug.php';
            http_response_code(500);
            require APP_PATH . '/views/errors/debug.php';
            exit;
        }

        
        // Lấy code lỗi
        $code = $e->getCode();

        // Nếu không có code → mặc định 500
        if (!$code) {
            $code = 500;
        }

        // Set HTTP status
        http_response_code($code);

        // Phân loại lỗi
        switch ($code) {
            case 404:
                require APP_PATH . '/views/errors/404.php';
                break;

            default:
                require APP_PATH . '/views/errors/500.php';
                break;
        }


        exit;
    }
}

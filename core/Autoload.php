<?php

/**
 * Tự động nạp các lớp (classes) dựa trên Namespace.
 * - $prefix: Tiền tố namespace cần kiểm tra (App\).
 * - $baseDir: Thư mục gốc chứa mã nguồn ứng dụng (app/).
 * - Chuyển đổi dấu gạch chéo ngược (\) trong namespace thành dấu gạch chéo xuôi (/) của hệ điều hành.
 * - Kiểm tra sự tồn tại của file trước khi require để tránh lỗi hệ thống.
 */
spl_autoload_register(function ($class) {

    $prefix = 'App\\';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));

    // Nếu thuộc namespace App\core thì tìm trong thư mục CORE_PATH
    if (strpos($relativeClass, 'core\\') === 0) {
        $className = substr($relativeClass, strlen('core\\'));
        $file = CORE_PATH . '/' . str_replace('\\', '/', $className) . '.php';
    } else {
        // Ngược lại tìm trong thư mục APP_PATH (controllers, models, helpers...)
        $file = APP_PATH . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    }

    if (file_exists($file)) {
        require_once $file;
    }
});

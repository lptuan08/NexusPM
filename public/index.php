<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// thứ tự require: Config → Core → Session → Run
define('APP_DEBUG', true); // dev -> trạng thái debug lỗi, cho ErrorHandler.php dùng
// 1. Khai báo hằng số đường dẫn gốc (Physical Paths)
define('APPROOT', dirname(dirname(__FILE__)));

// URLROOT: Virtual Host: dùng nexuspm.test trỏ vào C:/.../NexusPM/public
define('URLROOT', 'http://nexuspm.test'); //-> tương đương: 'http://nexuspm.test/public'





// 2. Định nghĩa các đường dẫn thư mục nội bộ
define('APP_PATH', APPROOT . '/app');
define('CORE_PATH', APPROOT . '/core');
define('CONFIG_PATH', APPROOT . '/config');
define('VIEW_PATH', APP_PATH . '/views');

// 1. Nạp Autoloader chính (Xử lý các class có Namespace App\)
require_once CORE_PATH . '/Autoload.php';


require_once CONFIG_PATH . '/config.php';
$routes = require_once CONFIG_PATH . '/routes.php';

// require_once APP_PATH . '/interface/Middlewareinterface.php';
// require_once APP_PATH . '/interface/Middlewareinterface.php';


// 3. Nạp các file cấu hình và interface bắt buộc
use App\core\Router;
use App\core\Request;
use App\core\ErrorHandler;
use App\helpers\Helper;
// 4. Khởi chạy ứng dụng
try {
    $app = new Router($routes);
    $url = Request::uri();
    $app->dispatch($url);
    if (APP_DEBUG) Helper::debug_mvc_widget(); // Chỉ hiển thị widget debug khi APP_DEBUG là true
} catch (Exception $e) {
    ErrorHandler::handle($e);
}

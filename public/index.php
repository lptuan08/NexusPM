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


require_once APP_PATH . '/interface/Middlewareinterface.php';


// Nạp file cấu hình ứng dụng
require_once CONFIG_PATH . '/config.php';
$routes = require_once CONFIG_PATH . '/routes.php';

// Core
require_once CORE_PATH . '/Config.php';
require_once CORE_PATH . '/View.php';
require_once CORE_PATH . '/Controller.php';
require_once CORE_PATH . '/Validator.php';
require_once CORE_PATH . '/Model.php';
require_once CORE_PATH . '/Database.php';
require_once CORE_PATH . '/Connection.php';
require_once CORE_PATH . '/ErrorHandler.php';
require_once CORE_PATH . '/Request.php';
require_once CORE_PATH . '/Response.php';
require_once APP_PATH . '/helpers/Helper.php';
require_once APP_PATH . '/helpers/SecurityHelper.php';

// App
// require_once CORE_PATH . '/App.php';
require_once CORE_PATH . '/Router.php';


// run
try {
    $app = new Router($routes);
    $url = Request::uri();
    $app->dispatch($url);
    if (APP_DEBUG) Helper::debug_mvc_widget(); // Chỉ hiển thị widget debug khi APP_DEBUG là true
} catch (Exception $e) {
    ErrorHandler::handle($e);
}

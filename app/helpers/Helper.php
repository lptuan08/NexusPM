<?php
namespace App\helpers;

use App\core\Session;

// method dùng chung: 
class Helper
{

    /**
     * 1. Xử lý đường dẫn URL tuyệt đối
     * không bị lỗi hình ảnh/CSS khi thay đổi cấu trúc thư mục
     */
    public static function asset($path)
    {
        return URLROOT . "/" . ltrim($path, '/');
    }


    /**
     * 2. Chuyển hướng trang (Redirect)
     */
    public static function redirect($url)
    {
        header("Location: " . $url);
        exit();
    }

    /**
     * 3. Làm sạch dữ liệu (Sanitization)
     * Tránh các cuộc tấn công XSS khi hiển thị dữ liệu từ người dùng
     */
    public static function clean($data)
    {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * 4. Quản lý thông báo nhanh (Flash Messages)
     * Rất hữu ích để báo "Đăng nhập thành công" hoặc "Lỗi"
     */
    public static function setFlash($key, $message)
    {
        Session::flash($key, $message); // có message -> nó sẽ ghi $_SESSION['flash_messages'][$key]
    }

    public static function getFlash($key)
    {
        return Session::flash($key); // không có lấy ra return $msg và xóa $_SESSION['flash_messages'][$key]
    }

    /**
     * 5. Định dạng ngày tháng (Dùng cho thời hạn Task trong NexusPM)
     */
    public static function formatDate($date, $format = 'd/m/Y')
    {
        return date($format, strtotime($date));
    }

    /**
     * 6. Debug nhanh (Hàm "thần thánh" của dân PHP)
     */
    public static function dd($data)
    {
        echo "<pre style='background: #202124; color: #ffab40; padding: 15px; border-radius: 5px;'>";
        print_r($data);
        echo "</pre>";
        die();
    }
    // load file Middleware
    public static function loadMiddleware($mwName)
    {
        $path = APP_PATH . "/middleware/{$mwName}.php";
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
        return false;
    }
    // load file controller    
    public static function loadController($controllerName)
    {
        $path = APP_PATH . "/controllers/{$controllerName}.php";
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
        return false;
    }



    // debug mvc
    public static function debug_mvc_widget()
    {
        // 1. Lấy thông tin Method và URL thực tế từ Server
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // 2. Lấy thông tin Routing (Được gán từ Router::dispatch hoặc App::run)
        // Thử tìm ở cả các key phổ biến nếu bạn chưa thống nhất tên biến
        $controller = $GLOBALS['current_controller'] ?? 'N/A';
        $action     = $GLOBALS['current_action']     ?? 'N/A';
        $params     = $GLOBALS['current_params']     ?? [];




        // 3. Lấy Call Stack
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        // Bắt đầu render giao diện lơ lửng
?>
        <div id="debug-widget" style="
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        max-height: 500px;
        background: rgba(29, 29, 29, 0.95);
        color: #00ff00;
        font-family: 'Consolas', monospace;
        font-size: 12px;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        z-index: 9999;
        overflow-y: auto;
        border: 1px solid #444;
    ">
            <div style="border-bottom: 1px solid #444; padding-bottom: 8px; margin-bottom: 10px; display: flex; justify-content: space-between;">
                <strong>MVC DEBUGGER</strong>
                <span style="cursor:pointer;" onclick="this.parentElement.parentElement.style.display='none'">[x]</span>
            </div>

            <div style="margin-bottom: 10px;">
                <b style="color: #ffca28;">URL:</b> <span style="color: #fff;"><?= htmlspecialchars($uri) ?></span><br>
                <b style="color: #ffca28;">METHOD:</b> <span style="color: #fff;"><?= $method ?></span><br>
                <b style="color: #ffca28;">CONTROLLER:</b> <span style="color: #00e5ff; font-weight: bold;"><?= $controller ?></span><br>
                <b style="color: #ffca28;">ACTION:</b> <span style="color: #00e5ff; font-weight: bold;"><?= $action ?></span><br>
                <b style="color: #ffca28;">PARAMS:</b> <span style="color: #fff;"><?= json_encode($params) ?></span>
            </div>

            <div style="border-top: 1px solid #444; pt-10px;">
                <b style="color: #ffca28;">EXECUTION FLOW:</b>
                <ul style="padding-left: 15px; margin-top: 5px; list-style: decimal-leading-zero;">
                    <?php foreach ($trace as $step): ?>
                        <?php if (isset($step['file'])): ?>
                            <li style="margin-bottom: 5px; color: #888;">
                                <span style="color: #00ff00;"><?= $step['function'] ?>()</span><br>
                                <small style="font-size: 10px;"><?= basename($step['file']) ?>:<?= $step['line'] ?></small>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
<?php
    }
}

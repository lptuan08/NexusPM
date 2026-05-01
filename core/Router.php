<?php
namespace App\core;

use App\core\Request;
use Exception;

class Router
{
    private $routes;
    public function __construct($routes)
    {
        if(!empty($routes)){
            $this->routes = $routes;
        }
        else{
            throw new Exception("Cấu hình routes không được để trống", 500);
        }
    }

    public function dispatch($url)
    {
        // Xác định phương thức (GET/POST)
        $method = Request::getMethod();
        // 2. Kiểm tra nhóm phương thức có tồn tại không
        if (!isset($this->routes[$method])) {
            throw new Exception("Phương thức HTTP '{$method}' không được hỗ trợ", 405);
        }

        foreach ($this->routes[$method] as $routePath => $config) {
            $pattern = preg_replace('/\{[a-zA-Z0-9]+\}/', '([^/]+)', $routePath);
            // $routePath -> key - $routes
            // \{ và \} tìm dấu {}
            // [a-zA-Z0-9]+ tìm nội dung bên trong ngoặc nhọn
            // '([^/]+)' lấy tất cả ký tự trừ dấu gạch chéo / -> chuỗi thay thế
            // nó sẽ túm lấy giá trị ([^/]+) này làm params
            $pattern = "@^" . $pattern . "$@";

            if (preg_match($pattern, $url, $matches)) {
                
                // $matches = [1.giá trị khớp toàn bộ, 2.giá trị trong nhóm ([^/]+)=> tham số]
                array_shift($matches);
                //giải thích:
                // Loại bỏ phần tử đầu tiên trong mảng $matches (là chuỗi khớp toàn bộ) 
                //để chỉ giữ lại các giá trị tham số biến (như {id})

                // 1. Chạy Middleware
                if (!empty($config['middleware'])) {
                    foreach ($config['middleware'] as $mwName) {
                        $fullMwName = "App\\middleware\\" . $mwName;
                        $mwInstance = new $fullMwName();
                        $mwInstance->handle();
                    }
                }
                //2. gọi controller
                $controllerName = $config['controller'];
                $action = $config['action'];
                // debug mvc (xóa khi chuyển sang product)
                $GLOBALS['current_controller'] = $controllerName; // Gán tên controller hiện tại cho debug widget
                $GLOBALS['current_action'] = $action;
                $GLOBALS['current_params'] = $matches;
                
                $fullControllerName = "App\\controllers\\" . str_replace('/', '\\', $controllerName);
                if (class_exists($fullControllerName)) {
                    $controllerInstance = new $fullControllerName();
                    if (method_exists($controllerInstance, $action)) {
                        return call_user_func_array([$controllerInstance, $action], $matches);
                    } else {
                        throw new Exception("Action '{$action}' không tồn tại trong controller '{$fullControllerName}'", 404);
                    }
                } else {
                    throw new Exception("Class '{$fullControllerName}' không tồn tại. Vui lòng kiểm tra lại namespace và file controller.", 500);
                }
            }
        }
        // Không tìm thấy trang
        throw new Exception("Không tìm thấy trang", 404);
    }
}

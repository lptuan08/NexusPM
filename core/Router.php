<?php
class Router
{
    private $routes;
    public function __construct($routes)
    {
        $this->routes = $routes;
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
                        // xử lý auto load sau.
                        Helper::loadMiddleware($mwName);
                        $mwInstance = new $mwName();
                        $mwInstance->handle();
                    }
                }
                //2. gọi controller
                $controllerName = $config['controller'];
                $action = $config['action'];
                // debug mvc (xóa khi chuyển sang product)
                $GLOBALS['current_controller'] = $controllerName;
                $GLOBALS['current_action'] = $action;
                $GLOBALS['current_params'] = $matches;
                // load file controller
                if (Helper::loadController($controllerName)) {
                    //kiểm tra class cotroller có tồn tại                    
                    if (class_exists($controllerName)) {
                        $controllerInstance = new $controllerName();
                        if (method_exists($controllerInstance, $action)) {
                            return call_user_func_array([$controllerInstance, $action], $matches);
                        } else {
                            throw new Exception("Action '{$action}' không tồn tại trong controller '{$controllerName}'", 404);
                        }
                    } else {
                        throw new Exception("Class '{$controllerName}' không tồn tại", 500);
                    }
                } else {
                    throw new Exception("controllers/'{$controllerName}'không tồn tại", 500);
                }
            }
        }
        // Không tìm thấy trang
        throw new Exception("Không tìm thấy trang", 404);
    }
}

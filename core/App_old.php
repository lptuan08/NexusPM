<?php
class App
{
    private $controller = 'Dashboard'; // Tên Controller mặc định
    private $action = 'index';         // Tên phương thức (action) mặc định
    private $params = [];              // Mảng chứa các tham số truyền qua URL
    private $_routes;                  // Đối tượng xử lý định tuyến (Route)
    public static $app;                // Lưu instance tĩnh để truy cập App từ mọi nơi

    public function __construct()
    {
        self::$app = $this;
        $this->_routes = Router::getInstance(); // Khởi tạo thông qua Singleton
    }

    // hàm này mạnh nhe, để chạy bên index
    public function run()
    {
        $this->handleUrl();
    }

    /**
     * Xử lý URL, phân tách thành Controller, Action và Params
     * Đây là bước quan trọng nhất để xác định code nào sẽ được thực thi.
     */
    public function handleUrl()
    {
        // 1. Lấy URL thô từ trình duyệt (ví dụ: 'nguoi-dung/danh-sach')
        $originalUrl = $this->_routes->getUrl();
        // 2. Chuyển đổi URL ảo (alias) thành URL thật dựa trên config/routes.php
        // Ví dụ: 'nguoi-dung' sẽ được dịch thành 'Users/getlist'
        $processedUrl =  $this->_routes->getRealPath($originalUrl);
        // 3. tách chuỗi URL thành mảng các phần tử
        $urlSegments = array_filter(explode('/', trim($processedUrl, '/')));

        // 4. Xác định Controller
        if (!empty($urlSegments[0])) {
            $this->controller = ucfirst($urlSegments[0]);
            array_shift($urlSegments); // Xóa phần tử đầu tiên sau khi đã lấy
        }

        // 5. xác định action (Method)
        if (!empty($urlSegments[0])) {
            $this->action = $urlSegments[0];
            array_shift($urlSegments); // Xóa phần tử tiếp theo

        }
        // 6. Các phần tử trong mảng chính pà Params
        $this->params = !empty($urlSegments) ? array_values($urlSegments) : [];

        // 7. Kiểm tra sự tồn tại của file controller vật lý
        $controllerPath = APP_PATH . "/controllers/{$this->controller}.php";

        if (file_exists($controllerPath)) {
            require_once $controllerPath;
            // 8. Kiểm tra xem class Controller có được định nghĩa trong file không
            if (class_exists($this->controller)) {
                // Khởi tạo đối tượng
                $controllerObject = new $this->controller();
                // 9. Kiểm tra Action (phương thức) có tồn tại trong Controller
                if (method_exists($controllerObject, $this->action)) {
                    // Thực thi Action và truyền mảng tham số vào
                    call_user_func_array([$controllerObject, $this->action], $this->params);
                } else {
                    // Nếu không có action, báo lỗi 404
                    throw new Exception("Không tìm thấy action (method) '{$this->action}' trong controller '{$this->controller}'", 404);
                }
            } else {
                // Nếu file tồn tại nhưng không định nghĩa class trùng tên
                throw new Exception("Không tìm thấy controller (class) '{$this->controller}' trong file '{$controllerPath}'", 404);
            }
        } else {
            // Nếu file .php không tồn tại trong thư mục controllers
            throw new Exception("Đường dẫn không tồn tại (File controller '{$this->controller}.php' không tìm thấy)", 404);
        }
    }
}
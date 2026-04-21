<?php
class Router
{
    public $url;
    public $handleUrl;

    public static function getInstance()
    {
        static $instance = null;
        if ($instance == null) {
            $instance = new Router();
        }
        return $instance;
    }
    public function getUrl()
    {
        if (!empty($_SERVER['PATH_INFO'])) {
            return trim($_SERVER['PATH_INFO'], '/');
        } else {
            return '/';
        }
    }

    public function getRealPath($url)
    {

        global $routes;

        // 1. Gán mặc định handleUrl là url hiện tại (phòng trường hợp không khớp route nào)
        $this->handleUrl = $url;
        // 2. Kiểm tra xem biến toàn cục $routes (từ config/routes.php) có dữ liệu không
        if (!empty($routes)) {
            // Duyệt qua từng cấu hình route: $key là đường dẫn ảo, $value là đường dẫn thực
            foreach ($routes as $key => $value) {
                // 3. Sử dụng Regex để kiểm tra xem URL hiện tại có khớp với cấu hình $key không
                if (preg_match('~^' . $key . '$~is', $url)) {
                    // 4. Nếu khớp, thay thế URL ảo bằng URL thực (hỗ trợ cả tham số như $1, $2)
                    $this->handleUrl = preg_replace('~^' . $key . '$~is', $value, $url);
                    // 5. Tìm thấy rồi thì thoát vòng lặp để tối ưu hiệu năng
                    break;
                }
            }
        }
        return $this->handleUrl;
    }
}

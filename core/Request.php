<?php
class Request
{
    // Biến lưu trữ dữ liệu đã lọc để tránh việc phải chạy lọc nhiều lần (caching)
    private array $bodyCache = [];

    /**
     * Lấy và làm sạch toàn bộ dữ liệu từ Request (GET hoặc POST)
     * * @return array Trả về mảng dữ liệu đã được sanitize
     */
    public function getBody(): array
    {
        // Nếu đã có trong cache thì trả về luôn, không cần lọc lại
        if (!empty($this->bodyCache)) {
            return $this->bodyCache;
        }

        $data = [];
        $method = $this->getMethod();

        if ($method === 'GET') {
            foreach ($_GET as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
        } elseif ($method === 'POST') {
            foreach ($_POST as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
        }

        $this->bodyCache = $data;
        return $data;
    }


    /**
     * Hàm hỗ trợ làm sạch dữ liệu đệ quy (Xử lý được cả chuỗi đơn và mảng lồng nhau)
     * * @param mixed $value Dữ liệu thô từ superglobals
     * @return mixed Dữ liệu đã sạch
     */
    private function sanitize($value)
    {
        if (is_array($value)) {
            // Nếu là mảng (ví dụ checkbox hobbies[]), lặp tiếp để lọc từng phần tử bên trong
            foreach ($value as $key => $val) {
                $value[$key] = $this->sanitize($val);
            }
            return $value;
        }

        // Sử dụng FILTER_SANITIZE_SPECIAL_CHARS để ngăn chặn XSS 
        // Lưu ý: filter_var linh hoạt hơn filter_input khi xử lý đệ quy
        return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    // Hàm tĩnh để lấy URL hiện tại
    public static function uri()
    {
        $url ='/';
        if (!empty($_SERVER['PATH_INFO'])) {
            $url ="/". trim($_SERVER['PATH_INFO'], '/');
        } 
        return $url;
    }

    /**
     * Lấy giá trị của một input cụ thể
     */
    public function input(string $key, $default = null)
    {
        $body = $this->getBody();
        return $body[$key] ?? $default;
    }

    public static function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    public function isPost()
    {
        return $this->getMethod() === 'POST';
    }

    public function isGet()
    {
        return $this->getMethod() === 'GET';
    }
}

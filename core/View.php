<?php
class View
{
    public static function render($view, $data = [], $layout = 'layouts/main')
    {
        // 1. Xác định đường dẫn file view cụ thể (vd: projects/index.php)
        $viewFile = VIEW_PATH . '/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new Exception("View file '{$view}' không tồn tại", 404);
        }

        // 2. Trích xuất dữ liệu thành các biến để dùng trong view và layout (vd: $title, $data...)
        extract($data);

        // 3. Sử dụng Output Buffering để nạp nội dung view vào bộ đệm
        ob_start();
        require_once $viewFile;
        $content = ob_get_clean(); // Lưu nội dung đã render vào biến $content và xóa bộ đệm

        // 4. Nếu không yêu cầu layout (truyền null), in thẳng nội dung view (dùng cho AJAX)
        if (!$layout) {
            echo $content;
            return;
        }

        // 5. Nạp layout. Lúc này biến $content đã sẵn sàng để hiển thị bên trong layout
        $layoutPath = VIEW_PATH . '/' . $layout . '.php';
        if (file_exists($layoutPath)) {
            require_once $layoutPath;
        } else {
            throw new Exception("Layout '{$layout}' không tồn tại", 500);
        }
    }
}

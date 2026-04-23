<?php
// method dùng chung: 
class Helper
{

    /**
     * 1. Xử lý đường dẫn URL tuyệt đối
     * không bị lỗi hình ảnh/CSS khi thay đổi cấu trúc thư mục
     */
    public static function asset($path)
    {
        return "http://nexuspm.test/" . ltrim($path, '/');
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
        $_SESSION['flash'][$key] = $message;
    }

    public static function getFlash($key)
    {
        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]); // Xem xong xóa luôn
            return $msg;
        }
        return null;
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
}

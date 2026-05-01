<?php
namespace App\core;

class Session
{
    /**
     * Gán giá trị vào session
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Lấy giá trị từ session
     * @param string $key
     * @param mixed $default Giá trị mặc định nếu không tồn tại key
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Kiểm tra một key có tồn tại trong session không
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Xóa một key cụ thể khỏi session
     * @param string $key
     */
    public static function remove($key)
    {
        if (self::has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Xử lý Flash Message (Thông báo xuất hiện một lần rồi tự xóa)
     * @param string $key Tên định danh (ví dụ: 'success', 'error')
     * @param string $message Nội dung thông báo (nếu để trống là lấy thông báo ra)
     * @return string|void
     */
    public static function flash($key, $message = '')
    {
        if (!empty($message)) {
            // Ghi dữ liệu vào flash session
            $_SESSION['flash_messages'][$key] = $message;
        } elseif (isset($_SESSION['flash_messages'][$key])) {
            // Lấy dữ liệu ra và xóa ngay lập tức
            $msg = $_SESSION['flash_messages'][$key];
            unset($_SESSION['flash_messages'][$key]);
            return $msg;
        }
        return '';
    }

    /**
     * Xóa sạch dữ liệu và hủy phiên làm việc (Dùng khi Logout)
     */
    public static function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    public static function regenerate()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}

<?php
namespace App\core;

use Exception;

class Config
{
    // nơi lưu các config đã load
    private static $configs = [];

    // hàm load config theo tên file
    public static function load($name)
    {
        // isset kiểm tra có tồn tại
        // nếu đã load rồi thì dừng lại
        if (isset(self::$configs[$name])) {
            return self::$configs[$name];
        }
        // Kiểm tra file config có tồn tại
        $path = CONFIG_PATH . "/{$name}.php";
        if (!file_exists($path)) {
            throw new Exception("Config không tồn tại", 500);
        }
        $config = require $path;
        // kiểm tra có phải là mảng, đúng định dạng
        if (!is_array($config)) {
            throw new Exception("Config không hợp lệ", 500);
        }

        // Lưu mảng cấu hình vừa đọc được vào thuộc tính static $configs.
        // Việc này giúp "cache" lại dữ liệu, nếu lần sau hàm load($name) được gọi lại với cùng tên file,
        // nó sẽ trả về ngay kết quả từ bộ nhớ mà không cần đọc file từ ổ cứng lần nữa.
        self::$configs[$name] = $config;

        // Trả về mảng dữ liệu cấu hình để nơi gọi hàm có thể sử dụng.
        return self::$configs[$name];
    }
}

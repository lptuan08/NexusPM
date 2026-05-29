<?php

namespace App\helpers;

use App\core\Session;

class AuthHelper
{
    // lấy permissions user
    public static function user(): array
    {
        return Session::get('user', []);
    }

    // trả về permissions của user
    public static function permissions(): array
    {
        $user = self::user();
        return is_array($user['permissions'] ?? null) ? $user['permissions'] : [];
    }

    // Kiểm tra $permission ở router có nằm trong permission của user -> trả vể True nếu có
    public static function can(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }

    // nếu không có quyền thì trả về true -> ngược lại can()
    public static function cannot(string $permission): bool
    {
        return !self::can($permission);
    }

    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }
        return false;
    }

    public static function id(): int
    {
        $user = self::user();

        return (int) ($user['id'] ?? 0);
    }
 

    //  sử dụng cho DEBUG
    /**
     * Làm mới dữ liệu Session từ Database
     * Giúp cập nhật quyền hạn ngay lập tức mà không cần đăng nhập lại
     */
    public static function refreshSession(): bool
    {
        $sessionUser = self::user();
        if (empty($sessionUser['id'])) return false;

        // Khởi tạo model thủ công (vì Helper không có phương thức model() như Controller)
        $authModel = new \App\models\AuthModel();
        $userModel = new \App\models\UserModel();

        $user = $userModel->getUserById($sessionUser['id']);
        if (!$user) return false;

        $permissions = $authModel->getPermissionSlugsByRoleId($user['role_id']);

        // Cập nhật lại mảng dữ liệu trong Session
        $sessionUser['name'] = $user['name'];
        $sessionUser['role_id'] = $user['role_id'];
        $sessionUser['role'] = $user['role_slug'];
        $sessionUser['avatar'] = $user['avatar'];
        $sessionUser['permissions'] = $permissions;

        Session::set('user', $sessionUser);
        return true;
    }
}

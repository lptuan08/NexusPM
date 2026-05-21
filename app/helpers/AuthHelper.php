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
}

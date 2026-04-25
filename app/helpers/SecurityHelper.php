<?php
// helpers/SecurityHelper.php
class SecurityHelper
{
    public static function generateToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Hàm tiện ích để chèn vào Form HTML
    public static function csrfInput()
    {
        $token = self::generateToken();
        echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

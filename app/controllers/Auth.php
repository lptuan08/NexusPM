<?php

/**
 * Controller Auth - Xử lý đăng nhập và xác thực
 */
class Auth extends Controller
{
    public function __construct()
    {
        // Không cần load model ở đây nếu chưa xử lý logic DB
    }

    /**
     * Hiển thị giao diện đăng nhập
     */
    public function login()
    {
        $request = new Request();
        if ($request->isPost()) {
            echo "xử lý đăng nhập";



            
        } else {
            View::render('auth/login', [
                'pageTitle' => 'Đăng nhập hệ thống - NexusPM'
            ], null); // Truyền null để không sử dụng layout main (dashboard)
        }
    }

    // Các hàm authenticate, logout sẽ được bổ sung sau
}

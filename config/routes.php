<?php

/**
 * BẢN ĐỒ ĐỊNH TUYẾN (ROUTES MAP)
 * Phân chia theo Method để tối ưu tốc độ và bảo mật.
 */

return [
    // NHÓM CÁC TRANG HIỂN THỊ (Lấy dữ liệu - GET)
    'GET' => [
        // Trang chủ quản trị
        '/' => [
            'controller' => 'DashboardController',
            'action'     => 'index',
            'middleware' => ['AuthMiddleware']
        ],

        // Đăng nhập
        '/login' => [
            'controller' => 'AuthController',
            'action'     => 'login', // Hiển thị form đăng nhập
            'middleware' => []
        ],

        // -----USER-----
        '/users' => [
            'controller' => 'UserController',
            'action'     => 'index',
            'middleware' => ['AuthMiddleware']
        ],

        // Form thêm mới
        '/users/create' => [
            'controller' => 'UserController',
            'action'     => 'create', // Chỉ render view form
            'middleware' => ['AuthMiddleware']
        ],

        // Form chỉnh sửa (Tham số động)
        '/users/{id}/edit' => [
            'controller' => 'UserController',
            'action'     => 'edit',
            'middleware' => ['AuthMiddleware']
        ],

        // Chi tiết người dùng
        '/users/{id}' => [
            'controller' => 'UserController',
            'action'     => 'show',
            'middleware' => ['AuthMiddleware']
        ],
        // -----end USER------
    ],

    // NHÓM CÁC HÀNH ĐỘNG XỬ LÝ (Gửi dữ liệu - POST)
    'POST' => [
        // Xử lý đăng nhập (Khi nhấn nút Submit login)
        '/login' => [
            'controller' => 'AuthController',
            'action'     => 'handleLogin', // Hàm kiểm tra tài khoản/mật khẩu
            'middleware' => []
        ],

        // Đăng xuất (Dùng POST để tránh Googlebot hoặc link rác tự đăng xuất người dùng)
        '/logout' => [
            'controller' => 'AuthController',
            'action'     => 'logout',
            'middleware' => ['AuthMiddleware','VerifyCsrfToken']
        ],

        // Lưu người dùng mới vào Database
        '/users' => [
            'controller' => 'UserController',
            'action'     => 'store', // Hàm thực hiện INSERT DB (form tạo mới sẽ POST đến đây)
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        // Form thêm mới
        '/users/create' => [
            'controller' => 'UserController',
            'action'     => 'store', // Xử lý dữ liệu thêm mới
            'middleware' => ['AuthMiddleware']
        ],

        // Cập nhật người dùng sau khi chỉnh sửa
        '/users/{id}/edit' => [
            'controller' => 'UserController',
            'action'     => 'update', // Hàm thực hiện UPDATE DB
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],

        // Xóa người dùng (Bắt buộc dùng POST để an toàn)
        '/users/{id}/delete' => [
            'controller' => 'UserController',
            'action'     => 'delete', // Hàm thực hiện DELETE DB
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
    ]
];

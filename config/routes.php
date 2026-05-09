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
            'controller' => 'auth/AuthController',
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

        // -----PROJECTS-----
        '/projects' => [
            'controller' => 'ProjectController',
            'action'     => 'index',
            'middleware' => ['AuthMiddleware']
        ],
        '/projects/create' => [
            'controller' => 'ProjectController',
            'action'     => 'create',
            'middleware' => ['AuthMiddleware']
        ],
        '/projects/{id}' => [
            'controller' => 'ProjectController',
            'action'     => 'show',
            'middleware' => ['AuthMiddleware']
        ],
        '/projects/{id}/edit' => [
            'controller' => 'ProjectController',
            'action'     => 'edit',
            'middleware' => ['AuthMiddleware']
        ],

        // -----end USER------

        // Thiết lập hệ thống

        '/settings' => [
            'controller' => 'admin/SettingsController',
            'action' => 'index',
            'middleware' => ['AuthMiddleware'] //bổ sung thêm quyền admin
        ],



        '/settings/project' => [
            'controller' => 'admin/ProjectStatusController',
            'action' => 'list',
            'middleware' => ['AuthMiddleware'] //bổ sung thêm quyền admin
        ],
        '/settings/task' => [
            'controller' => 'admin/TaskStatusController',
            'action' => 'list',
            'middleware' => ['AuthMiddleware'] //bổ sung thêm quyền admin
        ],
        '/settings/job' => [
            'controller' => 'admin/JobController',
            'action' => 'list',
            'middleware' => ['AuthMiddleware'] //bổ sung thêm quyền admin
        ],

        // ROLE
        '/admin/roles' => [
            'controller' => 'admin/RoleController',
            'action' => 'index',
            'middleware' => ['AuthMiddleware']
        ],
        '/admin/roles/{id}/permissions' => [
            'controller' => 'admin/PermissionController',
            'action' => 'RolePermissions',
            'middleware' => ['AuthMiddleware']
        ],



    ],

    // NHÓM CÁC HÀNH ĐỘNG XỬ LÝ (Gửi dữ liệu - POST)
    'POST' => [
        // Xử lý đăng nhập (Khi nhấn nút Submit login)
        '/login' => [
            'controller' => 'auth/AuthController',
            'action'     => 'handleLogin', // Hàm kiểm tra tài khoản/mật khẩu
            'middleware' => []
        ],

        // Đăng xuất (Dùng POST để tránh Googlebot hoặc link rác tự đăng xuất người dùng)
        '/logout' => [
            'controller' => 'auth/AuthController',
            'action'     => 'logout',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],

        // Lưu người dùng mới vào Database
        '/users/create' => [
            'controller' => 'UserController',
            'action'     => 'store', // Hàm thực hiện INSERT DB (form tạo mới sẽ POST đến đây)
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
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

        // -----PROJECTS POST-----
        '/projects/create' => [
            'controller' => 'ProjectController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/projects/{id}/edit' => [
            'controller' => 'ProjectController',
            'action'     => 'update',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/projects/{id}/delete' => [
            'controller' => 'ProjectController',
            'action'     => 'delete',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/projects/{id}/addMembers' => [
            'controller' => 'ProjectController',
            'action'     => 'addMembers',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/project/create' => [
            'controller' => 'admin/ProjectStatusController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/project/{id}/edit' => [
            'controller' => 'admin/ProjectStatusController',
            'action'     => 'update',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/project/reorder' => [
            'controller' => 'admin/ProjectStatusController',
            'action'     => 'reorder',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        // TASK STATUS
        '/settings/task/create' => [
            'controller' => 'admin/TaskStatusController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/task/{id}/edit' => [
            'controller' => 'admin/TaskStatusController',
            'action' => 'edit',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/task/reorder' => [
            'controller' => 'admin/TaskStatusController',
            'action'     => 'reorder',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],

        // JOB
        '/settings/job/create' => [
            'controller' => 'admin/JobController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/job/{id}/edit' => [
            'controller' => 'admin/JobController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/job/{id}/delete' => [
            'controller' => 'admin/JobController',
            'action'     => 'deleted',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],



        //ROLE
        '/admin/roles/create' => [
            'controller' => 'admin/RoleController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        // Cập nhật vai trò
        '/admin/roles/{id}/update' => [
            'controller' => 'admin/RoleController',
            'action'     => 'update',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        // Xóa vai trò
        '/admin/roles/{id}/delete' => [
            'controller' => 'admin/RoleController',
            'action'     => 'delete',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],

        '/admin/roles/{id}/permissions' => [
            'controller' => 'admin/PermissionController',
            'action' => 'RolePermissionsEdit',
            'middleware' => ['AuthMiddleware']
        ],




    ]
];

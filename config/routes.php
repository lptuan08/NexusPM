<?php

/**
 * BẢN ĐỒ ĐỊNH TUYẾN (ROUTES MAP)
 * Phân chia theo Method để tối ưu tốc độ và bảo mật.
 */

return [
    // NHÓM CÁC TRANG HIỂN THỊ (Lấy dữ liệu - GET)
    'GET' => [
        // --- Dashboard ---
        '/' => [
            'controller' => 'DashboardController',
            'action'     => 'index',
            'middleware' => ['AuthMiddleware']
        ],

        // --- Authentication ---
        '/login' => [
            'controller' => 'auth/AuthController',
            'action'     => 'login',
            'middleware' => []
        ],

        // --- User Management ---
        '/users' => [
            'controller' => 'UserController',
            'action'     => 'index',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', ['users.view.all']]
            ]
        ],
        '/users/create' => [
            'controller' => 'UserController',
            'action'     => 'create',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', ['users.create.all']]
            ]
        ],
        '/users/{id}/edit' => [
            'controller' => 'UserController',
            'action'     => 'edit',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', ['users.update.all']]
            ]
        ],
        '/users/{id}' => [
            'controller' => 'UserController',
            'action'     => 'show',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', ['users.view.all']]
            ]
        ],

        // --- Project Management ---

        '/projects' => [
            'controller' => 'ProjectController',
            'action'     => 'index',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'projects.view.joined',
                    'projects.view.all'
                ]]
            ]
        ],
        '/projects/create' => [
            'controller' => 'ProjectController',
            'action'     => 'create',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', ['projects.create.all']]
            ]
        ],
        '/projects/createWizard' => [
            'controller' => 'ProjectController',
            'action'     => 'showStep',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', ['projects.create.all']]
            ]
        ],


        '/projects/{id}' => [
            'controller' => 'ProjectController',
            'action'     => 'show',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'projects.view.joined',
                    'projects.view.all'
                ]]
            ]
        ],
        '/projects/{id}/edit' => [
            'controller' => 'ProjectController',
            'action'     => 'edit',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'projects.update.joined',
                    'projects.update.all'
                ]]
            ]
        ],

        // --- API Routes ---
        // '/api/projects' => [
        //     'controller' => 'api/ApiController',
        //     'action'     => 'projects',
        //     'middleware' => [] // Để trống middleware để bạn có thể test nhanh qua trình duyệt
        // ],

        '/api/projects/wizard-data' => [
            'controller' => 'api/ProjectApiController',
            'action'     => 'getWizardData',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', ['projects.create.all']]
            ]
        ],

        // --- Task Management ---
        '/tasks' => [
            'controller' => 'TaskController',
            'action'     => 'index',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'tasks.view.project',
                    'tasks.view.own',
                    'tasks.view.all'
                ]]
            ]
        ],
        '/tasks/list' => [
            'controller' => 'TaskController',
            'action'     => 'index',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'tasks.view.project',
                    'tasks.view.own',
                    'tasks.view.all'
                ]]
            ]
        ],
        '/tasks/create' => [
            'controller' => 'TaskController',
            'action'     => 'create',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'tasks.create.project',
                    'tasks.create.all'
                ]]
            ]
        ],
        '/tasks/{id}/edit' => [
            'controller' => 'TaskController',
            'action'     => 'edit',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'tasks.update.project',
                    'tasks.update.own',
                    'tasks.update.all'
                ]]
            ]
        ],
        '/tasks/{id}' => [
            'controller' => 'TaskController',
            'action'     => 'show',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'tasks.view.project',
                    'tasks.view.own',
                    'tasks.view.all'
                ]]
            ]
        ],
        '/tasks/{id}/list' => [
            'controller' => 'TaskController',
            'action'     => 'listIdProject',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'tasks.view.project',
                    'tasks.view.own',
                    'tasks.view.all'
                ]]
            ]
        ],
        '/tasks/{id}/kanban' => [
            'controller' => 'TaskController',
            'action'     => 'kanban',
            'middleware' => [
                'AuthMiddleware',
                ['PermissionMiddleware', [
                    'tasks.view.project',
                    'tasks.view.all'
                ]]
            ]
        ],

        // --- Admin Settings ---
        '/settings' => [
            'controller' => 'admin/SettingsController',
            'action' => 'index',
            'middleware' => ['AuthMiddleware']
        ],
        '/settings/project' => [
            'controller' => 'admin/ProjectStatusController',
            'action' => 'list',
            'middleware' => ['AuthMiddleware']
        ],
        '/settings/task' => [
            'controller' => 'admin/TaskStatusController',
            'action' => 'list',
            'middleware' => ['AuthMiddleware']
        ],
        '/settings/job' => [
            'controller' => 'admin/JobController',
            'action' => 'list',
            'middleware' => ['AuthMiddleware']
        ],
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
        // --- Authentication ---
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

        // --- User Management ---
        '/users/create' => [
            'controller' => 'UserController',
            'action'     => 'store',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', ['users.create.all']]
            ]
        ],
        '/users/{id}/edit' => [
            'controller' => 'UserController',
            'action'     => 'update',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', ['users.update.all']]
            ]
        ],
        '/users/{id}/delete' => [
            'controller' => 'UserController',
            'action'     => 'delete',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', ['users.delete.all']]
            ]
        ],

        // --- Project Management ---
        '/projects/create' => [
            'controller' => 'ProjectController',
            'action'     => 'store',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', ['projects.create.all']]
            ]
        ],
        '/projects/createWizard' => [
            'controller' => 'ProjectController',
            'action'     => 'postStep',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', ['projects.create.all']]
            ]
        ],
        '/projects/{id}/edit' => [
            'controller' => 'ProjectController',
            'action'     => 'update',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', [
                    'projects.update.joined',
                    'projects.update.all'
                ]]
            ]
        ],
        '/projects/{id}/delete' => [
            'controller' => 'ProjectController',
            'action'     => 'delete',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', ['projects.delete.all']]
            ]
        ],
        '/projects/{id}/addMembers' => [
            'controller' => 'ProjectController',
            'action'     => 'addMembers',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', [
                    'projects.update.joined',
                    'projects.update.all'
                ]]
            ]
        ],

        // --- Admin Settings: Project Status ---
        '/settings/project/create' => [
            'controller' => 'admin/ProjectStatusController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/project/reorder' => [
            'controller' => 'admin/ProjectStatusController',
            'action'     => 'reorder',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/project/{id}/edit' => [
            'controller' => 'admin/ProjectStatusController',
            'action'     => 'update',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/project/{id}/delete' => [
            'controller' => 'admin/ProjectStatusController',
            'action'     => 'delete',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],

        // --- Admin Settings: Task Status ---
        '/settings/task/create' => [
            'controller' => 'admin/TaskStatusController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/task/reorder' => [
            'controller' => 'admin/TaskStatusController',
            'action'     => 'reorder',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/task/{id}/edit' => [
            'controller' => 'admin/TaskStatusController',
            'action' => 'edit',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/settings/task/{id}/delete' => [
            'controller' => 'admin/TaskStatusController',
            'action'     => 'delete',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],

        // --- Admin Settings: Job Titles ---
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

        // --- Admin Settings: Roles & Permissions ---
        '/admin/roles/create' => [
            'controller' => 'admin/RoleController',
            'action'     => 'store',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/admin/roles/{id}/update' => [
            'controller' => 'admin/RoleController',
            'action'     => 'update',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/admin/roles/{id}/delete' => [
            'controller' => 'admin/RoleController',
            'action'     => 'delete',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],
        '/admin/roles/{id}/permissions' => [
            'controller' => 'admin/PermissionController',
            'action' => 'RolePermissionsEdit',
            'middleware' => ['AuthMiddleware', 'VerifyCsrfToken']
        ],

        // --- Task Management ---
        '/tasks/store' => [
            'controller' => 'TaskController',
            'action'     => 'store',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', [
                    'tasks.create.project',
                    'tasks.create.all'
                ]]
            ]
        ],
        '/tasks/{id}/edit' => [
            'controller' => 'TaskController',
            'action'     => 'update',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', [
                    'tasks.update.project',
                    'tasks.update.own',
                    'tasks.update.all'
                ]]
            ]
        ],
        '/tasks/{id}/delete' => [
            'controller' => 'TaskController',
            'action'     => 'delete',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', [
                    'tasks.delete.project',
                    'tasks.delete.all'
                ]]
            ]
        ],
        '/tasks/update-status' => [
            'controller' => 'TaskController',
            'action'     => 'updateStatus',
            'middleware' => [
                'AuthMiddleware',
                'VerifyCsrfToken',
                ['PermissionMiddleware', [
                    'tasks.update.project',
                    'tasks.update.own',
                    'tasks.update.all'
                ]]
            ]
        ],
    ]
];

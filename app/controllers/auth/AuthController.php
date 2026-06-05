<?php

namespace App\controllers\auth;

use App\core\Controller;
use App\core\View;
use App\core\Session;
use App\core\Response;
use App\helpers\SecurityHelper;

/**
 * Controller Auth - Xử lý đăng nhập và xác thực
 */
class AuthController extends Controller
{
    protected $authModel;

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
    public function __construct()
    {
        parent::__construct();
        $this->authModel = $this->model('AuthModel');
    }

    /**
     * Hiển thị giao diện đăng nhập
     */
    public function login()
    {
        View::render('auth/login', [
            'pageTitle' => 'Đăng nhập hệ thống - NexusPM'

        ], null); // Truyền null để không sử dụng layout main (dashboard)

    }
    /**
     * =============================================================
     * NHOM XU LY DANG NHAP
     * =============================================================
     */
    public function handleLogin()
    {
        if ($this->request->isPost()) {
            $user = [];
            $errors = [];
            // Xử lý đang nhập ở đây
            // - lấy dữ liệu POST, validate Email (đúng định dạng);
            // - gửi email lên model kiểm tra tồn tại
            // -> nếu tồn tại -> trả về thông tin user với mk mã hóa
            // -> password_verify() để so khớp mật khẩu
            // Sửa lại từ $this->request thành $request vì bạn khởi tạo biến cục bộ ở trên
            $body = $this->request->getBody();
            // lấy email -> body['email'];
            $email = $body['email'] ?? '';
            $password = $body['password'] ?? '';

            // validate email
            if ($this->validator->required('email', $email, 'Email')) {
                $this->validator->email('email', $email, 'Email'); // true|false
            }
            // validate password
            if ($this->validator->required('password', $password, 'Mật khẩu')) {
                $this->validator->min('password', $password, 3, 'Mật khẩu');
            }
            // validate ok
            if ($this->validator->passes()) {
                // truy vấn dữ liệu kiểm tra email
                $user = $this->authModel->findEmailUser($email);

                if (!empty($user)) {
                    // Kiểm tra tài khoản kích hoạt
                    if ($user['is_active'] == 1) {
                        // Kiểm tra mật khẩu 
                        if (password_verify($password, $user['password'])) {
                            // khởi tạo session & chuyển hướng -> trang chủ
                            $this->initSession($user);
                        } else {
                            $errors['password'] = "Mật khẩu chưa chính xác";
                        } // end check password                     
                    } else {
                        $errors['status'] = 'Tài khoản của bạn chưa được kích hoạt hoặc bị xóa';
                    } // end check kích hoạt
                } else {
                    $errors['email'] = "Email không tồn tại";
                } //end check email
            } else {
                $errors = $this->validator->getErrors();
            }

            // nếu error[] khác rỗng đăng nhập không thành công
            if (!empty($errors)) {
                return View::render('auth/login', [
                    'pageTitle' => 'Đăng nhập hệ thống - NexusPM',
                    // 'errors' => $errors, // Đã được xử lý trong Validator
                    'errors' => $errors,
                    'old' => $body
                ], null);
            }
        }
    }

    /**
     * =============================================================
     * NHOM QUAN LY PHIEN DANG NHAP
     * =============================================================
     */
    public function initSession($user)
    {

        // 1. Xóa sạch dữ liệu session cũ (guest data) nếu có
        // Session::destroy();

        // 2. Làm mới ID phiên làm việc (Built-in function)
        // Việc này giúp chống Session Fixation cực tốt
        Session::regenerate();

        // 3. Lưu thông tin người dùng vào mảng
        // Mẹo nhỏ: Cậu có thể gom vào một mảng 'user' để $_SESSION trông gọn hơn

        $permissions = $this->authModel->getPermissionSlugsByRoleId($user['role_id']);

        Session::set('user', [
            'id'     => $user['id'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'role_id' => $user['role_id'],
            'role'   => $user['role_slug'], // Sử dụng slug để kiểm tra quyền trong code
            'avatar' => $user['avatar'],
            'permissions' => $permissions
        ]);
        Session::set('is_logged_in', true);
        // lưu thời điểm đăng nhập
        $now = time();
        Session::set('login_at', $now);
        Session::set('last_activity', $now);

        // 4. Khởi tạo CSRF Token mới tinh cho phiên đăng nhập này
        // Sử dụng SecurityHelper mà chúng ta đã build ở trên
        SecurityHelper::generateToken();

        // 5. Điều hướng về trang đầu tiên user có quyền truy cập
        Response::redirect(URLROOT . $this->resolveHomePath($permissions));
        return; // Đảm bảo không có code nào được thực thi sau khi chuyển hướng
    }

    /**
     * Chọn trang mặc định sau đăng nhập theo quyền hiện có của user.
     *
     * @param array<int, string> $permissions
     */
    private function resolveHomePath(array $permissions): string
    {
        $canAny = static function (array $required) use ($permissions): bool {
            return !empty(array_intersect($required, $permissions));
        };

        if ($canAny(['dashboard.view.all', 'dashboard.view.own'])) {
            return '/';
        }

        if ($canAny(['projects.view.all', 'projects.view.joined'])) {
            return '/projects';
        }

        if ($canAny(['tasks.project', 'tasks.view.all', 'tasks.view.own'])) {
            return '/tasks';
        }

        if ($canAny([
            'settings.view.all',
            'users.view.all',
            'job_titles.view.all',
            'project_statuses.view.all',
            'task_statuses.view.all',
            'roles.view.all',
            'roles.update_permissions.all'
        ])) {
            return '/settings';
        }

        return '/account/password';
    }

    /**
     * =============================================================
     * NHOM DANG XUAT
     * =============================================================
     */
    public function logout()
    {
        // Sử dụng phương thức destroy tập trung để xóa sạch dữ liệu và cookie session
        Session::destroy();

        // 5. Chuyển hướng
        Response::redirect(URLROOT . '/login');
    }
}

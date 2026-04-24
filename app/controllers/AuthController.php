<?php

/**
 * Controller Auth - Xử lý đăng nhập và xác thực
 */
class AuthController extends Controller
{
    protected $authModel;

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
    public function handleLogin()
    {
        if ($this->request->isPost()) {
            var_dump("handle Login được chạy");
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
                    'errors' => $errors,
                    'old' => $body
                ], null);
            }
        }
    }

    public function initSession($user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_avatar'] = $user['avatar'];
        $_SESSION['is_logged_in'] = true;

        Response::redirect(URLROOT . '/');
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Xóa toàn bộ dữ liệu session
        $_SESSION = [];
        // Hủy session cookie nếu có
        // if (ini_get("session.use_cookies")) {
        //     $params = session_get_cookie_params();
        //     setcookie(
        //         session_name(),
        //         '',
        //         time() - 42000,
        //         $params["path"],
        //         $params["domain"],
        //         $params["secure"],
        //         $params["httponly"]
        //     );
        // }

        // Hủy session trên server
        session_destroy();

        // Chuyển hướng về trang đăng nhập
        Response::redirect(URLROOT . '/login');
    }
}

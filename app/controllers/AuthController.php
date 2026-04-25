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
            // var_dump("handle Login được chạy"); // Debug statement, nên xóa trong production
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

    public function initSession($user)
    {
        // 1. Xóa sạch dữ liệu session cũ (guest data) nếu có
        session_unset();

        // 2. Làm mới ID phiên làm việc (Built-in function)
        // Việc này giúp chống Session Fixation cực tốt
        session_regenerate_id(true);

        // 3. Lưu thông tin người dùng vào mảng
        // Mẹo nhỏ: Cậu có thể gom vào một mảng 'user' để $_SESSION trông gọn hơn
        $_SESSION['user'] = [
            'id'     => $user['id'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'role'   => $user['role'],
            'avatar' => $user['avatar']
        ];
        $_SESSION['is_logged_in'] = true;

        // 4. Khởi tạo CSRF Token mới tinh cho phiên đăng nhập này
        // Sử dụng SecurityHelper mà chúng ta đã build ở trên
        SecurityHelper::generateToken();

        // 5. Điều hướng về trang chủ
        Response::redirect(URLROOT . '/');
        return; // Đảm bảo không có code nào được thực thi sau khi chuyển hướng
    }

    public function logout()
    {
        // 1. Đảm bảo session đã được khởi động để có cái mà hủy
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. Xóa sạch dữ liệu trong mảng $_SESSION
        $_SESSION = [];

        // 3. Xóa Cookie của Session trên trình duyệt người dùng
        // Đây là bước cực kỳ quan trọng để "vệ sinh" hoàn toàn
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000, // Đặt thời gian hết hạn về quá khứ
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // 4. Hủy session trên server
        session_destroy();

        // 5. Chuyển hướng
        Response::redirect(URLROOT . '/login');
    }
}

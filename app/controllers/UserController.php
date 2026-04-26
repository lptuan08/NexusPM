<?php

/**
 * Controller Users - Quản lý các hành động liên quan đến nhân sự
 */
class UserController extends Controller
{
    private $modelUser;
    public function __construct()
    {
        parent::__construct();
        $this->modelUser = $this->model('UserModel');
    }

    // =========================================================================
    // 1. NHÓM HIỂN THỊ (READ)
    // =========================================================================

    /**
     * Hiển thị danh sách toàn bộ nhân viên
     */
    public function index()
    {
        $perPage = 5; //số bản ghi mỗi trang

        $currentPage = (int)$this->request->input('page', 1);
        if ($currentPage < 1) $currentPage = 1;

        $users = $this->modelUser->getUserByPage($currentPage, $perPage);
        $totalUsers = $this->modelUser->count();
        $totalPages = ceil($totalUsers / $perPage);
        $pages = range(1, $totalPages);
        View::render('users/list', [
            'data' => $users,
            'pageTitle' => 'Nhân viên',
            'extra_css' => 'users',
            'totalUsers' => $totalUsers,
            'perPage' => $perPage,
            'currentPage' => $currentPage,
            'totalPage' => $totalPages,
            'pages' => $pages
            // 'extra_js' => 'user_list'
        ]);
    }

    /**
     * Hiển thị chi tiết hồ sơ nhân viên
     */
    public function show($id)
    {
        $user = $this->modelUser->getUserById($id);
        $projectUser = $this->modelUser->getUserProjects($id);
        $taskUser = $this->modelUser->getUserTasks($id);

        if (!$user) {
            Response::redirect(URLROOT . '/users');
        }

        View::render('users/detail', [
            'user' => $user,
            'projects' => $projectUser,
            'tasks' => $taskUser,
            'extra_css' => 'users',
            'pageTitle' => 'Hồ sơ chi tiết - ' . $user['name']
        ]);
    }

    // =========================================================================
    // 2. NHÓM THÊM MỚI (CREATE)
    // =========================================================================

    // Load trang thêm mới
    public function create()
    {
        $jobTitle = $this->modelUser->getJobTitle();

        View::render('users/create', [
            'job_titles' => $jobTitle,
            'extra_css' => 'users',
            'pageTitle' => 'Thêm nhân viên mới', // Tiêu đề trang
            'action_url' => URLROOT . '/users/create'
        ]);
    }

    // Thực hiện thêm mới
    public function store() //->create - POST
    {
        if ($this->request->isPost()) {
            $data = $this->getFormData();
            $validator = new Validator();

            // $validator->required('employee_code', $data['employee_code'], 'Mã nhân viên');
            $validator->required('name', $data['name'], 'Họ tên');
            $validator->required('email', $data['email'], 'Email');
            $validator->email('email', $data['email'], 'Email');
            $validator->required('role', $data['role'], 'Quyền hạn');
            $validator->required('job_title_id', $data['job_title_id'], 'Chức danh');
            $validator->image('avatar', 'Ảnh đại diện');
            $validator->required('password', $data['password'], 'Password');
            $validator->min('password', $data['password'], 4);

            // Kiểm tra email trùng lặp
            if (!empty($data['email']) && $this->modelUser->isEmailExists($data['email'])) {
                $validator->addError('email', 'Email này đã tồn tại trên hệ thống');
            }

            if (!$validator->passes()) {
                return View::render('users/create', [
                    'job_titles' => $this->modelUser->getJobTitle(),
                    'errors' => $validator->getErrors(),
                    'old' => $this->request->getBody(),
                    'extra_css' => 'users',
                    'pageTitle' => 'Thêm nhân viên mới',
                    'action_url' => URLROOT . '/users' // Form tạo mới sẽ POST đến /users
                ]);
            }

            $avatar = $this->uploadAvatar();
            if ($avatar) $data['avatar'] = $avatar;

            // Sử dụng phương thức tập trung có Transaction
            $this->modelUser->createWithEmployeeCode($data);
            Helper::setFlash('success', 'Thêm nhân viên mới thành công!');
            Response::redirect(URLROOT . '/users');
        }
    }

    // =========================================================================
    // 3. NHÓM CẬP NHẬT (UPDATE)
    // =========================================================================

    // Load trang hiển thị edit user 
    public function edit($id)
    {
        $user = $this->modelUser->getUserById($id);
        $job_titles = $this->modelUser->getJobTitle();
        if (!$user) Response::redirect(URLROOT . '/users');

        View::render('users/create', [
            'user' => $user,
            'job_titles' => $job_titles,
            'extra_css' => 'users',
            'pageTitle' => 'Chỉnh sửa nhân viên',
            'action_url' => URLROOT . "/users/{$id}/edit" // URL cho form cập nhật
        ]);
    }



    // Thực hiện cập nhật user
    public function update($id) // Edit - POST
    {
        // 1. Kiểm tra sự tồn tại của nhân viên
        $user = $this->modelUser->getUserById($id);
        if (!$user) {
            Response::redirect(URLROOT . '/users');
        }

        if ($this->request->isPost()) {
            // 2. Lấy dữ liệu và validate
            $data = $this->getFormData(true);
            $validator = new Validator();

            $validator->required('name', $data['name'], 'Họ tên');
            $validator->required('email', $data['email'], 'Email');
            $validator->email('email', $data['email'], 'Email');
            $validator->required('role', $data['role'], 'Quyền hạn');
            $validator->required('job_title_id', $data['job_title_id'], 'Chức danh');
            $validator->image('avatar', 'Ảnh đại diện');

            // Kiểm tra email trùng lặp (trừ user hiện tại)
            if (!empty($data['email']) && $this->modelUser->isEmailExists($data['email'], $id)) {
                $validator->addError('email', 'Email này đã được sử dụng bởi nhân viên khác');
            }

            if (!$validator->passes()) {
                return View::render('users/create', [
                    'user'       => $user, // Truyền thông tin user cũ để form hiển thị đúng
                    'job_titles' => $this->modelUser->getJobTitle(),
                    'errors'     => $validator->getErrors(),
                    'old'        => $this->request->getBody(),
                    'extra_css'  => 'users',
                    'pageTitle'      => 'Chỉnh sửa nhân viên',
                    'action_url' => URLROOT . "/users/{$id}/edit" // Đảm bảo $id được truyền đúng
                ]);
            }

            // 3. Xử lý ảnh đại diện và xóa ảnh cũ
            $avatar = $this->uploadAvatar();
            if ($avatar) {
                if (!empty($user['avatar'])) {
                    $oldPath = APPROOT . '/public/uploads/avatars/' . $user['avatar'];
                    if (file_exists($oldPath)) @unlink($oldPath);
                }
                $data['avatar'] = $avatar;
            }

            // 4. Cập nhật vào DB và chuyển hướng
            $this->modelUser->updateUser($id, $data);
            Helper::setFlash('success', 'Thông tin nhân viên đã được cập nhật');
            Response::redirect(URLROOT . "/users/$id");
        }
    }
    // Xóa nhân viên
    public function delete($id)
    {
        $result = $this->modelUser->delete($id);
        Helper::setFlash('success', 'Xóa nhân viên thành công');
        Response::redirect(URLROOT . '/users');
    }

    /**
     * TÁCH RIÊNG: Logic lấy và chuẩn hóa dữ liệu từ $_POST
     * @param bool $isUpdate Xác định xem là thêm mới hay cập nhật
     */
    private function getFormData($isUpdate = false) //false = tao mới
    {
        $body = $this->request->getBody();

        $data = [
            'name'          => $body['name'] ?? '',
            'email'         => $body['email'] ?? '',
            'role'          => $body['role'] ?? 'member',
            'job_title_id'  => $body['job_title_id'] ?? null,
            'is_active'     => isset($body['is_active']) ? 1 : 0,
        ];

        // Xử lý mật khẩu: 
        // - Nếu thêm mới: Bắt buộc hash (hoặc dùng mặc định)
        if (!$isUpdate) {
            $password = !empty($body['password']) ? $body['password'] : '123456';
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        // - Nếu cập nhật: Chỉ hash nếu người dùng có nhập mật khẩu mới
        else {
            if (!empty($body['password'])) {
                $data['password'] = password_hash($body['password'], PASSWORD_DEFAULT);
            }
        }

        return $data;
    }

    /**
     * TÁCH RIÊNG: Logic xử lý upload file ảnh đại diện
     * @return string|null Trả về tên file nếu thành công, ngược lại null
     */
    private function uploadAvatar()
    {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = APPROOT . '/public/uploads/avatars/'; // Đường dẫn vật lý đến thư mục lưu trữ
            // var_dump($uploadDir); // Debug statement, nên xóa trong production
            // Tự động tạo thư mục nếu chưa tồn tại
            if (!is_dir($uploadDir)) {
                // Sử dụng quyền 0755 để bảo mật hơn 0777
                if (!mkdir($uploadDir, 0755, true)) {
                    return null; // Không tạo được thư mục (có thể do quyền ghi)
                }
                // $uploadDir: Đường dẫn thư mục cần tạo.
                // 0777: Cấp quyền đọc/ghi/thực thi cao nhất cho thư mục (phổ biến trên Linux).
                // true: Cho phép tạo thư mục lồng nhau (ví dụ tạo luôn 'uploads' nếu chưa có, sau đó mới tạo 'avatars').

            }

            // Kiểm tra định dạng file
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileExtension = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            // - pathinfo(): Hàm lấy thông tin về đường dẫn file.
            // - PATHINFO_EXTENSION: Hằng số yêu cầu hàm chỉ trả về phần mở rộng (đuôi file).
            // - strtolower(): Chuyển đuôi file về chữ thường (ví dụ: .JPG -> .jpg) để so khớp chính xác.
            if (in_array($fileExtension, $allowedExtensions)) {
                // Tạo tên file duy nhất để không bị trùng
                $fileName = 'avatar_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;

                // Thay vì di chuyển trực tiếp, ta gọi hàm resize để tối ưu ảnh trước khi lưu
                if ($this->resizeImage($_FILES['avatar']['tmp_name'], $targetPath, $fileExtension)) {

                    return $fileName;
                }
            }
        }

        return null;
    }

    /**
     * Tối ưu ảnh: Thay đổi kích thước và nén ảnh trước khi lưu vào server
     * @param string $tmpName Đường dẫn file tạm của PHP
     * @param string $targetPath Đường dẫn đích để lưu file
     * @param string $extension Định dạng file (jpg, png, gif)
     * @return bool Trả về true nếu xử lý thành công
     */
    private function resizeImage($tmpName, $targetPath, $extension)
    {
        $result = false;
        // Kiểm tra thư viện GD có được bật không
        if (!extension_loaded('gd')) {
            return $result;
        }

        // 1. Lấy kích thước thực tế của ảnh gốc
        list($width, $height) = getimagesize($tmpName);
        // 2. Thiết lập kích thước chuẩn tối ưu (Avatar thường là 400x400)
        $maxWidth = 400;
        $maxHeight = 400;

        // 3. Tính toán tỷ lệ để không làm biến dạng (méo) ảnh
        // $ratio là tỷ lệ khung hình (Aspect Ratio) của ảnh gốc.
        // Ví dụ: Ảnh 1920x1080 sẽ có ratio = 1.77 (Landscape - Ảnh ngang)
        //       Ảnh 1080x1920 sẽ có ratio = 0.56 (Portrait - Ảnh dọc)
        $ratio = $width / $height;

        if ($maxWidth / $maxHeight > $ratio) {
            // Trường hợp ảnh dọc: Ta ưu tiên cố định Chiều cao là 400px
            // Sau đó tính Chiều rộng dựa trên Chiều cao mới * Tỷ lệ gốc
            $newWidth = (int)($maxHeight * $ratio);
            $newHeight = $maxHeight;
        } else {
            // Ngược lại, nếu ảnh gốc có xu hướng "ngang" hơn hoặc là hình vuông (ratio >= 1)
            // Ta ưu tiên cố định Chiều rộng là 400px
            // Sau đó tính Chiều cao dựa trên Chiều rộng mới / Tỷ lệ gốc
            $newWidth = $maxWidth;
            $newHeight = (int)($maxWidth / $ratio);
        }
        // Kết quả: $newWidth và $newHeight luôn <= 400 và không làm ảnh bị "lùn đi" hay "gầy đi".

        $srcImage = null;
        if ($extension === 'jpg' || $extension === 'jpeg') {
            $srcImage = imagecreatefromjpeg($tmpName);
        } elseif ($extension === 'png') {
            $srcImage = imagecreatefrompng($tmpName);
        } elseif ($extension === 'gif') {
            $srcImage = imagecreatefromgif($tmpName);
        } elseif ($extension === 'webp') {
            $srcImage = imagecreatefromwebp($tmpName);
        }

        if (!$srcImage) return false;

        // Tạo một khung ảnh trống mới với kích thước đã tính toán
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        // Đoạn lưu ảnh vào sever

        if ($extension === 'jpg' || $extension === 'jpeg') $result = imagejpeg($dstImage, $targetPath, 85);
        elseif ($extension === 'png') $result = imagepng($dstImage, $targetPath);
        elseif ($extension === 'gif') $result = imagegif($dstImage, $targetPath);
        elseif ($extension === 'webp') $result = imagewebp($dstImage, $targetPath, 85);

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return $result; // 

    }
}

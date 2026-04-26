<?php
class Controller
{
    protected $request;
    protected $validator;
    public function __construct()
    {
        $this->request = new Request();
        $this->validator = new Validator();
    }

    public function model($name)
    {
        $model = false;
        $path = APP_PATH . "/models/{$name}.php";
        if (file_exists($path)) {
            require_once $path;
            if (class_exists($name)) {
                $model = new $name();
            } else {
                throw new Exception("class model {$name} không tồn tại", 500);
            }
        } else {
            throw new Exception("File model {$path} không tồn tại", 500);
        }

        return $model;
    }
    public function getEllipsisPagination($currentPage, $totalPages, $delta = '4')
    {
        // có delta: sẽ phát triển sau
    }

    // check login Kiểm tra đăng nhập
    public function checkLogin()
    {
        if (!Session::get('is_logged_in')) {
            Response::redirect(URLROOT . '/login'); // Cập nhật URL đăng nhập mới
        }
    }

}

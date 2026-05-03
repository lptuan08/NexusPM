<?php
namespace App\core;

use App\core\Request;
use App\core\Validator;
use App\core\Session;
use App\core\Response;
use Exception;

class Controller
{
    protected $request;
    protected $validator;
    protected $a = 'ssss';
    public function __construct()
    {
        $this->request = new Request();
        $this->validator = new Validator();
    }

    public function model($name)
    {
        $className = "App\\models\\" . $name;
        if (class_exists($className)) {
                return new $className();
            } else {
                throw new Exception("class model {$name} không tồn tại", 500);
            }
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

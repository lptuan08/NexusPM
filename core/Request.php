<?php
class Request
{
    public function getMethod()
    {
        // Input vào → chuẩn hoá ngay từ đầu → toàn hệ thống dùng 1 format (chữ thường) 
        return strtolower($_SERVER['REQUEST_METHOD']);
    }
    public function isPost()
    {
        if ($this->getMethod() == 'post') {
            return true;
        }

        return false;
    }

    public function isGet()
    {
        if ($this->getMethod() == 'get') {
            return true;
        }

        return false;
    }
    
    
}

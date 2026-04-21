<?php
class Controller
{
    public function model($name)
    {
        $model = false;
        $path = APP_PATH . "/models/{$name}.php";
        if (file_exists($path)) {
            require_once $path;
            if (class_exists($name)) {
                $model = new $name();
            }else{
                throw new Exception("class model {$name} không tồn tại", 500);
            }
        }else{
            throw new Exception("File model {$path} không tồn tại", 500);
        }

        return $model;
    }

    
}

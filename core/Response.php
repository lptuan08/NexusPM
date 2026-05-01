<?php
namespace App\core;

class Response {
    public static function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function redirect($url) {
        header("Location: $url");
        exit;
    }
}
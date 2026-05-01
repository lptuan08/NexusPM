<?php
namespace App\controllers\admin;

use App\core\Controller;
use App\core\View;

class SettingsController extends Controller{
    public function __construct()
    {
    }
    public function index(){
        echo "heheheh";
    }
    public function projectSetting()
    {
        // Dữ liệu mẫu để hiển thị và kiểm tra giao diện kéo thả
        $statuses = [
            ['id' => 1, 'name' => 'Lên kế hoạch', 'slug' => 'planning', 'color' => '#3b82f6', 'position' => 0],
            ['id' => 2, 'name' => 'Đang thực hiện', 'slug' => 'active', 'color' => '#10b981', 'position' => 1],
            ['id' => 3, 'name' => 'Tạm dừng', 'slug' => 'on_hold', 'color' => '#f59e0b', 'position' => 2],
            ['id' => 4, 'name' => 'Hoàn thành', 'slug' => 'completed', 'color' => '#6366f1', 'position' => 3],
        ];

        View::render('admin/settings/project_status', [
            'statuses' => $statuses
        ]);
    }
}
<?php

namespace App\controllers\admin;

use App\core\Controller;
use App\core\Request;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;


class TaskSettingController extends Controller
{
    protected $TaskSettingModel;
    public function __construct()
    {
        parent::__construct();
        $this->TaskSettingModel = $this->model('TaskSettingModel');
    }
    public function list()
    {
        // Lấy project_id từ query string (?project_id=...)
        $projectId = $this->request->input('project_id');
        
        // Chuyển sang kiểu int nếu có giá trị, nếu không để null để xử lý "Toàn hệ thống"
        $projectId = ($projectId !== null && $projectId !== '') ? (int)$projectId : null;

        $projects = $this->TaskSettingModel->listProject();
        $statuses = $this->TaskSettingModel->getStatuses($projectId);

        View::render('admin/settings/task_status',[
            'projects'  => $projects,
            'projectId' => $projectId,
            'statuses'  => $statuses
        ]);
    }
}

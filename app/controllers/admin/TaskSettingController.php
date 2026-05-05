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

        View::render('admin/settings/task_status', [
            'projects'  => $projects,
            'projectId' => $projectId,
            'statuses'  => $statuses
        ]);
    }
    public function store()
    {
        $body = $this->request->getBody();
        $this->validator->required('name', $body['name'], 'Tên trạng thái');
        $this->validator->required('slug', $body['slug'], "Slug");
        $this->validator->color('color', $body['color'] ?? '#3b82f6');

        $projectId = !empty($body['project_id']) ? (int)$body['project_id'] : null;
        $checkSlug = $this->TaskSettingModel->isSlugExists($body['slug'], $projectId);
        if ($checkSlug) {
            $this->validator->addError('slug', "Mã định danh (slug) đã tồn tại trong phạm vi này");
        }
        if (!$this->validator->passes()) {
            $projects = $this->TaskSettingModel->listProject();
            $statuses = $this->TaskSettingModel->getStatuses($projectId);

            return View::render('admin/settings/task_status', [
                'projects'  => $projects,
                'projectId' => $projectId,
                'statuses'  => $statuses,
                'errors'    => $this->validator->getErrors(),
                'old'       => $body
            ]);
        }
        $statusCommit = $this->TaskSettingModel->add($body, $projectId);

        Helper::setFlash('success', 'Thêm trạng thái công việc thành công!');
        if (!empty($projectId)) {
            $url = '/settings/task?project_id=' . $projectId;
        } else {
            $url = '/settings/task';
        }
        Response::redirect(URLROOT . $url);
    }
}

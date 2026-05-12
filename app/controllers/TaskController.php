<?php

namespace App\controllers;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
use App\models\TaskModel;
use App\models\ProjectModel;
use App\models\UserModel;
use App\models\TaskStatusModel;

/**
 * @property \App\core\Request $request
 */
class TaskController extends Controller
{
    private TaskModel $taskModel;
    private ProjectModel $projectModel;
    private UserModel $userModel;
    private TaskStatusModel $statusModel;

    public function __construct()
    {
        parent::__construct();
        $this->taskModel = $this->model('TaskModel');
        $this->projectModel = $this->model('ProjectModel');
        $this->userModel = $this->model('UserModel');
        $this->statusModel = $this->model('TaskStatusModel');
    }
    public function index()
    {
        $query = $this->request->getBody();

        // Thu thập các tham số lọc từ Request
        $filters = [
            'search'      => $query['search'] ?? null,
            'project_id'  => $query['project_id'] ?? null,
            'assigned_to' => $query['assigned_to'] ?? null,
            'status_id'   => $query['status_id'] ?? null,
        ];

        // Kiểm tra tính hợp lệ của project_id nếu có
        if (!empty($filters['project_id']) && !$this->validateProjectId($filters['project_id'])) {
            throw new \Exception("ID dự án không hợp lệ.", 400);
        }

        // Lấy dữ liệu từ Model dựa trên bộ lọc (không giới hạn phân trang)
        $tasks = $this->taskModel->getAllTasks($filters);

        // Lấy thông tin bổ trợ để hiển thị trên giao diện (dropdown lọc, breadcrumb)
        $selectedProject = !empty($filters['project_id']) ? $this->projectModel->find($filters['project_id']) : null;
        $statuses = $this->statusModel->getList($filters['project_id'] ?? null);

        View::render('tasks/list', [
            'tasks'           => $tasks,
            'projects'        => $this->projectModel->getAllProjects(),
            'users'           => $this->userModel->getAllUsers(),
            'statuses'        => $statuses,
            'filters'         => $filters,
            'selectedProject' => $selectedProject,
            'pageTitle'       => 'Danh sách công việc'
        ]);
    }

    //VALIDATE project_id
    public function validateProjectId($id): bool
    {
        // Tầng 1: Kiểm tra định dạng
        if (filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 999999999]]) == false) {
            return false;
            // throw new \Exception("ID dự án không hợp lệ. Vui lòng cung cấp một số nguyên dương.", 400);
        }
        return true;
    }




    /**
     * Hiển thị danh sách công việc với bộ lọc và phân trang
     */

    public function listIdProject($id)
    {
        $allProject = $this->projectModel->getAllProjects();
        $selectedProject = $this->projectModel->find($id);
        $statusTask = $this->statusModel->getList($id);

        // Lấy dữ liệu từ Model (Yêu cầu TaskModel phải có các phương thức này)
        $tasks = $this->taskModel->getTaskByIdProject($id);

        // Thu thập các tham số lọc từ URL
        View::render('tasks/list', [
            'tasks'      => $tasks,
            'projects'   => $allProject,
            'users'      => $this->userModel->getAllUsers(),
            'statuses'   => $statusTask,
            'selectedProject' => $selectedProject,
            'pageTitle'  => 'Danh sách công việc'
        ]);
    }

    public function create()
    {
        View::render('tasks/create', [
            'projects' => $this->projectModel->getAllProjects(),
            'users' => $this->userModel->getAllUsers(),
            'statuses' => $this->statusModel->getList(),
            'pageTitle' => 'Tạo công việc mới',
            'action_url' => URLROOT . '/tasks/create',
            'old' => $this->request->getBody()
        ]);
    }

    /**
     * Xử lý lưu công việc mới
     */
    public function store()
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/tasks/create');
        }

        $data = $this->getFormData();

        // Validate dữ liệu
        $this->validator->required('title', $data['title'], 'Tiêu đề');
        $this->validator->required('project_id', $data['project_id'], 'Dự án');
        $this->validator->required('status_id', $data['status_id'], 'Trạng thái');

        if (!$this->validator->passes()) {
            return View::render('tasks/create', [
                'projects' => $this->projectModel->getAllProjects(),
                'users' => $this->userModel->getAllUsers(),
                'statuses' => $this->statusModel->getList(),
                'errors' => $this->validator->getErrors(),
                'old' => $this->request->getBody(),
                'pageTitle' => 'Tạo công việc mới',
                'action_url' => URLROOT . '/tasks/create'
            ]);
        }

        if ($this->taskModel->create($data)) {
            Helper::setFlash('success', 'Thêm công việc mới thành công!');
            Response::redirect(URLROOT . '/tasks');
        } else {
            Helper::setFlash('danger', 'Có lỗi xảy ra trong quá trình lưu dữ liệu.');
            Response::redirect(URLROOT . '/tasks/create');
        }
    }

    private function getFormData()
    {
        $body = $this->request->getBody();
        return [
            'title'           => $body['title'] ?? '',
            'description'     => $body['description'] ?? '',
            'project_id'      => !empty($body['project_id']) ? (int)$body['project_id'] : null,
            'assigned_to'     => !empty($body['assigned_to']) ? (int)$body['assigned_to'] : null,
            'status_id'       => !empty($body['status_id']) ? (int)$body['status_id'] : null,
            'priority'        => $body['priority'] ?? 'medium',
            'due_date'        => !empty($body['due_date']) ? $body['due_date'] : null,
            'estimated_hours' => !empty($body['estimated_hours']) ? (float)$body['estimated_hours'] : 0,
            'created_at'      => date('Y-m-d H:i:s')
        ];
    }
}

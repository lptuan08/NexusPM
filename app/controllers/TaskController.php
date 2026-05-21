<?php

namespace App\controllers;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\core\Session;
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
        $query = $this->request->getQuery();

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
            'pagination'      => [],
            'selectedProject' => $selectedProject,
            'pageTitle'       => 'Danh sách công việc'
        ]);
    }

    /**
     * Hiển thị chi tiết công việc
     */
    public function show($id)
    {
        $task = $this->taskModel->find($id);
        if (!$task) {
            Helper::setFlash('danger', 'Công việc không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
        }

        View::render('tasks/detail', [
            'task' => $task,
            'pageTitle' => 'Chi tiết công việc'
        ]);
    }

    /**
     * Hiển thị form chỉnh sửa công việc
     */
    public function edit($id)
    {
        $task = $this->taskModel->find($id);
        if (!$task) {
            Helper::setFlash('danger', 'Công việc không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
        }

        View::render('tasks/create', [
            'task' => $task,
            'projects' => $this->projectModel->getAllProjects(),
            'users' => $this->userModel->getAllUsers(),
            'statuses' => $this->statusModel->getList($task['project_id']),
            'statusesByProject' => $this->getStatusesByProject(),
            'pageTitle' => 'Chỉnh sửa công việc',
            'action_url' => URLROOT . '/tasks/' . $id . '/edit',
            'old' => $task,
        ]);
    }

    /**
     * Xử lý cập nhật công việc
     */
    public function update($id)
    {
        $task = $this->taskModel->find($id);
        if (!$task) {
            Helper::setFlash('danger', 'Công việc không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
        }

        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . "/tasks/{$id}/edit");
            return;
        }

        $body = $this->request->getBody();
        $data = $this->getFormData();
        $this->ensureStatusForSelectedProject($data);
        
        // Không cập nhật ngày tạo khi chỉnh sửa
        unset($data['created_at']);
        unset($data['created_by']);

        $this->validator->required('title', $data['title'], 'Tiêu đề');
        $this->validator->selected('project_id', $data['project_id'], 'Dự án');
        $this->validator->selected('status_id', $data['status_id'], 'Trạng thái');

        if ($this->validator->passes()) {
            $projectId = (int) $data['project_id'];
            $statusId = (int) $data['status_id'];

            if (!$this->projectModel->find($projectId)) {
                $this->validator->addError('project_id', 'Dự án không tồn tại hoặc đã bị xóa.');
            } elseif (!$this->statusModel->belongsToProject($statusId, $projectId)) {
                $this->validator->addError('status_id', 'Trạng thái không thuộc dự án đã chọn.');
            }
        }

        if (!$this->validator->passes()) {
            return View::render('tasks/create', [
                'task' => $task,
                'projects' => $this->projectModel->getAllProjects(),
                'users' => $this->userModel->getAllUsers(),
                'statuses' => $this->statusModel->getList((int)$body['project_id']),
                'statusesByProject' => $this->getStatusesByProject(),
                'errors' => $this->validator->getErrors(),
                'old' => $body,
                'pageTitle' => 'Chỉnh sửa công việc',
                'action_url' => URLROOT . '/tasks/' . $id . '/edit',
            ]);
        }

        $assigneeId = !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        unset($data['assigned_to']);

        try {
            $this->taskModel->beginTransaction();
            
            // Cập nhật thông tin cơ bản của task
            $this->taskModel->update($id, $data);

            // Cập nhật người phụ trách (Xóa cũ, thêm mới nếu có)
            // Giả định Model có phương thức removeAssignments hoặc xử lý trực tiếp
            $this->taskModel->query("DELETE FROM task_assignments WHERE task_id = :tid", ['tid' => $id]);
            
            if ($assigneeId) {
                $this->taskModel->assignUserToTask($id, $assigneeId, $this->currentUserId());
            }

            $this->taskModel->commit();
            Helper::setFlash('success', 'Cập nhật công việc thành công!');
            Response::redirect(URLROOT . '/tasks/' . $id);
        } catch (\Throwable $e) {
            $this->taskModel->rollBack();
            Helper::setFlash('danger', 'Lỗi: ' . $e->getMessage());
            Response::redirect(URLROOT . "/tasks/{$id}/edit");
        }
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
            'tasks'             => $tasks,
            'projects'          => $allProject,
            'users'             => $this->userModel->getAllUsers(),
            'statuses'          => $statusTask,
            'filters'           => ['project_id' => $id],
            'pagination'        => [],
            'selectedProject'   => $selectedProject,
            'pageTitle'         => 'Danh sách công việc'
        ]);
    }

    /**
     * Hiển thị bảng Kanban cho một dự án cụ thể
     */
    public function kanban($id)
    {
        $project = $this->projectModel->find($id);
        if (!$project) {
            Helper::setFlash('danger', 'Dự án không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
        }

        $statuses = $this->statusModel->getList($id);
        $tasks = $this->taskModel->getTaskByIdProject($id);

        // Nhóm các task theo status_id
        $groupedTasks = [];
        foreach ($statuses as $status) {
            $groupedTasks[$status['id']] = [];
        }
        foreach ($tasks as $task) {
            $groupedTasks[$task['status_id']][] = $task;
        }

        View::render('tasks/kanban', [
            'project'      => $project,
            'projects'     => $this->projectModel->getAllProjects(),
            'statuses'     => $statuses,
            'groupedTasks' => $groupedTasks,
            'pageTitle'    => 'Bảng Kanban: ' . $project['name']
        ]);
    }

    /**
     * API xử lý cập nhật trạng thái qua AJAX (Kanban Drag & Drop)
     */
    public function updateStatus()
    {
        $body = $this->request->getBody();
        $taskId = $body['task_id'] ?? null;
        $statusId = $body['status_id'] ?? null;

        if ($taskId && $statusId) {
            $this->taskModel->update($taskId, ['status_id' => $statusId]);
            return Response::success([], 'Cập nhật trạng thái thành công');
        }

        return Response::error('Dữ liệu không hợp lệ');
    }

    public function create()
    {
        $query = $this->request->getQuery();
        $prefillProjectId = isset($query['project_id']) && $query['project_id'] !== '' && $query['project_id'] !== null
            ? (int) $query['project_id']
            : null;

        View::render('tasks/create', [
            'projects' => $this->projectModel->getAllProjects(),
            'users' => $this->userModel->getAllUsers(),
            'statuses' => $this->statusModel->getList($prefillProjectId),
            'statusesByProject' => $this->getStatusesByProject(),
            'pageTitle' => 'Tạo công việc mới',
            'action_url' => URLROOT . '/tasks/store',
            'old' => $this->request->getBody(),
        ]);
    }

    /**
     * Xử lý lưu công việc mới
     */
    public function store()
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/tasks/create');
            return;
        }

        $body = $this->request->getBody();
        $data = $this->getFormData();
        $this->ensureStatusForSelectedProject($data);

        $this->validator->required('title', $data['title'], 'Tiêu đề');
        $this->validator->selected('project_id', $data['project_id'], 'Dự án');
        $this->validator->selected('status_id', $data['status_id'], 'Trạng thái');

        if ($this->validator->passes()) {
            $projectId = (int) $data['project_id'];
            $statusId = (int) $data['status_id'];

            if (!$this->projectModel->find($projectId)) {
                $this->validator->addError('project_id', 'Dự án không tồn tại hoặc đã bị xóa.');
            } elseif (!$this->statusModel->belongsToProject($statusId, $projectId)) {
                $this->validator->addError('status_id', 'Trạng thái không thuộc dự án đã chọn.');
            }
        }

        if (!$this->validator->passes()) {
            $statusProjectId = !empty($body['project_id']) ? (int) $body['project_id'] : null;
            return View::render('tasks/create', [
                'projects' => $this->projectModel->getAllProjects(),
                'users' => $this->userModel->getAllUsers(),
                'statuses' => $this->statusModel->getList($statusProjectId),
                'statusesByProject' => $this->getStatusesByProject(),
                'errors' => $this->validator->getErrors(),
                'old' => $body,
                'pageTitle' => 'Tạo công việc mới',
                'action_url' => URLROOT . '/tasks/store',
            ]);
        }

        $assigneeId = !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        unset($data['assigned_to']);

        try {
            $this->taskModel->beginTransaction();
            $this->taskModel->create($data);
            $taskId = $this->taskModel->lastInsertId();
            if ($taskId < 1) {
                throw new \RuntimeException('Không lấy được ID công việc sau khi lưu.');
            }
            if ($assigneeId) {
                $this->taskModel->assignUserToTask($taskId, $assigneeId, $this->currentUserId());
            }
            $this->taskModel->commit();
            Helper::setFlash('success', 'Thêm công việc mới thành công!');
            Response::redirect(URLROOT . '/tasks');
        } catch (\Throwable $e) {
            $this->taskModel->rollBack();
            Helper::setFlash('danger', 'Không thể lưu công việc. Kiểm tra dữ liệu và thử lại.');
            Response::redirect(URLROOT . '/tasks/create');
        }
    }

    private function getFormData(): array
    {
        $body = $this->request->getBody();
        $allowedPriority = ['low', 'medium', 'high', 'urgent'];
        $priority = $body['priority'] ?? 'medium';
        if (!in_array($priority, $allowedPriority, true)) {
            $priority = 'medium';
        }
        $now = date('Y-m-d H:i:s');

        return [
            'title' => $body['title'] ?? '',
            'description' => $body['description'] ?? '',
            'project_id' => !empty($body['project_id']) ? (int) $body['project_id'] : null,
            'assigned_to' => !empty($body['assigned_to']) ? (int) $body['assigned_to'] : null,
            'status_id' => !empty($body['status_id']) ? (int) $body['status_id'] : null,
            'priority' => $priority,
            'due_date' => !empty($body['due_date']) ? $body['due_date'] : null,
            'estimated_hours' => isset($body['estimated_hours']) && $body['estimated_hours'] !== ''
                ? (float) $body['estimated_hours']
                : 0.0,
            'created_by' => $this->currentUserId(),
            'created_at' => $now,
        ];
    }

    private function currentUserId(): int
    {
        return (int) (Session::get('user')['id'] ?? 0);
    }

    private function getStatusesByProject(): array
    {
        $statusesByProject = [];
        foreach ($this->projectModel->getAllProjects() as $project) {
            $projectId = (int) ($project['id'] ?? 0);
            if ($projectId > 0) {
                $statusesByProject[$projectId] = $this->statusModel->getList($projectId);
            }
        }

        return $statusesByProject;
    }

    private function ensureStatusForSelectedProject(array &$data): void
    {
        if (!empty($data['status_id']) || empty($data['project_id'])) {
            return;
        }

        $statuses = $this->statusModel->getList((int) $data['project_id']);
        if (!empty($statuses[0]['id'])) {
            $data['status_id'] = (int) $statuses[0]['id'];
        }
    }
}

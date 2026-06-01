<?php

namespace App\controllers;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\core\Session;
use App\helpers\Helper;
use App\helpers\ListTableHelper;
use App\models\TaskModel;
use App\models\ProjectModel;
use App\models\UserModel;
use App\models\TaskStatusModel;
use App\helpers\AuthHelper;

/**
 * @property \App\core\Request $request
 */
class TaskController extends Controller
{
    private TaskModel $taskModel;
    private ProjectModel $projectModel;
    private UserModel $userModel;
    private TaskStatusModel $statusModel;

    private const PROJECT_TASK_ABILITIES = [
        'manager' => ['view', 'create', 'update', 'delete', 'assign'],
        'member' => ['view', 'create', 'update'],
        'viewer' => ['view'],
    ];

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
    public function __construct()
    {
        parent::__construct();
        $this->taskModel = $this->model('TaskModel');
        $this->projectModel = $this->model('ProjectModel');
        $this->userModel = $this->model('UserModel');
        $this->statusModel = $this->model('TaskStatusModel');
    }

    /**
     * =============================================================
     * DANH SACH QUYEN MODULE TASKS
     * =============================================================
     *
     * Nhom xem:
     * - tasks.view.all: Xem tat ca cong viec trong he thong.
     * - tasks.view.own: Xem cong viec do user tao hoac dang duoc phan cong.
     *
     * Nhom tao:
     * - tasks.create.all: Tao cong viec trong tat ca du an.
     *
     * Nhom cap nhat:
     * - tasks.update.all: Cap nhat tat ca cong viec.
     * - tasks.update.own: Cap nhat cong viec do user tao hoac dang duoc phan cong.
     *
     * Nhom xoa:
     * - tasks.delete.all: Xoa tat ca cong viec.
     *
     * Nhom phan cong:
     * - tasks.assign.all: Phan cong nguoi thuc hien cho tat ca cong viec.
     *
     * Nhom theo role trong project:
     * - tasks.project: Cho phep dung role manager/member/viewer trong project de quyet dinh
     *   user duoc view/create/update/delete/assign task trong project do.
     */

    /**
     * Lấy vai trò của user hiện tại trong một dự án.
     *
     * Owner của dự án được ProjectModel quy về vai trò manager; thành viên thường
     * lấy theo bảng project_members. Trả về null nếu user không thuộc dự án.
     *
     * @param int $projectId ID dự án cần kiểm tra.
     * @return string|null Vai trò manager/member/viewer hoặc null nếu không tham gia.
     */
    private function projectRole(int $projectId): ?string
    {
        return $this->projectModel->getUserProjectRole($projectId, $this->currentUserId());
    }

    /**
     * Kiem tra user co duoc thuc hien mot hanh dong task theo role trong project hay khong.
     *
     * Permission tasks.project chi mo cong xu ly theo project; role manager/member/viewer
     * trong project moi quyet dinh hanh dong cu the.
     *
     * @param int $projectId ID du an can kiem tra.
     * @param string $ability Hanh dong task: view/create/update/delete/assign.
     * @return bool True neu role trong project duoc phep thuc hien hanh dong.
     */
    private function canUseProjectTaskRole(int $projectId, string $ability): bool
    {
        if (!AuthHelper::can('tasks.project')) {
            return false;
        }

        $projectRole = $this->projectRole($projectId);

        return in_array($ability, self::PROJECT_TASK_ABILITIES[$projectRole] ?? [], true);
    }

    /**
     * Lay danh sach project role duoc phep thuc hien mot hanh dong task.
     *
     * @param string $ability Hanh dong task: view/create/update/delete/assign.
     * @return array<int, string> Danh sach role trong project.
     */
    private function projectRolesForTaskAbility(string $ability): array
    {
        $roles = [];
        foreach (self::PROJECT_TASK_ABILITIES as $role => $abilities) {
            if (in_array($ability, $abilities, true)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * Xác định task có thuộc trách nhiệm trực tiếp của user hiện tại hay không.
     *
     * Một task được xem là "own" khi user là người tạo hoặc đang được phân công
     * active trong bảng task_assignments.
     *
     * @param array<string, mixed> $task Dữ liệu task lấy từ database.
     * @return bool True nếu task do user tạo hoặc đang được giao cho user.
     */
    private function taskBelongsToCurrentUser(array $task): bool
    {
        $taskId = (int) ($task['id'] ?? 0);
        $userId = $this->currentUserId();

        return $userId > 0
            && (
                (int) ($task['created_by'] ?? 0) === $userId
                || ($taskId > 0 && $this->taskModel->isTaskAssignedToUser($taskId, $userId))
            );
    }

    /**
     * Kiểm tra quyền xem một task cụ thể theo quyền global/project/own.
     *
     * @param array<string, mixed> $task Dữ liệu task cần kiểm tra.
     * @return bool True nếu user hiện tại được xem task.
     */
    private function canViewTask(array $task): bool
    {
        $projectId = (int) ($task['project_id'] ?? 0);

        return AuthHelper::can('tasks.view.all')
            || $this->canUseProjectTaskRole($projectId, 'view')
            || (AuthHelper::can('tasks.view.own') && $this->taskBelongsToCurrentUser($task));
    }

    /**
     * Chặn truy cập nếu user hiện tại không được xem task.
     *
     * @param array<string, mixed> $task Dữ liệu task cần kiểm tra.
     * @return void
     * @throws \Exception Khi user không có quyền xem task.
     */
    private function requireCanViewTask(array $task): void
    {
        if ($this->canViewTask($task)) {
            return;
        }

        throw new \Exception('Bạn không có quyền xem công việc này.', 403);
    }

    /**
     * Kiểm tra quyền xem toàn bộ task trong một dự án.
     *
     * Quyền này dùng cho các màn theo ngữ cảnh dự án như list theo project và Kanban.
     *
     * @param int $projectId ID dự án cần kiểm tra.
     * @return bool True nếu user có quyền xem task trong dự án.
     */
    private function canViewProjectTasks(int $projectId): bool
    {
        return AuthHelper::can('tasks.view.all')
            || $this->canUseProjectTaskRole($projectId, 'view');
    }

    /**
     * Chặn truy cập nếu user không được xem task trong dự án.
     *
     * @param int $projectId ID dự án cần kiểm tra.
     * @return void
     * @throws \Exception Khi user không có quyền xem task của dự án.
     */
    private function requireCanViewProjectTasks(int $projectId): void
    {
        if ($this->canViewProjectTasks($projectId)) {
            return;
        }

        throw new \Exception('Bạn không có quyền xem công việc của dự án này.', 403);
    }

    /**
     * Kiểm tra quyền tạo task trong một dự án.
     *
     * Manager va member cua du an duoc phep tao khi role he thong co tasks.project;
     * viewer chi duoc xem.
     *
     * @param int $projectId ID dự án cần tạo task.
     * @return bool True nếu user được tạo task trong dự án.
     */
    private function canCreateTaskInProject(int $projectId): bool
    {
        return AuthHelper::can('tasks.create.all')
            || $this->canUseProjectTaskRole($projectId, 'create');
    }

    /**
     * Chặn thao tác tạo task nếu user không đủ quyền trong dự án.
     *
     * @param int $projectId ID dự án cần tạo task.
     * @return void
     * @throws \Exception Khi user không có quyền tạo task.
     */
    private function requireCanCreateTaskInProject(int $projectId): void
    {
        if ($this->canCreateTaskInProject($projectId)) {
            return;
        }

        throw new \Exception('Bạn không có quyền tạo công việc trong dự án này.', 403);
    }

    /**
     * Kiểm tra quyền cập nhật task theo quyền global/project/own.
     *
     * @param array<string, mixed> $task Dữ liệu task cần kiểm tra.
     * @return bool True nếu user được cập nhật task.
     */
    private function canUpdateTask(array $task): bool
    {
        $projectId = (int) ($task['project_id'] ?? 0);

        return AuthHelper::can('tasks.update.all')
            || $this->canUseProjectTaskRole($projectId, 'update')
            || (AuthHelper::can('tasks.update.own') && $this->taskBelongsToCurrentUser($task));
    }

    /**
     * Chặn thao tác cập nhật nếu user không có quyền với task.
     *
     * @param array<string, mixed> $task Dữ liệu task cần kiểm tra.
     * @return void
     * @throws \Exception Khi user không có quyền cập nhật task.
     */
    private function requireCanUpdateTask(array $task): void
    {
        if ($this->canUpdateTask($task)) {
            return;
        }

        throw new \Exception('Bạn không có quyền cập nhật công việc này.', 403);
    }

    /**
     * Kiểm tra quyền xóa task theo quyền global hoặc quyền trong dự án.
     *
     * @param array<string, mixed> $task Dữ liệu task cần kiểm tra.
     * @return bool True nếu user được xóa task.
     */
    private function canDeleteTask(array $task): bool
    {
        $projectId = (int) ($task['project_id'] ?? 0);

        return AuthHelper::can('tasks.delete.all')
            || $this->canUseProjectTaskRole($projectId, 'delete');
    }

    /**
     * Chặn thao tác xóa nếu user không có quyền với task.
     *
     * @param array<string, mixed> $task Dữ liệu task cần kiểm tra.
     * @return void
     * @throws \Exception Khi user không có quyền xóa task.
     */
    private function requireCanDeleteTask(array $task): void
    {
        if ($this->canDeleteTask($task)) {
            return;
        }

        throw new \Exception('Bạn không có quyền xóa công việc này.', 403);
    }

    /**
     * Kiểm tra quyền phân công task trong một dự án.
     *
     * @param int $projectId ID dự án chứa task cần phân công.
     * @return bool True nếu user được phân công người thực hiện task.
     */
    private function canAssignTaskInProject(int $projectId): bool
    {
        return AuthHelper::can('tasks.assign.all')
            || $this->canUseProjectTaskRole($projectId, 'assign');
    }

    /**
     * Chặn thao tác phân công nếu user không có quyền trong dự án.
     *
     * @param int $projectId ID dự án chứa task cần phân công.
     * @return void
     * @throws \Exception Khi user không có quyền phân công task.
     */
    private function requireCanAssignTaskInProject(int $projectId): void
    {
        if ($this->canAssignTaskInProject($projectId)) {
            return;
        }

        throw new \Exception('Bạn không có quyền phân công công việc trong dự án này.', 403);
    }

    /**
     * Tạo bộ lọc quyền xem task để truyền xuống TaskModel.
     *
     * Nếu có tasks.view.all thì không cần thêm điều kiện giới hạn. Nếu không,
     * model sẽ lọc theo task trong project user tham gia và/hoặc task của chính user.
     *
     * @return array<string, mixed> Bộ lọc quyền xem task.
     */
    private function taskVisibilityFilters(): array
    {
        if (AuthHelper::can('tasks.view.all')) {
            return [];
        }

        return [
            'visibility_user_id' => $this->currentUserId(),
            'visibility_project' => AuthHelper::can('tasks.project'),
            'visibility_own' => AuthHelper::can('tasks.view.own'),
        ];
    }

    /**
     * Lấy danh sách dự án được phép xuất hiện trong bộ lọc/list task.
     *
     * @return array<int, array<string, mixed>> Danh sách dự án theo quyền xem hiện tại.
     */
    private function projectOptionsForTaskList(): array
    {
        if (AuthHelper::can('tasks.view.all') || AuthHelper::can('projects.view.all')) {
            return $this->projectModel->getAllProjects();
        }

        return $this->projectModel->getProjectsForUser($this->currentUserId());
    }

    /**
     * Lấy danh sách dự án user được phép tạo task.
     *
     * @return array<int, array<string, mixed>> Danh sách dự án có thể tạo task.
     */
    private function projectOptionsForTaskCreate(): array
    {
        if (AuthHelper::can('tasks.create.all')) {
            return $this->projectModel->getAllProjects();
        }

        if (AuthHelper::can('tasks.project')) {
            return $this->projectModel->getProjectsForUser($this->currentUserId(), $this->projectRolesForTaskAbility('create'));
        }

        return [];
    }

    /**
     * =============================================================
     * NHOM HIEN THI VA TRA CUU CONG VIEC
     * =============================================================
     */



    /**
     * Hiển thị danh sách task có phân trang, bộ lọc và giới hạn theo quyền.
     *
     * @return void
     * @throws \Exception Khi project_id trên query không hợp lệ.
     */
    public function index()
    {
        $query = $this->request->getQuery();
        $page = $this->positiveInt($query['page'] ?? 1, 1);
        $perPage = ListTableHelper::perPage();

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

        $filters = array_merge($filters, $this->taskVisibilityFilters());

        // Lấy dữ liệu từ Model dựa trên bộ lọc và phân trang
        $totalItem = $this->taskModel->countAll($filters);
        $totalPage = max((int) ceil($totalItem / $perPage), 1);
        $page = min($page, $totalPage);
        $tasks = $this->taskModel->getTasksByPage($page, $perPage, $filters);
        foreach ($tasks as &$task) {
            $task['can_update'] = $this->canUpdateTask($task);
            $task['can_delete'] = $this->canDeleteTask($task);
        }
        unset($task);

        // Lấy thông tin bổ trợ để hiển thị trên giao diện (dropdown lọc, breadcrumb)
        $selectedProject = !empty($filters['project_id']) ? $this->projectModel->find($filters['project_id']) : null;
        $statuses = $this->statusModel->getList($filters['project_id'] ?? null);
        $createProjectOptions = $this->projectOptionsForTaskCreate();
        $canCreateTask = $selectedProject
            ? $this->canCreateTaskInProject((int) $selectedProject['id'])
            : !empty($createProjectOptions);

        View::render('tasks/list', [
            'tasks'           => $tasks,
            'projects'        => $this->projectOptionsForTaskList(),
            'users'           => $this->userModel->getAllUsers(),
            'statuses'        => $statuses,
            'filters'         => $filters,
            'pagination'      => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItem,
                'total_pages' => $totalPage,
            ],
            'selectedProject' => $selectedProject,
            'canCreateTask'   => $canCreateTask,
            'pageTitle'       => 'Danh sách công việc'
        ]);
    }

    /**
     * Hiển thị chi tiết một task sau khi kiểm tra quyền xem.
     *
     * @param int|string $id ID task cần xem.
     * @return void
     * @throws \Exception Khi user không có quyền xem task.
     */
    public function show($id)
    {
        $task = $this->taskModel->find($id);
        if (!$task) {
            Helper::setFlash('danger', 'Công việc không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
        }

        $this->requireCanViewTask($task);

        View::render('tasks/detail', [
            'task' => $task,
            'canUpdateTask' => $this->canUpdateTask($task),
            'canDeleteTask' => $this->canDeleteTask($task),
            'pageTitle' => 'Chi tiết công việc'
        ]);
    }

    /**
     * Hiển thị form chỉnh sửa task.
     *
     * Dự án của task bị khóa khi chỉnh sửa để tránh di chuyển task sang project
     * khác ngoài ý muốn và để giữ đúng phạm vi phân quyền.
     *
     * @param int $id ID task cần chỉnh sửa.
     * @return void
     * @throws \Exception Khi user không có quyền cập nhật task.
     */
    public function edit(int $id)
    {
        $task = $this->taskModel->find($id);
        // $project = $this->projectModel->find($task['project_id']);

        if (!$task) {
            Helper::setFlash('danger', 'Công việc không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
        }

        $this->requireCanUpdateTask($task);
        $projectOptions = array_values(array_filter([$this->projectModel->find((int) $task['project_id'])]));

        View::render('tasks/create', [
            'task' => $task,
            'projects' => $projectOptions,
            'users' => $this->userModel->getAllUsers(),
            'statuses' => $this->statusModel->getList($task['project_id']),
            'statusesByProject' => $this->getStatusesByProject($projectOptions),
            'pageTitle' => 'Chỉnh sửa công việc',
            'action_url' => URLROOT . '/tasks/' . $id . '/edit',
            'old' => $task,
        ]);
    }

    /**
     * Xử lý cập nhật task và người được phân công.
     *
     * Hàm kiểm tra quyền cập nhật task, khóa project_id về project gốc, validate
     * status thuộc đúng project, rồi cập nhật task trong transaction.
     *
     * @param int $id ID task cần cập nhật.
     * @return void
     * @throws \Exception Khi user không có quyền cập nhật hoặc phân công task.
     */
    public function update(int $id)
    {
        $task = $this->taskModel->find($id);
        if (!$task) {
            Helper::setFlash('danger', 'Công việc không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
            return;
        }

        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . "/tasks/{$id}/edit");
            return;
        }

        $this->requireCanUpdateTask($task);
        $body = $this->request->getBody();
        $data = $this->getFormData();
        $projectOptions = array_values(array_filter([$this->projectModel->find((int) $task['project_id'])]));

        // Khi chỉnh sửa, không cho đổi dự án của công việc.
        // Dự án thật luôn lấy từ dữ liệu hiện tại trong database.
        $originalProjectId = (int) $task['project_id'];
        $data['project_id'] = $originalProjectId;

        $this->ensureStatusForSelectedProject($data);

        // Không cập nhật ngày tạo khi chỉnh sửa
        unset($data['created_at'], $data['created_by']);

        $this->validator->required('title', $data['title'], 'Tiêu đề');
        $this->validator->selected('project_id', $data['project_id'], 'Dự án');
        $this->validator->selected('status_id', $data['status_id'], 'Trạng thái');

        if (!empty($body['project_id']) && (int) $body['project_id'] !== $originalProjectId) {
            $this->validator->addError('project_id', 'Không được thay đổi dự án khi chỉnh sửa công việc.');
        }

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
                'projects' => $projectOptions,
                'users' => $this->userModel->getAllUsers(),
                'statuses' => $this->statusModel->getList($originalProjectId),
                'statusesByProject' => $this->getStatusesByProject($projectOptions),
                'errors' => $this->validator->getErrors(),
                'old' => array_merge($body, ['project_id' => $originalProjectId]),
                'pageTitle' => 'Chỉnh sửa công việc',
                'action_url' => URLROOT . '/tasks/' . $id . '/edit',
            ]);
        }

        $assigneeId = !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        if ($assigneeId) {
            $this->requireCanAssignTaskInProject($originalProjectId);
        }
        unset($data['assigned_to']);

        try {
            $this->taskModel->beginTransaction();

            // Cập nhật thông tin cơ bản của task
            $this->taskModel->update($id, $data);

            // Cập nhật người phụ trách (Xóa cũ, thêm mới nếu có)
            $this->taskModel->removeAssignments($id);

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

    /**
     * Kiểm tra định dạng project_id trên URL/query.
     *
     * @param mixed $id Giá trị project_id cần kiểm tra.
     * @return bool True nếu là số nguyên dương trong phạm vi cho phép.
     */
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
     * Hiển thị danh sách task theo một project cụ thể.
     *
     * @param int|string $id ID dự án cần lọc task.
     * @return void
     * @throws \Exception Khi project_id không hợp lệ.
     */
    public function listIdProject($id)
    {
        if (!$this->validateProjectId($id)) {
            throw new \Exception("ID dự án không hợp lệ.", 400);
        }

        $query = $this->request->getQuery();
        $page = $this->positiveInt($query['page'] ?? 1, 1);
        $perPage = ListTableHelper::perPage();
        $filters = [
            'search'      => $query['search'] ?? null,
            'project_id'  => (int) $id,
            'assigned_to' => $query['assigned_to'] ?? null,
            'status_id'   => $query['status_id'] ?? null,
        ];
        $filters = array_merge($filters, $this->taskVisibilityFilters());

        $selectedProject = $this->projectModel->find($id);
        if (!$selectedProject) {
            Helper::setFlash('danger', 'Dự án không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
        }
        $statusTask = $this->statusModel->getList($id);

        // Lấy dữ liệu từ Model dựa trên bộ lọc và phân trang
        $totalItem = $this->taskModel->countAll($filters);
        $totalPage = max((int) ceil($totalItem / $perPage), 1);
        $page = min($page, $totalPage);
        $tasks = $this->taskModel->getTasksByPage($page, $perPage, $filters);
        foreach ($tasks as &$task) {
            $task['can_update'] = $this->canUpdateTask($task);
            $task['can_delete'] = $this->canDeleteTask($task);
        }
        unset($task);
        $createProjectOptions = $this->projectOptionsForTaskCreate();
        $canCreateTask = $selectedProject
            ? $this->canCreateTaskInProject((int) $selectedProject['id'])
            : !empty($createProjectOptions);

        // Thu thập các tham số lọc từ URL
        View::render('tasks/list', [
            'tasks'             => $tasks,
            'projects'          => $this->projectOptionsForTaskList(),
            'users'             => $this->userModel->getAllUsers(),
            'statuses'          => $statusTask,
            'filters'           => $filters,
            'pagination'        => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItem,
                'total_pages' => $totalPage,
            ],
            'selectedProject'   => $selectedProject,
            'canCreateTask'     => $canCreateTask,
            'pageTitle'         => 'Danh sách công việc'
        ]);
    }

    /**
     * Hiển thị bảng Kanban cho task của một dự án.
     *
     * @param int|string $id ID dự án cần hiển thị Kanban.
     * @return void
     * @throws \Exception Khi user không có quyền xem task của dự án.
     */
    public function kanban($id)
    {
        $project = $this->projectModel->find($id);
        if (!$project) {
            Helper::setFlash('danger', 'Dự án không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
        }

        $projectId = (int) $id;
        $this->requireCanViewProjectTasks($projectId);
        $statuses = $this->statusModel->getList($id);
        $tasks = $this->taskModel->getTaskByIdProject($id);
        foreach ($tasks as &$task) {
            $task['can_update'] = $this->canUpdateTask($task);
            $task['can_delete'] = $this->canDeleteTask($task);
        }
        unset($task);
        $canUpdateProjectTasks = AuthHelper::can('tasks.update.all')
            || $this->canUseProjectTaskRole($projectId, 'update');

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
            'projects'     => $this->projectOptionsForTaskList(),
            'statuses'     => $statuses,
            'groupedTasks' => $groupedTasks,
            'canCreateTask' => $this->canCreateTaskInProject($projectId),
            'canUpdateProjectTasks' => $canUpdateProjectTasks,
            'pageTitle'    => 'Bảng Kanban: ' . $project['name']
        ]);
    }

    /**
     * API cập nhật trạng thái task từ thao tác kéo thả Kanban.
     *
     * @return void JSON success/error được gửi trực tiếp qua Response.
     * @throws \Exception Khi user không có quyền cập nhật task.
     */
    public function updateStatus()
    {
        $body = $this->request->getBody();
        $taskId = $body['task_id'] ?? null;
        $statusId = $body['status_id'] ?? null;

        if ($taskId && $statusId) {
            $task = $this->taskModel->find((int) $taskId);
            if (!$task) {
                return Response::error('Công việc không tồn tại.', [], 404);
            }

            $this->requireCanUpdateTask($task);
            if (!$this->statusModel->belongsToProject((int) $statusId, (int) $task['project_id'])) {
                return Response::error('Trạng thái không thuộc dự án của công việc.', [], 422);
            }

            $this->taskModel->update((int) $taskId, ['status_id' => (int) $statusId]);
            return Response::success([], 'Cập nhật trạng thái thành công');
        }

        return Response::error('Dữ liệu không hợp lệ');
    }

    /**
     * Xóa mềm một task sau khi kiểm tra quyền xóa.
     *
     * @param int|string $id ID task cần xóa.
     * @return void
     * @throws \Exception Khi user không có quyền xóa task.
     */
    public function delete($id)
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/tasks');
            return;
        }

        $taskId = $this->positiveInt($id);
        $task = $taskId > 0 ? $this->taskModel->find($taskId) : false;
        if ($taskId <= 0 || !$task) {
            Helper::setFlash('danger', 'Công việc không tồn tại.');
            Response::redirect(URLROOT . '/tasks');
            return;
        }

        $this->requireCanDeleteTask($task);
        $this->taskModel->delete($taskId);
        Helper::setFlash('success', 'Xóa công việc thành công.');
        Response::redirect($this->taskListRedirectUrl());
    }

    /**
     * Hiển thị form tạo task mới.
     *
     * Nếu truyền project_id trên query, hàm kiểm tra user có được tạo task trong
     * project đó hay không trước khi render form.
     *
     * @return void
     * @throws \Exception Khi user không có dự án nào được phép tạo task.
     */
    public function create()
    {
        $query = $this->request->getQuery();
        $projectOptions = $this->projectOptionsForTaskCreate();
        if (empty($projectOptions)) {
            throw new \Exception('Bạn không có quyền tạo công việc trong dự án nào.', 403);
        }

        $prefillProjectId = isset($query['project_id']) && $query['project_id'] !== '' && $query['project_id'] !== null
            ? (int) $query['project_id']
            : null;
        if ($prefillProjectId !== null) {
            $this->requireCanCreateTaskInProject($prefillProjectId);
        }

        View::render('tasks/create', [
            'projects' => $projectOptions,
            'users' => $this->userModel->getAllUsers(),
            'statuses' => $this->statusModel->getList($prefillProjectId),
            'statusesByProject' => $this->getStatusesByProject($projectOptions),
            'pageTitle' => 'Tạo công việc mới',
            'action_url' => URLROOT . '/tasks/store',
            'old' => $this->request->getBody(),
        ]);
    }

    /**
     * Xử lý lưu task mới và phân công người thực hiện nếu có.
     *
     * @return void
     * @throws \Exception Khi user không có quyền tạo hoặc phân công task.
     */
    public function store()
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/tasks/create');
            return;
        }

        $body = $this->request->getBody();
        $data = $this->getFormData();
        $projectOptions = $this->projectOptionsForTaskCreate();
        if (empty($projectOptions)) {
            throw new \Exception('Bạn không có quyền tạo công việc trong dự án nào.', 403);
        }
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
            } else {
                $this->requireCanCreateTaskInProject($projectId);
            }
        }

        if (!$this->validator->passes()) {
            $statusProjectId = !empty($body['project_id']) ? (int) $body['project_id'] : null;
            return View::render('tasks/create', [
                'projects' => $projectOptions,
                'users' => $this->userModel->getAllUsers(),
                'statuses' => $this->statusModel->getList($statusProjectId),
                'statusesByProject' => $this->getStatusesByProject($projectOptions),
                'errors' => $this->validator->getErrors(),
                'old' => $body,
                'pageTitle' => 'Tạo công việc mới',
                'action_url' => URLROOT . '/tasks/store',
            ]);
        }

        $assigneeId = !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        if ($assigneeId) {
            $this->requireCanAssignTaskInProject((int) $data['project_id']);
        }
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

    /**
     * Chuẩn hóa dữ liệu task lấy từ request body.
     *
     * @return array<string, mixed> Dữ liệu task đã chuẩn hóa để validate/lưu DB.
     */
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

    /**
     * Lấy ID user hiện tại từ session.
     *
     * @return int ID user đang đăng nhập, hoặc 0 nếu session không hợp lệ.
     */
    private function currentUserId(): int
    {
        return (int) (Session::get('user')['id'] ?? 0);
    }

    /**
     * Lấy danh sách trạng thái task theo từng project để render dropdown phụ thuộc.
     *
     * @param array<int, array<string, mixed>>|null $projects Danh sách project cần lấy trạng thái.
     * @return array<int, array<int, array<string, mixed>>> Map project_id => danh sách trạng thái.
     */
    private function getStatusesByProject(?array $projects = null): array
    {
        $statusesByProject = [];
        $projects = $projects ?? $this->projectOptionsForTaskList();
        foreach ($projects as $project) {
            $projectId = (int) ($project['id'] ?? 0);
            if ($projectId > 0) {
                $statusesByProject[$projectId] = $this->statusModel->getList($projectId);
            }
        }

        return $statusesByProject;
    }

    /**
     * Tự chọn trạng thái đầu tiên của project nếu form chưa gửi status_id.
     *
     * @param array<string, mixed> $data Dữ liệu form task, được cập nhật trực tiếp.
     * @return void
     */
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

    /**
     * Chuẩn hóa một giá trị thành số nguyên dương.
     *
     * @param mixed $value Giá trị cần chuyển đổi.
     * @param int $default Giá trị mặc định nếu không hợp lệ.
     * @return int Số nguyên dương hợp lệ hoặc giá trị mặc định.
     */
    private function positiveInt($value, int $default = 0): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? $default : (int) $id;
    }

    /**
     * Xác định URL quay lại sau khi xóa task.
     *
     * Chỉ chấp nhận redirect nội bộ dưới /tasks để tránh open redirect.
     *
     * @return string URL danh sách task an toàn.
     */
    private function taskListRedirectUrl(): string
    {
        $body = $this->request->getBody();
        $redirectTo = trim((string) ($body['redirect_to'] ?? ''));

        if ($redirectTo !== '' && str_starts_with($redirectTo, URLROOT . '/tasks')) {
            return $redirectTo;
        }

        return URLROOT . '/tasks';
    }
}

<?php

namespace App\controllers;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
use App\models\ProjectModel;
use App\models\ProjectStatusModel;
use App\models\TaskStatusModel;
use App\models\UserModel;

/**
 * @property \App\core\Request $request
 * @property \App\core\Validator $validator
 */
class ProjectController extends Controller
{
    private ProjectModel $modelProject;
    private UserModel $modelUser;
    private ProjectStatusModel $modelProjectStatus;
    private TaskStatusModel $modelTaskStatus;

    public function __construct()
    {
        parent::__construct();
        $this->modelProject = $this->model('ProjectModel');
        $this->modelUser = $this->model('UserModel');
        $this->modelProjectStatus = $this->model('ProjectStatusModel');
        $this->modelTaskStatus = $this->model('TaskStatusModel');
    }

    /**
     * Hiển thị danh sách dự án có phân trang
     */
    public function index()
    {
        $query = $this->request->getQuery();
        if (isset($query['page'])) {
            $page = (int) $query['page'];
        } else {
            $page = 1;
        }
        $perPage = 5; // Số dự án trên mỗi trang (có thể cấu hình)

        // Lấy các tham số lọc từ request
        $filters = [
            'status_id' => $query['status_id'] ?? [],
            'start_date' => $query['start_date'] ?? null,
            'end_date' => $query['end_date'] ?? null,
        ];

        // Đảm bảo status_id là một mảng nếu chỉ có một giá trị được chọn
        if (!is_array($filters['status_id']) && !empty($filters['status_id'])) {
            $filters['status_id'] = [$filters['status_id']];
        }

        $totalItem = $this->modelProject->countAll($filters);
        $projects = $this->modelProject->getProjectsByPage($page, $perPage, $filters);
        $totalPage = ceil($totalItem / $perPage);
        $statusOptions = $this->modelProjectStatus->getList(); // Lấy danh sách trạng thái để hiển thị trong bộ lọc

        View::render('projects/list', [
            'projects' => $projects,
            'currentPage' => $page,
            'perPage' => $perPage,
            'totalItem' => $totalItem,
            'totalPage' => $totalPage,
            'statusOptions' => $statusOptions,
            'currentFilters' => $filters,
            'pageTitle' => 'Danh sách dự án',
        ]);
    }

    /**
     * Hiển thị chi tiết dự án
     */
    public function show($id)
    {
        $project = $this->modelProject->find($id);
        if (!$project) {
            Response::redirect(URLROOT . '/projects');
        }

        // Lấy thông tin thành viên và công việc thuộc dự án
        $members = $this->modelProject->getProjectMembers($id);
        $tasks = $this->modelProject->getProjectTasks($id);

        // Lấy danh sách toàn bộ nhân viên để hiển thị trong Modal thêm thành viên
        $allUsers = $this->modelUser->getAllUsers();

        // Business Logic: Calculate stats here instead of in the View
        $stats = [
            'total' => count($tasks),
            'completed' => 0,
            'overdue' => 0,
            'percent' => 0
        ];

        $today = strtotime(date('Y-m-d'));
        foreach ($tasks as $task) {
            if (($task['status_slug'] ?? '') === 'done') {
                $stats['completed']++;
            }
            if (!empty($task['due_date']) && strtotime($task['due_date']) < $today && ($task['status_slug'] ?? '') !== 'done') {
                $stats['overdue']++;
            }
        }
        if ($stats['total'] > 0) {
            $stats['percent'] = (int) round(($stats['completed'] / $stats['total']) * 100);
        }

        // Sắp xếp thành viên ngay tại Controller
        usort($members, function($a, $b) {
            $roleOrder = ['manager' => 1, 'member' => 2, 'viewer' => 3];
            $orderA = $roleOrder[$a['role'] ?? 'member'] ?? 99;
            $orderB = $roleOrder[$b['role'] ?? 'member'] ?? 99;
            return $orderA <=> $orderB;
        });

        View::render('projects/detail', [
            'project' => $project,
            'members' => $members,
            'tasks' => $tasks,
            'stats' => $stats,
            'allUsers' => $allUsers,
            'pageTitle' => 'Chi tiết dự án: ' . $project['name'],
        ]);
    }

    /**
     * Hiển thị form tạo dự án mới
     */
    public function create()
    {
        if ($this->request->isGet()) {
            return View::render('projects/create', $this->getProjectFormViewData([
                'pageTitle' => 'Tạo dự án mới'
            ]));
        }
        return $this->store();
    }


    /**
     * Xử lý thêm nhiều thành viên vào dự án thông qua Modal
     */
    public function addMembers($id)
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . "/projects/$id");
        }

        $body = $this->request->getBody();
        $userIds = $body['user_ids'] ?? [];
        $role = trim($body['role'] ?? 'member');

        if (empty($userIds)) {
            Helper::setFlash('danger', 'Vui lòng chọn ít nhất một nhân viên');
        } else {
            $successCount = 0;
            foreach ($userIds as $userId) {
                if (!$this->modelProject->isMemberExists($id, $userId)) {
                    if ($this->modelProject->addMember($id, (int)$userId, $role)) {
                        $successCount++;
                    }
                }
            }

            if ($successCount > 0) {
                Helper::setFlash('success', "Đã thêm $successCount thành viên vào dự án thành công!");
            } else {
                Helper::setFlash('warning', 'Các nhân viên được chọn đã tham gia dự án này.');
            }
        }

        Response::redirect(URLROOT . "/projects/$id");
    }

    /**
     * Xử lý lưu dự án mới vào cơ sở dữ liệu
     */
    private function store()
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/projects/create');
            return null;
        }

        // Lấy dữ liệu từ form và validate
        $data = $this->getFormData();
        $this->validateProjectData($data);

        // Nếu có lỗi, render lại form kèm thông báo lỗi và dữ liệu cũ
        if (!$this->validator->passes()) {
            return View::render('projects/create', $this->getProjectFormViewData([
                'errors'    => $this->validator->getErrors(),
                'old'       => $this->request->getBody(),
                'pageTitle' => 'Tạo dự án mới'
            ]));
        }

        // Gọi Model xử lý lưu trữ
        $this->modelProject->createWithProjectCode($data);
        Helper::setFlash('success', 'Tạo dự án mới thành công!');
        Response::redirect(URLROOT . '/projects');
    }

    /**
     * Hiển thị form chỉnh sửa dự án
     */
    public function edit($id)
    {
        $project = $this->modelProject->find($id);
        if (!$project) {
            Response::redirect(URLROOT . '/projects');
        }

        View::render('projects/edit', $this->getProjectFormViewData([
            'project' => $project,
            'pageTitle' => 'Chỉnh sửa dự án'
        ]));
    }

    /**
     * Xử lý cập nhật thông tin dự án
     */
    public function update($id)
    {

        if (!$this->request->isPost()) {
            return;
        }

        $project = $this->modelProject->find($id);
        if (!$project) {
            Response::redirect(URLROOT . '/projects');
        }

        // Thu thập và kiểm tra dữ liệu
        $data = $this->getFormData();
        $this->validateProjectData($data);

        // Xử lý khi validation thất bại
        if (!$this->validator->passes()) {
            return View::render('projects/edit', $this->getProjectFormViewData([
                'project'   => $project,
                'errors'    => $this->validator->getErrors(),
                'old'       => $this->request->getBody(),
                'pageTitle' => 'Chỉnh sửa dự án'
            ]));
        }

        // Lưu thay đổi
        $this->modelProject->update($id, $data);
        Helper::setFlash('success', 'Cập nhật dự án thành công');
        Response::redirect(URLROOT . '/projects');
    }

    /**
     * Xử lý xóa dự án (Xóa mềm hoặc xóa cứng tùy thuộc vào cấu hình Model)
     */
    public function delete($id)
    {
        $this->modelProject->delete($id);
        Helper::setFlash('success', 'Đã xóa dự án vào thùng rác');
        Response::redirect(URLROOT . '/projects');
    }

    /**
     * Chuẩn hóa và lấy dữ liệu từ Request Body
     * @return array
     */
    private function getFormData(?array $body = null): array
    {
        $body = $body ?? $this->request->getBody();
        
        return [
            'name' => $body['name'] ?? '',
            'description' => $body['description'] ?? '',
            'status_id' => isset($body['status_id']) ? (int) $body['status_id'] : 0,
            'owner_id' => isset($body['owner_id']) ? (int) $body['owner_id'] : 0,
            'start_date' => !empty($body['start_date']) ? $body['start_date'] : null,
            'due_date' => !empty($body['due_date']) ? $body['due_date'] : null,
        ];
    }

    /**
     * Chuẩn bị dữ liệu bổ trợ cho View của Project Form (như danh sách nhân viên để chọn Owner)
     * @param array $data Dữ liệu hiện có
     * @return array
     */
    private function getProjectFormViewData(array $extra = []): array
    {
        $base = [
            'ownerOptions'  => $this->modelUser->getProjectOwnerOptions(),
            'statusOptions' => $this->modelProjectStatus->getList(),
            'action_url'    => isset($extra['project']) ? URLROOT . "/projects/{$extra['project']['id']}/edit" : URLROOT . '/projects/create'
        ];
        return array_merge($base, $extra);
    }


    /**
     * Thực hiện kiểm tra các quy tắc nghiệp vụ cho dữ liệu dự án
     * @param array $data
     */
    private function validateProjectData(array $data)
    {
        $this->validator->required('name', $data['name'], 'Tên dự án');
        $this->validator->selected('status_id', $data['status_id'], 'Trạng thái');

        // Kiểm tra tính hợp lệ của trạng thái từ Database
        if (!empty($data['status_id'])) {
            $statusIds = array_column($this->modelProjectStatus->getList(), 'id');
            if (!in_array($data['status_id'], $statusIds)) {
                $this->validator->addError('status_id', 'Trạng thái dự án không hợp lệ');
            }
        }

        // Kiểm tra chủ dự án
        if ($this->validator->selected('owner_id', $data['owner_id'], 'Chủ dự án')) {
            $ownerIds = array_map('intval', array_column($this->modelUser->getProjectOwnerOptions(), 'id'));
            if (!in_array($data['owner_id'], $ownerIds, true)) {
                $this->validator->addError('owner_id', 'Chủ dự án không hợp lệ');
            }
        }

        // Kiểm tra logic thời gian: Ngày kết thúc không được trước ngày bắt đầu
        if (!empty($data['start_date']) && !empty($data['due_date']) && strtotime($data['due_date']) < strtotime($data['start_date'])) {
            $this->validator->addError('due_date', 'Hạn xử lý phải lớn hơn hoặc bằng ngày bắt đầu');
        }
    }

    /**
     * Giải mã chuỗi JSON từ hidden input wizard; rỗng → [].
     *
     * @return array|null null nếu JSON lỗi (đã addError vào $fieldKey).
     */
    private function decodeJsonArrayField(string $raw, string $fieldKey): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $this->validator->addError($fieldKey, 'Dữ liệu không đúng định dạng JSON.');
            return null;
        }
        return $decoded;
    }

    /**
     * Kiểm tra mảng trạng thái công việc do client gửi (bước 2 wizard).
     */
    private function validateWizardTaskStatuses(array $rows): void
    {
        if ($rows === []) {
            return;
        }
        $defaults = 0;
        $dones = 0;
        $slugs = [];
        foreach ($rows as $r) {
            $name = trim((string) ($r['name'] ?? ''));
            $slug = strtolower(trim((string) ($r['slug'] ?? '')));
            if ($name === '') {
                $this->validator->addError('wizard_task_statuses', 'Mỗi trạng thái công việc phải có tên.');
                return;
            }
            if ($slug === '' || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $slug)) {
                $this->validator->addError('wizard_task_statuses', 'Slug trạng thái không hợp lệ (chữ thường, số, gạch ngang).');
                return;
            }
            if (in_array($slug, $slugs, true)) {
                $this->validator->addError('wizard_task_statuses', 'Slug trạng thái không được trùng nhau.');
                return;
            }
            $slugs[] = $slug;
            $color = (string) ($r['color'] ?? '#64748b');
            if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
                $this->validator->addError('wizard_task_statuses', 'Màu trạng thái không hợp lệ.');
                return;
            }
            if (!empty($r['is_default'])) {
                $defaults++;
            }
            if (!empty($r['is_done'])) {
                $dones++;
            }
        }
        if ($defaults !== 1 || $dones !== 1) {
            $this->validator->addError('wizard_task_statuses', 'Cần đúng một trạng thái mặc định và một trạng thái hoàn thành.');
        }
    }

    /**
     * Thành viên bổ sung (bước 3): user hợp lệ, không trùng owner, role thuộc whitelist.
     *
     * @param array<int, array<string, mixed>> $members
     */
    private function validateWizardMembers(array $members, int $ownerId): void
    {
        $allowedRoles = ['manager', 'member', 'viewer'];
        $validUserIds = array_flip(array_map('intval', array_column($this->modelUser->getProjectOwnerOptions(), 'id')));
        foreach ($members as $m) {
            $uid = (int) ($m['user_id'] ?? 0);
            $role = trim((string) ($m['role'] ?? 'member'));
            if ($uid <= 0) {
                continue;
            }
            if ($uid === $ownerId) {
                $this->validator->addError('wizard_members', 'Không thêm trưởng dự án vào danh sách thành viên phụ (đã có vai trò quản lý).');
                return;
            }
            if (!isset($validUserIds[$uid])) {
                $this->validator->addError('wizard_members', 'Có thành viên không tồn tại hoặc không hợp lệ.');
                return;
            }
            if (!in_array($role, $allowedRoles, true)) {
                $this->validator->addError('wizard_members', 'Vai trò thành viên không hợp lệ.');
                return;
            }
        }
    }

    private function wizardCreateViewData(array $extra = []): array
    {
        return array_merge([
            'action_url' => URLROOT . '/projects/createWizard',
            'pageTitle' => 'Thêm mới dự án',
        ], $extra);
    }

    // STEP BY STEP
    public function showStep()
    {
        View::render('projects/createWizard', $this->wizardCreateViewData());
    }

    /**
     * Lưu dự án từ wizard: POST form (CSRF + Auth) — cùng payload với hidden JSON bước 2–3.
     * Luồng DB: tạo bản ghi projects → task_statuses (tùy chỉnh hoặc clone mẫu hệ thống) → project_members (owner + danh sách).
     */
    public function postStep()
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/projects/createWizard');
            return;
        }

        $body = $this->request->getBody();
        // chuẩn hóa dữ liệu form
        $data = $this->getFormData();
        $taskRows = $this->decodeJsonArrayField((string) ($body['wizard_task_statuses'] ?? ''), 'wizard_task_statuses');
        $memberRows = $this->decodeJsonArrayField((string) ($body['wizard_members'] ?? ''), 'wizard_members');
        if ($taskRows === null || $memberRows === null) {
            return View::render('projects/createWizard', $this->wizardCreateViewData([
                'errors' => $this->validator->getErrors(),
                'old' => $body,
            ]));
        }

        $this->validateProjectData($data);
        $this->validateWizardTaskStatuses($taskRows);
        $this->validateWizardMembers($memberRows, (int) $data['owner_id']);

        if (!$this->validator->passes()) {
            return View::render('projects/createWizard', $this->wizardCreateViewData([
                'errors' => $this->validator->getErrors(),
                'old' => $body,
            ]));
        }

        try {
            $projectId = $this->modelProject->createWithProjectCode($data);

            // Nếu trạng thái rỗng sẽ thêm trạng thái mặc đinh (của project_id is null); 
            if ($taskRows === []) {
                $this->modelTaskStatus->cloneGlobalStatusesToProject($projectId);
            } else {
                // dùng vòng lặp thêm trạng thái
                foreach ($taskRows as $r) {
                    $this->modelTaskStatus->add([
                        'name' => trim((string) ($r['name'] ?? '')),
                        'slug' => strtolower(trim((string) ($r['slug'] ?? ''))),
                        'color' => (string) ($r['color'] ?? '#64748b'),
                        'project_id' => $projectId,
                        'is_active' => 1,
                        'is_default' => !empty($r['is_default']) ? 1 : 0,
                        'is_done' => !empty($r['is_done']) ? 1 : 0,
                    ]);
                }
            }

            $ownerId = (int) $data['owner_id'];
            if (!$this->modelProject->isMemberExists($projectId, $ownerId)) {
                $this->modelProject->addMember($projectId, $ownerId, 'manager');
            }
            foreach ($memberRows as $m) {
                $uid = (int) ($m['user_id'] ?? 0);
                $role = trim((string) ($m['role'] ?? 'member')) ?: 'member';
                if ($uid <= 0 || $uid === $ownerId) {
                    continue;
                }
                if (!$this->modelProject->isMemberExists($projectId, $uid)) {
                    $this->modelProject->addMember($projectId, $uid, $role);
                }
            }
        } catch (\Throwable $e) {
            Helper::setFlash('danger', 'Không thể tạo dự án: ' . $e->getMessage());
            Response::redirect(URLROOT . '/projects/createWizard');
            return;
        }

        Helper::setFlash('success', 'Tạo dự án thành công.');
        Response::redirect(URLROOT . '/projects/' . $projectId);
    }
}

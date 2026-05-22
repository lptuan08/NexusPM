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
use App\core\Session;
use App\helpers\AuthHelper;
use App\helpers\ListTableHelper;

/**
 * @property \App\core\Request $request
 * @property \App\core\Validator $validator
 */
class ProjectController extends Controller
{
    private const MEMBER_ROLES = ['manager', 'member', 'viewer'];

    private ProjectModel $modelProject;
    private UserModel $modelUser;
    private ProjectStatusModel $modelProjectStatus;
    private TaskStatusModel $modelTaskStatus;

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
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
     *
     * =============================================================
     * NHOM HIEN THI VA TRA CUU DU AN
     * =============================================================
     */
    public function index()
    {
        // lấy page nếu page
        $query = $this->request->getQuery();
        $page = $this->positiveInt($query['page'] ?? 1, 1);
        $perPage = ListTableHelper::perPage();
        // lấy danh sách trạng thái nạp cho bộ lọc
        $statusOptions = $this->modelProjectStatus->getList();

        // Normalize filter input once, then pass only safe values to the model.
        $filters = $this->getProjectFilters($query, $statusOptions);

        if (AuthHelper::can('projects.view.all')) {
            $totalItem = $this->modelProject->countAll($filters);
            $projects = $this->modelProject->getProjectsByPage($page, $perPage, $filters);
        } elseif (AuthHelper::can('projects.view.joined')) {
            $userId = $this->currentUserId();
            $totalItem = $this->modelProject->countForJoinedUser($userId, $filters);
            $projects = $this->modelProject->getProjectsByPageForJoinedUser($userId, $page, $perPage, $filters);
        } else {
            throw new \Exception('Bạn không có quyền xem danh sách dự án.', 403);
        }
        $totalPage = (int) ceil($totalItem / $perPage);

        foreach ($projects as &$project) {
            $project['can_update'] = AuthHelper::can('projects.update.all')
                || (AuthHelper::can('projects.update.joined') && $this->isJoinedProject($project));
            $project['can_delete'] = AuthHelper::can('projects.delete.all');
        }
        unset($project);

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
        $project = $this->findProjectOrRedirect($id);
        $projectId = (int) $project['id'];

        // Lấy thông tin thành viên và công việc thuộc dự án
        $this->requireCanViewProject($project);

        $members = $this->modelProject->getProjectMembers($projectId);
        $tasks = $this->modelProject->getProjectTasks($projectId);

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
            $isDone = !empty($task['status_is_done']) || ($task['status_slug'] ?? '') === 'done';
            if ($isDone) {
                $stats['completed']++;
            }
            $dueDate = !empty($task['due_date']) ? strtotime((string) $task['due_date']) : false;
            if ($dueDate !== false && $dueDate < $today && !$isDone) {
                $stats['overdue']++;
            }
        }
        if ($stats['total'] > 0) {
            $stats['percent'] = (int) round(($stats['completed'] / $stats['total']) * 100);
        }

        // Sắp xếp thành viên ngay tại Controller
        usort($members, function ($a, $b) {
            $roleOrder = ['manager' => 1, 'member' => 2, 'viewer' => 3];
            $orderA = $roleOrder[$a['role'] ?? 'member'] ?? 99;
            $orderB = $roleOrder[$b['role'] ?? 'member'] ?? 99;
            return $orderA <=> $orderB;
        });

        $canUpdateProject = AuthHelper::can('projects.update.all')
            || (AuthHelper::can('projects.update.joined') && $this->isJoinedProject($project));
        $canDeleteProject = AuthHelper::can('projects.delete.all');

        View::render('projects/detail', [
            'project' => $project,
            'members' => $members,
            'tasks' => $tasks,
            'stats' => $stats,
            'allUsers' => $allUsers,
            'canUpdateProject' => $canUpdateProject,
            'canDeleteProject' => $canDeleteProject,
            'pageTitle' => 'Chi tiết dự án: ' . $project['name'],
        ]);
    }

    /**
     * Hiển thị form tạo dự án mới
     *
     * =============================================================
     * NHOM TAO MOI DU AN
     * =============================================================
     */
    public function create()
    {
        if ($this->request->isGet()) {
            // The old simple create view is not present; keep the route usable by showing the wizard form.
            return View::render('projects/createWizard', $this->wizardCreateViewData());
        }
        return $this->store();
    }


    /**
     * Xử lý thêm nhiều thành viên vào dự án thông qua Modal
     *
     * =============================================================
     * NHOM THANH VIEN DU AN
     * =============================================================
     */
    public function addMembers($id)
    {
        $project = $this->findProjectOrRedirect($id);
        $projectId = (int) $project['id'];

        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . "/projects/$projectId");
        }

        $this->requireCanUpdateProject($project);

        $body = $this->request->post();
        $userIds = $this->normalizeIdList($body['user_ids'] ?? []);
        $role = $this->normalizeMemberRole($body['role'] ?? 'member');

        if (empty($userIds)) {
            Helper::setFlash('danger', 'Vui lòng chọn ít nhất một nhân viên');
        } else {
            $successCount = 0;
            $validUserIds = $this->validUserIdMap();
            foreach ($userIds as $userId) {
                if (!isset($validUserIds[$userId])) {
                    continue;
                }

                if (!$this->modelProject->isMemberExists($projectId, $userId)) {
                    if ($this->modelProject->addMember($projectId, $userId, $role)) {
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

        Response::redirect(URLROOT . "/projects/$projectId");
    }

    /**
     * Xử lý lưu dự án mới vào cơ sở dữ liệu
     */
    public function store()
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/projects/create');
            return null;
        }

        // Lấy dữ liệu từ form và validate
        $body = $this->request->post();
        $data = $this->getFormData($body);
        $this->validateProjectData($data);

        // Nếu có lỗi, render lại form kèm thông báo lỗi và dữ liệu cũ
        if (!$this->validator->passes()) {
            return View::render('projects/createWizard', $this->wizardCreateViewData([
                'errors'    => $this->validator->getErrors(),
                'old'       => $body,
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
     *
     * =============================================================
     * NHOM CAP NHAT DU AN
     * =============================================================
     */
    public function edit($id)
    {
        $project = $this->findProjectOrRedirect($id);

        $this->requireCanUpdateProject($project);

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
            Response::redirect(URLROOT . '/projects');
        }

        $project = $this->findProjectOrRedirect($id);
        $projectId = (int) $project['id'];

        // Thu thập và kiểm tra dữ liệu
        $this->requireCanUpdateProject($project);

        $body = $this->request->post();
        $data = $this->getFormData($body);
        $this->validateProjectData($data);

        // Xử lý khi validation thất bại
        if (!$this->validator->passes()) {
            return View::render('projects/edit', $this->getProjectFormViewData([
                'project'   => $project,
                'errors'    => $this->validator->getErrors(),
                'old'       => $body,
                'pageTitle' => 'Chỉnh sửa dự án'
            ]));
        }

        // Lưu thay đổi
        $this->modelProject->update($projectId, $data);
        Helper::setFlash('success', 'Cập nhật dự án thành công');
        Response::redirect(URLROOT . '/projects');
    }

    /**
     * Xử lý xóa dự án (Xóa mềm hoặc xóa cứng tùy thuộc vào cấu hình Model)
     *
     * =============================================================
     * NHOM XOA DU AN
     * =============================================================
     */
    public function delete($id)
    {
        $project = $this->findProjectOrRedirect($id);
        $projectId = (int) $project['id'];

        $this->requireCanDeleteProject($project);

        $this->modelProject->delete($projectId);
        Helper::setFlash('success', 'Đã xóa dự án vào thùng rác');
        Response::redirect(URLROOT . '/projects');
    }

    /**
     * Chuẩn hóa và lấy dữ liệu từ Request Body
     * @return array
     *
     * =============================================================
     * NHOM CHUAN HOA DU LIEU FORM
     * =============================================================
     */
    private function getFormData(?array $body = null): array
    {
        $body = $body ?? $this->request->post();

        return [
            'name' => trim((string) ($body['name'] ?? '')),
            'description' => trim((string) ($body['description'] ?? '')),
            'status_id' => $this->positiveInt($body['status_id'] ?? 0),
            'owner_id' => $this->positiveInt($body['owner_id'] ?? 0),
            'start_date' => $this->normalizeDate($body['start_date'] ?? null),
            'due_date' => $this->normalizeDate($body['due_date'] ?? null),
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
            'action_url'    => isset($extra['project']) ? URLROOT . "/projects/" . (int) $extra['project']['id'] . "/edit" : URLROOT . '/projects/create'
        ];
        return array_merge($base, $extra);
    }


    /**
     * Thực hiện kiểm tra các quy tắc nghiệp vụ cho dữ liệu dự án
     * @param array $data
     *
     * =============================================================
     * NHOM KIEM TRA DU LIEU DU AN
     * =============================================================
     */
    private function validateProjectData(array $data)
    {
        $this->validator->required('name', $data['name'], 'Tên dự án');
        $this->validator->max('name', $data['name'], 255, 'Tên dự án');
        $this->validator->max('description', $data['description'], 5000, 'Mô tả dự án');
        $this->validator->selected('status_id', $data['status_id'], 'Trạng thái');

        // Kiểm tra tính hợp lệ của trạng thái từ Database
        if (!empty($data['status_id'])) {
            $statusIds = array_map('intval', array_column($this->modelProjectStatus->getList(), 'id'));
            if (!in_array((int) $data['status_id'], $statusIds, true)) {
                $this->validator->addError('status_id', 'Trạng thái dự án không hợp lệ');
            }
        }

        if (!$this->isValidDateOrNull($data['start_date'])) {
            $this->validator->addError('start_date', 'Ngày bắt đầu không hợp lệ');
        }

        if (!$this->isValidDateOrNull($data['due_date'])) {
            $this->validator->addError('due_date', 'Hạn xử lý không hợp lệ');
        }

        // Kiểm tra chủ dự án
        if ($this->validator->selected('owner_id', $data['owner_id'], 'Chủ dự án')) {
            $ownerIds = array_map('intval', array_column($this->modelUser->getProjectOwnerOptions(), 'id'));
            if (!in_array($data['owner_id'], $ownerIds, true)) {
                $this->validator->addError('owner_id', 'Chủ dự án không hợp lệ');
            }
        }

        // Kiểm tra logic thời gian: Ngày kết thúc không được trước ngày bắt đầu
        if ($this->isValidDateOrNull($data['start_date'])
            && $this->isValidDateOrNull($data['due_date'])
            && !empty($data['start_date'])
            && !empty($data['due_date'])
            && strtotime($data['due_date']) < strtotime($data['start_date'])
        ) {
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
        $validUserIds = $this->validUserIdMap();
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
            if (!in_array($role, self::MEMBER_ROLES, true)) {
                $this->validator->addError('wizard_members', 'Vai trò thành viên không hợp lệ.');
                return;
            }
        }
    }

    /**
     * =============================================================
     * NHOM WIZARD TAO DU AN
     * =============================================================
     */
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

        $body = $this->request->post();
        // chuẩn hóa dữ liệu form
        $data = $this->getFormData($body);
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
                $role = $this->normalizeMemberRole($m['role'] ?? 'member');
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

    // permission
    /**
     * Lấy ID user đang đăng nhập.
     * Dự án hiện đã lưu thông tin user trong Session ở AuthController::initSession().
     *
     * =============================================================
     * NHOM PHAN QUYEN DU AN
     * =============================================================
     */
    private function currentUserId(): int
    {
        return (int) (Session::get('user')['id'] ?? 0);
    }

    private function isJoinedProject(array $project): bool
    {
        $userId = $this->currentUserId();
        $projectId = (int) ($project['id'] ?? 0);
        $ownerId = (int) ($project['owner_id'] ?? 0);

        return $userId > 0
            && ($ownerId === $userId || $this->modelProject->isActiveMember($projectId, $userId));
    }

    private function findProjectOrRedirect($id): array
    {
        $projectId = $this->positiveInt($id);
        if ($projectId <= 0) {
            Response::redirect(URLROOT . '/projects');
        }

        $project = $this->modelProject->find($projectId);
        if (!$project) {
            Response::redirect(URLROOT . '/projects');
        }

        return $project;
    }

    /**
     * =============================================================
     * NHOM BO LOC VA CHUAN HOA GIA TRI
     * =============================================================
     */
    private function getProjectFilters(array $query, array $statusOptions): array
    {
        $validStatusIds = array_flip(array_map('intval', array_column($statusOptions, 'id')));
        $requestedStatusIds = $this->normalizeIdList($query['status_id'] ?? []);
        $statusIds = array_values(array_filter($requestedStatusIds, static function (int $id) use ($validStatusIds): bool {
            return isset($validStatusIds[$id]);
        }));

        $startDate = $this->normalizeDate($query['start_date'] ?? null);
        $endDate = $this->normalizeDate($query['end_date'] ?? null);

        return [
            'status_id' => $statusIds,
            'start_date' => $this->isValidDateOrNull($startDate) ? $startDate : null,
            'end_date' => $this->isValidDateOrNull($endDate) ? $endDate : null,
        ];
    }

    private function normalizeIdList($value): array
    {
        $items = is_array($value) ? $value : [$value];
        $ids = [];

        foreach ($items as $item) {
            $id = $this->positiveInt($item);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    // xử lý validate giá trị page, nhỏ nhất là 1, mặc định là 0
    private function positiveInt($value, int $default = 0): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? $default : (int) $id;
    }

    private function normalizeDate($value): ?string
    {
        $date = trim((string) ($value ?? ''));

        return $date === '' ? null : $date;
    }

    private function isValidDateOrNull(?string $date): bool
    {
        if ($date === null || $date === '') {
            return true;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed instanceof \DateTimeImmutable && $parsed->format('Y-m-d') === $date;
    }

    private function normalizeMemberRole($role): string
    {
        $role = trim((string) $role);

        return in_array($role, self::MEMBER_ROLES, true) ? $role : 'member';
    }

    private function validUserIdMap(): array
    {
        return array_flip(array_map('intval', array_column($this->modelUser->getProjectOwnerOptions(), 'id')));
    }

    /**
     * Kiểm tra user hiện tại có quyền xem một project cụ thể hay không.
     *
     * Logic:
     * - Có projects.view.all: được xem mọi project.
     * - Có projects.view.joined: chỉ xem được project mình sở hữu hoặc là thành viên active.
     * - Không có quyền phù hợp: chặn 403.
     */
    private function requireCanViewProject(array $project): void
    {
        // Quyền cao nhất: xem tất cả dự án.
        if (AuthHelper::can('projects.view.all')) {
            return;
        }

        // Không có quyền xem theo member thì chặn ngay.
        if (!AuthHelper::can('projects.view.joined')) {
            throw new \Exception('Bạn không có quyền xem dự án này.', 403);
        }

        $userId = $this->currentUserId();
        $projectId = (int) ($project['id'] ?? 0);
        $ownerId = (int) ($project['owner_id'] ?? 0);

        // Owner của dự án được xem dự án của mình.
        if ($ownerId === $userId) {
            return;
        }

        // Thành viên active của dự án được xem dự án.
        if ($this->modelProject->isActiveMember($projectId, $userId)) {
            return;
        }

        // Có quyền member nhưng không thuộc project này thì vẫn bị chặn.
        throw new \Exception('Bạn không có quyền xem dự án này.', 403);
    }

    private function requireCanUpdateProject(array $project): void
    {
        if (AuthHelper::can('projects.update.all')) {
            return;
        }

        if (!AuthHelper::can('projects.update.joined')) {
            throw new \Exception('Bạn không có quyền cập nhật dự án này.', 403);
        }

        $userId = $this->currentUserId();
        $projectId = (int) ($project['id'] ?? 0);
        $ownerId = (int) ($project['owner_id'] ?? 0);

        if ($ownerId === $userId || $this->modelProject->isActiveMember($projectId, $userId)) {
            return;
        }

        throw new \Exception('Bạn không có quyền cập nhật dự án này.', 403);
    }

    private function requireCanDeleteProject(array $project): void
    {
        if (AuthHelper::can('projects.delete.all')) {
            return;
        }

        throw new \Exception('Bạn không có quyền xóa dự án này.', 403);
    }
}

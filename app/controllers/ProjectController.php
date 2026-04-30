<?php

/**
 * Controller ProjectController - Quản lý các hoạt động liên quan đến dự án
 */
class ProjectController extends Controller
{
    private $modelProject;
    private $modelUser;

    public function __construct()
    {
        parent::__construct();
        $this->modelProject = $this->model('ProjectModel');
        $this->modelUser = $this->model('UserModel');
    }

    /**
     * Hiển thị danh sách dự án có phân trang
     */
    public function index()
    {
        // Lấy tham số tìm kiếm và lọc
        // Lấy toàn bộ dự án dựa theo filter (không phân trang để chia 2 phần)
        // Lưu ý: Nếu dữ liệu cực lớn, ta nên dùng query riêng cho 2 phần kèm pagination
        $allProjects = $this->modelProject->getAllProjectsWithFilters();
        
        View::render('projects/list', [
            'projects' => $allProjects,
            'pageTitle' => 'Quản lý dự án',
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

        // Đảm bảo chủ dự án có trong danh sách thành viên để hiển thị đồng nhất
        $members = $this->ensureOwnerInMembers($project, $members);

        // Lấy danh sách toàn bộ nhân viên để hiển thị trong Modal thêm thành viên
        $allUsers = $this->modelUser->getAllUsers();

        View::render('projects/detail', [
            'project' => $project,
            'members' => $members,
            'tasks' => $tasks,
            'allUsers' => $allUsers,
            'pageTitle' => 'Chi tiết dự án: ' . $project['name'],
        ]);
    }

    /**
     * Hiển thị form tạo dự án mới
     */
    public function create()
    {
        View::render('projects/create', $this->getProjectFormViewData([
            'pageTitle' => 'Tạo dự án mới',
            'action_url' => URLROOT . '/projects/create',
        ]));
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
    public function store()
    {
        if (!$this->request->isPost()) {
            return;
        }

        // Lấy dữ liệu từ form và validate
        $data = $this->getFormData();
        $this->validateProjectData($data);

        // Nếu có lỗi, render lại form kèm thông báo lỗi và dữ liệu cũ
        if (!$this->validator->passes()) {
            return View::render('projects/create', $this->getProjectFormViewData([
                'errors' => $this->validator->getErrors(),
                'old' => $this->request->getBody(),
                'pageTitle' => 'Tạo dự án mới',
                'action_url' => URLROOT . '/projects/create',
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

        View::render('projects/create', $this->getProjectFormViewData([
            'project' => $project,
            'pageTitle' => 'Chỉnh sửa dự án',
            'action_url' => URLROOT . "/projects/{$id}/edit",
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
            return View::render('projects/create', $this->getProjectFormViewData([
                'project' => $project,
                'errors' => $this->validator->getErrors(),
                'old' => $this->request->getBody(),
                'pageTitle' => 'Chỉnh sửa dự án',
                'action_url' => URLROOT . "/projects/{$id}/edit",
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
    private function getFormData()
    {
        $body = $this->request->getBody();

        return [
            'name' => trim($body['name'] ?? ''),
            'description' => trim($body['description'] ?? ''),
            'status' => $body['status'] ?? 'planning',
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
    private function getProjectFormViewData(array $data = [])
    {
        $data['ownerOptions'] = $this->modelUser->getProjectOwnerOptions();
        return $data;
    }

    /**
     * Kiểm tra và thêm chủ dự án vào danh sách thành viên nếu chưa có
     * (Hữu ích khi hiển thị danh sách nhân sự tham gia dự án ở trang chi tiết)
     * @return array
     */
    private function ensureOwnerInMembers(array $project, array $members)
    {
        if (empty($project['owner_id'])) {
            return $members;
        }

        foreach ($members as $member) {
            if ((int) ($member['id'] ?? 0) === (int) $project['owner_id']) {
                return $members;
            }
        }

        if (empty($project['owner_name'])) {
            return $members;
        }

        $members[] = [
            'id' => $project['owner_id'],
            'name' => $project['owner_name'],
            'avatar' => $project['owner_avatar'] ?? null,
            'email' => $project['owner_email'] ?? '',
            'role' => 'Chủ dự án',
            'joined_at' => $project['created_at'] ?? null,
        ];

        return $members;
    }

    /**
     * Thực hiện kiểm tra các quy tắc nghiệp vụ cho dữ liệu dự án
     * @param array $data
     */
    private function validateProjectData(array $data)
    {
        $this->validator->required('name', $data['name'], 'Tên dự án');
        $this->validator->required('status', $data['status'], 'Trạng thái');

        // Kiểm tra tính hợp lệ của trạng thái
        $allowedStatuses = ['planning', 'active', 'on_hold', 'completed'];
        if (!in_array($data['status'], $allowedStatuses, true)) {
            $this->validator->addError('status', 'Trạng thái dự án không hợp lệ');
        }

        // Kiểm tra chủ dự án
        if (empty($data['owner_id'])) {
            $this->validator->addError('owner_id', 'Chủ dự án không được để trống');
        } else {
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
}

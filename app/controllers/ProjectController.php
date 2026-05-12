<?php

namespace App\controllers;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
use App\models\ProjectModel;
use App\models\ProjectStatusModel;
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

    public function __construct()
    {
        parent::__construct();
        $this->modelProject = $this->model('ProjectModel');
        $this->modelUser = $this->model('UserModel');
        $this->modelProjectStatus = $this->model('ProjectStatusModel');
    }

    /**
     * Hiển thị danh sách dự án có phân trang
     */
    public function index()
    {
        $query = $this->request->getBody();
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
        if ($this->request->isGet()) {
            return View::render('projects/create', $this->getProjectFormViewData([
                'pageTitle' => 'Tạo dự án mới',
                'action_url' => URLROOT . '/projects/create'
            ]));
        } else {
            $data = $this->request->getBody();
            var_dump($data);
        }
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
    private function getProjectFormViewData(array $data = [])
    {
        $data['ownerOptions'] = $this->modelUser->getProjectOwnerOptions();
        $data['statusOptions'] = $this->modelProjectStatus->getList();
        return $data;
    }

  
    /**
     * Thực hiện kiểm tra các quy tắc nghiệp vụ cho dữ liệu dự án
     * @param array $data
     */
    private function validateProjectData(array $data)
    {
        $this->validator->required('name', $data['name'], 'Tên dự án');
        $this->validator->required('status_id', $data['status_id'], 'Trạng thái');

        // Kiểm tra tính hợp lệ của trạng thái từ Database
        if (!empty($data['status_id'])) {
            $statusIds = array_column($this->modelProjectStatus->getList(), 'id');
            if (!in_array($data['status_id'], $statusIds)) {
                $this->validator->addError('status_id', 'Trạng thái dự án không hợp lệ');
            }
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

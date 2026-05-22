<?php

namespace App\controllers\admin;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
use App\models\TaskStatusModel;


class TaskStatusController extends Controller
{
    protected TaskStatusModel $modelTaskStatus;

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
    public function __construct()
    {
        parent::__construct();
        $this->modelTaskStatus = $this->model('TaskStatusModel');
    }

    /**
     * =============================================================
     * NHOM HIEN THI TRANG THAI CONG VIEC
     * =============================================================
     */
    public function list()
    {
        // Lấy project_id từ query string (?project_id=...)
        $query = $this->request->getQuery();
        $projectId = $query['project_id'] ?? null;

        // Chuyển sang kiểu int nếu có giá trị, nếu không để null để xử lý "Toàn hệ thống"
        $projectId = ($projectId !== null && $projectId !== '') ? (int)$projectId : null;

        $projects = $this->modelTaskStatus->listProject();
        $statuses = $this->modelTaskStatus->getStatuses($projectId);

        View::render('admin/settings/task_status', [
            'projects'  => $projects,
            'projectId' => $projectId,
            'statuses'  => $statuses
        ]);
    }

    /**
     * =============================================================
     * NHOM TAO MOI TRANG THAI CONG VIEC
     * =============================================================
     */
    public function store()
    {
        $data = $this->request->getBody();
        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;

        $isValid = $this->validateStatus($data, $projectId);

        if (!$isValid) {
            $projects = $this->modelTaskStatus->listProject();
            $statuses = $this->modelTaskStatus->getStatuses($projectId);

            View::render('admin/settings/task_status', [
                'projects'  => $projects,
                'projectId' => $projectId,
                'statuses'  => $statuses,
                'errors'    => $this->validator->getErrors(),
                'old'       => $data
            ]);
            return;
        }
        $taskStatus = [
            'name'      => $data['name'],
            'slug'      => $data['slug'],
            'color'     => $data['color'],
            'project_id' => $projectId,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 0,
            'is_default' => isset($data['is_default']) ? (int)$data['is_default'] : 0,
            'is_done'   => isset($data['is_done']) ? (int)$data['is_done'] : 0
        ];
        $this->modelTaskStatus->add($taskStatus);

        Helper::setFlash('success', 'Thêm trạng thái công việc thành công!');
        if (!empty($projectId)) {
            $url = '/settings/task?project_id=' . $projectId;
        } else {
            $url = '/settings/task';
        }
        Response::redirect(URLROOT . $url);
    }

    /**
     * =============================================================
     * NHOM CAP NHAT TRANG THAI CONG VIEC
     * =============================================================
     */
    public function edit(string $id)
    {
        if ($this->request->isPost()) {

            $data = $this->request->getBody();
            $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;

            // Truyền ID vào để check slug loại trừ bản ghi hiện tại
            $isValid = $this->validateStatus($data, $projectId, $id);

            if (!$isValid) {
                $projects = $this->modelTaskStatus->listProject();
                $statuses = $this->modelTaskStatus->getStatuses($projectId);

                View::render('admin/settings/task_status', [
                    'projects'  => $projects,
                    'projectId' => $projectId,
                    'statuses'  => $statuses,
                    'errors'    => $this->validator->getErrors(),
                    'old'       => $data
                ]);

                return;
            }

            $taskStatus = [
                'id' => $data['id'],
                'name' => $data['name'],
                'project_id' => $projectId,
                'slug' => $data['slug'],
                'color' => $data['color'],
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 0,
                // Đảm bảo các giá trị từ checkbox được ép kiểu về int (0 hoặc 1)
                // Nếu checkbox không được gửi lên (unchecked), giá trị mặc định là 0
                'is_default' => isset($data['is_default']) ? (int)$data['is_default'] : 0,
                'is_done' => isset($data['is_done']) ? (int)$data['is_done'] : 0,
            ];


            // Gọi model update...
            $this->modelTaskStatus->update($id, $taskStatus);
            Helper::setFlash('success', 'Cập nhật trạng thái thành công!');
            Response::redirect(URLROOT . '/settings/task' . ($projectId ? '?project_id=' . $projectId : ''));
        }
    }

    /**
     * FLOW XỬ LÝ REORDER (BACKEND):
     * 1. Nhận mảng status_ids từ form POST (đã đúng thứ tự mong muốn).
     * 2. Duyệt qua mảng: Vị trí (position) mới = Index của mảng + 1.
     * 3. Gọi Model để cập nhật hàng loạt trong một Database Transaction.
     *
     * =============================================================
     * NHOM SAP XEP THU TU TRANG THAI
     * =============================================================
     */
    public function reorder()
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/settings/task');
        }

        $body = $this->request->getBody();

        $statusIds = $body['status_ids'] ?? [];

        if (!empty($statusIds)) {
            $orderData = [];
            foreach ($statusIds as $index => $id) {
                $orderData[] = [
                    'id'       => (int)$id,
                    'position' => $index + 1 // Index bắt đầu từ 0 nên cần +1
                ];
            }

            // Thực hiện cập nhật vào Database
            $this->modelTaskStatus->updateOrder($orderData);
            Helper::setFlash('success', 'Cập nhật thứ tự trạng thái thành công!');
        }

        // Chuyển hướng về trang danh sách, giữ nguyên bộ lọc project_id nếu có
        Response::redirect(URLROOT . '/settings/task' . (isset($body['project_id']) && $body['project_id'] ? '?project_id=' . $body['project_id'] : ''));
    }

    /**
     * =============================================================
     * NHOM XOA TRANG THAI CONG VIEC
     * =============================================================
     */
    public function delete(string $id)
    {
        $body = $this->request->getBody();
        $projectId = !empty($body['project_id']) ? (int)$body['project_id'] : null;

        if ($this->modelTaskStatus->delete($id)) {
            Helper::setFlash('success', 'Xóa trạng thái công việc thành công!');
        } else {
            Helper::setFlash('danger', 'Có lỗi xảy ra khi xóa trạng thái công việc.');
        }

        Response::redirect(URLROOT . '/settings/task' . ($projectId ? '?project_id=' . $projectId : ''));
    }

    /**
     * Hàm validate chung cho Task Status
     *
     * =============================================================
     * NHOM KIEM TRA DU LIEU TRANG THAI
     * =============================================================
     */
    private function validateStatus(array $data, ?int $projectId, $id = null)
    {
        $this->validator->required('name', $data['name'], 'Tên trạng thái');
        $this->validator->required('slug', $data['slug'], "Slug");
        $this->validator->max('name', $data['name'], 45, 'Tên trạng thái');
        $this->validator->max('slug', $data['slug'], 45, 'Slug');
        if (!empty($data['slug'])) {
            $this->validator->slug('slug', $data['slug'], 'Slug');
        }
        $this->validator->color('color', $data['color'] ?? '#3b82f6');
        // Kiểm tra tính duy nhất của slug trong phạm vi dự án
        if (!empty($data['slug'])) {
            if ($this->modelTaskStatus->isSlugExists($data['slug'], $projectId, $id)) {
                $this->validator->addError('slug', "Mã định danh (slug) đã tồn tại");
            }
        }

        return $this->validator->passes();
    }
}

<?php

namespace App\controllers\admin;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
use App\models\ProjectStatusModel;

// use App\core\Validator;

class ProjectStatusController extends Controller
{
    protected ProjectStatusModel $modelProjectStatus;

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
    public function __construct()
    {
        parent::__construct();
        $this->modelProjectStatus = $this->model('ProjectStatusModel');
    }

    /**
     * =============================================================
     * NHOM HIEN THI TRANG THAI DU AN
     * =============================================================
     */
    public function list()
    {
        $data = $this->modelProjectStatus->getList();
        $statuses = $data;

        View::render('admin/settings/project_status', [
            'statuses' => $statuses
        ]);
    }

    /**
     * =============================================================
     * NHOM TAO MOI TRANG THAI DU AN
     * =============================================================
     */
    public function store()
    {
        // Lấy dữ liệu form
        $body = $this->request->getBody();
        $data = [
            'name'      => $body['name'] ?? '',
            'slug'      => $body['slug'] ?? '',
            'color'     => $body['color'] ?? '#3b82f6',
            'is_active' => isset($body['is_active']) ? 1 : 0
        ];

        // Validate 
        // 1. name
        $this->validator->required('name', $data['name'], 'Tên trạng thái');
        $this->validator->max('name', $data['name'], 45, 'Tên trạng thái');
        // 2. slug
        $this->validator->required('slug', $data['slug']);
        $this->validator->max('slug', $data['slug'], 45);
        if (!empty($data['slug'])) {
            $this->validator->slug('slug', $data['slug'], 'Slug');
        }
        // 3. color
        $this->validator->color('color', $data['color']);

        // 4 slug đã tồn tại
        $isSlugExits = $this->modelProjectStatus->isSlugExists($data['slug']);
        if ($isSlugExits) {
            $this->validator->addError('slug', "Mã định danh (slug) đã tồn tại");
        }

        if (!$this->validator->passes()) {
            $errors = $this->validator->getErrors();
            $statuses = $this->modelProjectStatus->getList();
            return View::render('admin/settings/project_status', [
                'statuses' => $statuses,
                'errors' => $errors,
                'old' => $body
            ]);
        }

        $this->modelProjectStatus->addProjectStatus($data);

        Helper::setFlash('success', 'Thêm trạng thái dự án mới thành công!');
        Response::redirect(URLROOT . '/settings/project');
    }

    /**
     * =============================================================
     * NHOM CAP NHAT TRANG THAI DU AN
     * =============================================================
     */
    public function update(string $id)
    {
        $body = $this->request->getBody();
        $data = [
            'name'      => $body['name'] ?? '',
            'slug'      => $body['slug'] ?? '',
            'color'     => $body['color'] ?? '#3b82f6',
            'is_active' => isset($body['is_active']) ? 1 : 0
        ];

        // Validate
        $this->validator->required('name', $data['name'], 'Tên trạng thái');
        $this->validator->max('name', $data['name'], 45, 'Tên trạng thái');
        $this->validator->required('slug', $data['slug']);
        $this->validator->max('slug', $data['slug'], 45);
        if (!empty($data['slug'])) {
            $this->validator->slug('slug', $data['slug'], 'Slug');
        }
        $this->validator->color('color', $data['color']);

        // Kiểm tra slug (trừ chính ID hiện tại)
        if ($this->modelProjectStatus->isSlugExists($data['slug'], $id)) {
            $this->validator->addError('slug', "Mã định danh (slug) đã tồn tại");
        }

        if (!$this->validator->passes()) {
            $statuses = $this->modelProjectStatus->getList();
            return View::render('admin/settings/project_status', [
                'statuses' => $statuses,
                'errors'   => $this->validator->getErrors(),
                'old'      => $body
            ]);
        }

        $this->modelProjectStatus->updateProjectStatus($id, $data);

        Helper::setFlash('success', 'Cập nhật trạng thái dự án thành công!');
        Response::redirect(URLROOT . '/settings/project');
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
            $this->modelProjectStatus->updateOrder($orderData);
            Helper::setFlash('success', 'Cập nhật thứ tự trạng thái thành công!');
        }

        Response::redirect(URLROOT . '/settings/project');
    }

    /**
     * =============================================================
     * NHOM XOA TRANG THAI DU AN
     * =============================================================
     */
    public function delete(string $id)
    {
        if ($this->modelProjectStatus->delete($id)) {
            Helper::setFlash('success', 'Xóa trạng thái dự án thành công!');
        } else {
            Helper::setFlash('danger', 'Có lỗi xảy ra khi xóa trạng thái dự án.');
        }

        Response::redirect(URLROOT . '/settings/project');
    }
}

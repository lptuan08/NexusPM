<?php

namespace App\controllers\admin;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
// use App\core\Validator;

class SettingsController extends Controller
{
    protected $modelSetting;
    public function __construct()
    {
        parent::__construct();
        $this->modelSetting = $this->model('SettingModel');
    }
    public function list()
    {
        $data = $this->modelSetting->getList();
        $statuses = $data;

        View::render('admin/settings/project_status', [
            'statuses' => $statuses
        ]);
    }
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
        // 3. color
        $this->validator->color('color', $data['color']);

        // 4 slug đã tồn tại
        $isSlugExits = $this->modelSetting->isSlugExits($data['slug']);
        if ($isSlugExits) {
            $this->validator->addError('slug', "Mã định danh (slug) đã tồn tại");
        }

        if (!$this->validator->passes()) {
            $errors = $this->validator->getErrors();
            return View::render('admin/settings/project_status', ['errors' => $errors, 'old' => $body]);
        }

        $this->modelSetting->addProjectStatus($data);

        Helper::setFlash('success', 'Thêm trạng thái dự án mới thành công!');
        Response::redirect(URLROOT . '/settings/project');
    }

    public function update($id)
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
        $this->validator->required('slug', $data['slug']);
        $this->validator->color('color', $data['color']);

        // Kiểm tra slug (trừ chính ID hiện tại)
        if ($this->modelSetting->isSlugExits($data['slug'], $id)) {
            $this->validator->addError('slug', "Mã định danh (slug) đã tồn tại");
        }

        if (!$this->validator->passes()) {
            $statuses = $this->modelSetting->getList();
            return View::render('admin/settings/project_status', [
                'statuses' => $statuses,
                'errors'   => $this->validator->getErrors(),
                'old'      => $body
            ]);
        }

        $this->modelSetting->updateProjectStatus($id, $data);

        Helper::setFlash('success', 'Cập nhật trạng thái dự án thành công!');
        Response::redirect(URLROOT . '/settings/project');
    }

    /**
     * FLOW XỬ LÝ REORDER (BACKEND):
     * 1. Nhận mảng status_ids từ form POST (đã đúng thứ tự mong muốn).
     * 2. Duyệt qua mảng: Vị trí (position) mới = Index của mảng + 1.
     * 3. Gọi Model để cập nhật hàng loạt trong một Database Transaction.
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
            $this->modelSetting->updateOrder($orderData);
            Helper::setFlash('success', 'Cập nhật thứ tự trạng thái thành công!');
        }

        Response::redirect(URLROOT . '/settings/project');
    }
}

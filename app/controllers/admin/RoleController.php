<?php

namespace App\controllers\admin;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
use App\models\RoleModel;
use App\models\PermissionModel;

class RoleController extends Controller
{
    protected RoleModel $modelRole;
    protected PermissionModel $modelPermission;

    public function __construct()
    {
        parent::__construct();
        $this->modelRole = $this->model('RoleModel');
        $this->modelPermission = $this->model('PermissionModel');
    }

    public function index()
    {
        $roles = $this->getRolesWithCount();
        View::render('admin/role/list', [
            'roles' => $roles,
            'pageTitle' => 'Quản lý vai trò & phân quyền'
        ]);
    }

    /**
     * Hỗ trợ lấy danh sách vai trò kèm số lượng quyền tương ứng
     */
    private function getRolesWithCount()
    {
        $roles = $this->modelRole->getRoles();
        foreach ($roles as &$role) {
            $role['permissions_count'] = $this->modelPermission->countPermissionsForRole($role['id']);
        }
        return $roles;
    }

    public function store()
    {
        if ($this->request->isPost()) {
            $body = $this->request->getBody();

            $data = [
                'name'        => $body['name'] ?? '',
                'slug'        => $body['slug'] ?? '',
                'description' => $body['description'] ?? '',
                'is_active'   => isset($body['is_active']) ? 1 : 0
            ];

            if (!$this->validateForm($data)) {
                $roles = $this->getRolesWithCount();
                View::render('admin/role/list', [
                    'roles' => $roles,
                    'pageTitle' => 'Quản lý vai trò & phân quyền',
                    'errors' => $this->validator->getErrors(),
                    'old' => $data
                ]);
                return;
            }
            $this->modelRole->add($data);
            Helper::setFlash('success', "Thêm vai trò thành công");
            Response::redirect(URLROOT . '/admin/roles');
        }
    }

    public function update(string $id)
    {
        if ($this->request->isPost()) {
            $role = $this->modelRole->find($id);
            if (!$role) {
                Helper::setFlash('danger', 'Vai trò không tồn tại.');
                Response::redirect(URLROOT . '/admin/roles');
            }

            $body = $this->request->getBody();

            $data = [
                'id'          => $id,
                'name'        => $body['name'] ?? '',
                'slug'        => $body['slug'] ?? '',
                'description' => $body['description'] ?? '',
                'is_active'   => isset($body['is_active']) ? 1 : 0,
                'is_system'   => (int)($role['is_system'] ?? 0)
            ];

            if (!$this->validateForm($data, $id)) {
                $roles = $this->getRolesWithCount();
                View::render('admin/role/list', [
                    'roles' => $roles,
                    'pageTitle' => 'Quản lý vai trò & phân quyền',
                    'errors' => $this->validator->getErrors(),
                    'old' => $data
                ]);
                return;
            }

            if ($this->modelRole->update($id, $data)) {
                Helper::setFlash('success', "Cập nhật vai trò thành công");
            } else {
                Helper::setFlash('danger', "Có lỗi xảy ra khi cập nhật");
            }
            Response::redirect(URLROOT . '/admin/roles');
        }
    }

    public function delete(string $id)
    {
        if (!$this->request->isPost()) {
            Response::redirect(URLROOT . '/admin/roles');
        }

        $role = $this->modelRole->find($id);

        if (!$role) {
            Helper::setFlash('danger', 'Vai trò không tồn tại.');
        } elseif (isset($role['is_system']) && $role['is_system'] == 1) {
            Helper::setFlash('danger', 'Không thể xóa vai trò mặc định của hệ thống.');
        } else {
            if ($this->modelRole->delete($id)) {
                Helper::setFlash('success', 'Xóa vai trò thành công!');
            } else {
                Helper::setFlash('danger', 'Có lỗi xảy ra trong quá trình xóa.');
            }
        }
        Response::redirect(URLROOT . '/admin/roles');
    }



    public function validateForm(array $data, $id = null)
    {
        $this->validator->required('name', $data['name'], "Tên vai trò");
        $this->validator->required('slug', $data['slug'], "Slug");
        $this->validator->max('name', $data['name'], 45, "Tên vai trò");
        $this->validator->max('slug', $data['slug'], 45, "Slug");
        $this->validator->max('description', $data['description'], 100, "Mô tả");
        $this->validator->isValidStatus('is_active', $data['is_active'], "Kích hoạt vai trò");

        if (!empty($data['slug'])) {
            if ($this->modelRole->isSlugExists($data['slug'], $id)) {
                $this->validator->addError('slug', "Mã định danh Slug đã tồn tại");
            }
        }
        if (!empty($data['name'])) {
            if ($this->modelRole->isNameExists($data['name'], $id)) {
                $this->validator->addError('name', "Tên vai trò đã tồn tại");
            }
        }

        return $this->validator->passes();
    }


}

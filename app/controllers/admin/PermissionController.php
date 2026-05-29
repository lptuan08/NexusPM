<?php

namespace App\controllers\admin;

use App\core\Controller;
use App\core\View;
use App\core\Response;
use App\helpers\Helper;
use App\models\RoleModel;
use App\models\PermissionModel;

class PermissionController extends Controller
{
    protected RoleModel $modelRole;
    protected PermissionModel $modelPermission;

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
    public function __construct()
    {
        parent::__construct();
        $this->modelRole = $this->model('RoleModel');
        $this->modelPermission = $this->model('PermissionModel');
    }

    /**
     * =============================================================
     * NHOM HIEN THI PHAN QUYEN
     * =============================================================
     */
    public function RolePermissions(string $id)
    {
        // 1. Lấy thông tin vai trò để hiển thị tiêu đề
        $role = $this->modelRole->find($id);
        if (!$role) {
            Helper::setFlash('danger', 'Vai trò không tồn tại.');
            Response::redirect(URLROOT . '/admin/roles');
        }

        // 2. Lấy tất cả quyền hiện có trong hệ thống (đã nhóm theo module)
        $permissionsByGroup = $this->modelPermission->getAllPermissionsGrouped();

        // 3. Lấy danh sách ID các quyền mà vai trò này đang có
        $activePermissions = $this->modelPermission->getActivePermissionIds($id);

        // 4. Lấy danh sách vai trò để người dùng chuyển nhanh khi phân quyền
        $roles = $this->modelRole->getRoles();

        // 5. Render giao diện
        View::render('admin/role/permissions', [
            'role' => $role,
            'roles' => $roles,
            'permissionsByGroup' => $permissionsByGroup,
            'activePermissions' => $activePermissions,
            'pageTitle' => 'Phân quyền: ' . $role['name']
        ]);
    }

    /**
     * Xử lý cập nhật danh sách quyền hạn cho một Vai trò
     * Phương thức: POST
     * Route: /admin/roles/{id}/permissions
     *
     * =============================================================
     * NHOM CAP NHAT PHAN QUYEN
     * =============================================================
     */
    public function RolePermissionsEdit(string $id)
    {
        // 1. Lấy toàn bộ dữ liệu từ form (chứa mảng permission_ids[])
        $data = $this->request->getBody();

        // 3. Danh sách ID các quyền được chọn (mặc định là mảng rỗng nếu không chọn quyền nào)
        $permissionIds = $data['permission_ids'] ?? [];

        // Kiểm tra an toàn: Nếu không có ID vai trò thì không thể thực hiện
        if (!$id) {
            Helper::setFlash('danger', 'Không tìm thấy thông tin vai trò.');
            Response::redirect(URLROOT . '/admin/roles');
        }
        /**
         * 4. Gọi Model để thực hiện đồng bộ quyền hạn (Sync)
         * 
         * GỢI Ý LOGIC CHO HÀM syncRolePermissions($roleId, $permissionIds) TRONG MODEL:
         * - Bước 1: Mở một Database Transaction (db->beginTransaction).
         * - Bước 2: Xóa tất cả các quyền hiện tại của Role này trong bảng trung gian (vd: role_permissions).
         * - Bước 3: Nếu mảng $permissionIds có dữ liệu, duyệt mảng và chèn các bản ghi mới vào bảng trung gian.
         * - Bước 4: Commit nếu mọi thứ thành công, hoặc Rollback nếu có bất kỳ lỗi nào xảy ra.
         */
        $result = $this->modelPermission->syncRolePermissions($id, $permissionIds);
        // 5. Thiết lập thông báo phản hồi dựa trên kết quả xử lý
        if ($result) {
            Helper::setFlash('success', 'Cập nhật phân quyền thành công!');
        } else {
            Helper::setFlash('danger', 'Có lỗi xảy ra khi cập nhật phân quyền.');
        }

        // 6. Chuyển hướng quay lại trang phân quyền của chính vai trò đó
        Response::redirect(URLROOT . "/admin/roles/$id/permissions");
    }
}

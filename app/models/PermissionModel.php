<?php

namespace App\models;

use App\core\Model;
use PDO;
use Exception;

class PermissionModel extends Model
{
    protected $tablePermission = 'permissions'; // Giả định bảng pivot lưu quan hệ vai trò-quyền
    protected $tableRolePermission = 'role_permissions'; // Giả định bảng pivot lưu quan hệ vai trò-quyền

    /**
     * =============================================================
     * NHOM THONG KE QUYEN THEO VAI TRO
     * =============================================================
     *
     * @param int|string $roleId ID vai tro can dem so quyen.
     * @return int So luong quyen dang gan voi vai tro.
     */
    public function countPermissionsForRole($roleId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->tableRolePermission} WHERE role_id = :role_id";
        return $this->db->query($sql, ['role_id' => $roleId])->fetchColumn();
    }

    /**
     * Lấy toàn bộ quyền trong hệ thống và nhóm theo Module
     *
     * =============================================================
     * NHOM TRUY VAN DANH SACH QUYEN
     * =============================================================
     *
     * @return array<string, array<int, array<string, mixed>>> Danh sach quyen nhom theo module.
     */
    public function getAllPermissionsGrouped()
    {
        $sql = "SELECT * FROM {$this->tablePermission} WHERE deleted_at IS NULL ORDER BY module ASC, name ASC";
        $permissions = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($permissions as $p) {
            $grouped[$p['module']][] = $p;
        }
        return $grouped;
    }

    /**
     * Lấy danh sách ID các quyền mà một vai trò đang sở hữu
     *
     * @param int|string $roleId ID vai tro can lay quyen dang kich hoat.
     * @return array<int, int|string> Danh sach permission_id.
     */
    public function getActivePermissionIds($roleId)
    {
        $sql = "SELECT permission_id FROM {$this->tableRolePermission} WHERE role_id = :role_id";
        return $this->db->query($sql, ['role_id' => $roleId])->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @param int|string $id ID vai tro can lay chi tiet quyen.
     * @return array<int, array<string, mixed>> Danh sach quyen cua vai tro.
     */
    public function getRolePermission($id)
    {
        $sql = "SELECT p.*
            FROM {$this->tableRolePermission} rp
            JOIN {$this->tablePermission} p 
                ON p.id = rp.permission_id
            WHERE rp.role_id = :id
            AND p.deleted_at IS NULL";
        return $this->db->query($sql, ['id' => $id])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * =============================================================
     * NHOM DONG BO QUYEN THEO VAI TRO
     * =============================================================
     *
     * @param int|string $id ID vai tro can dong bo quyen.
     * @param array<int, int|string> $permission Danh sach permission_id moi.
     * @return bool True neu dong bo thanh cong, false neu transaction that bai.
     */
    public function syncRolePermissions($id, $permission)
    {
        try {
            $this->db->beginTransaction();

            // 1. Xóa tất cả các quyền hiện tại của Role này
            $sqlDelete = "DELETE FROM {$this->tableRolePermission} WHERE role_id = :role_id";
            $this->db->query($sqlDelete, ['role_id' => $id]);

            // 2. Nếu có danh sách quyền mới, thực hiện chèn vào bảng trung gian
            if (!empty($permission)) {
                $placeholders = [];
                $params = [];
                foreach ($permission as $key => $permId) {
                    $placeholders[] = "(:role_id_{$key}, :permission_id_{$key})";
                    $params["role_id_{$key}"] = $id;
                    $params["permission_id_{$key}"] = $permId;
                }
                $stringPlaceholders = implode(",", $placeholders);
                $sql =  "INSERT INTO {$this->tableRolePermission} (role_id, permission_id) VALUES {$stringPlaceholders}";
                $this->db->query($sql, $params);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}

<?php
namespace App\models;

use App\core\Model;
use PDO;
class AuthModel extends Model
{
    protected $table = 'users';

    /**
     * =============================================================
     * NHOM TRUY VAN TAI KHOAN DANG NHAP
     * =============================================================
     *
     * @param string $email Email dang nhap can tim.
     * @return array|false Thong tin user kem vai tro, hoac false neu khong tim thay.
     */
    public function findEmailUser($email)
    {
        $sql = "SELECT u.*, r.slug as role_slug, r.name as role_name 
                FROM {$this->table} u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.email = :email AND u.deleted_at IS NULL";
        $stmt = $this->db->query($sql, ['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * =============================================================
     * NHOM TRUY VAN QUYEN HAN
     * =============================================================
     *
     * @param int|string $roleId ID vai tro can lay danh sach quyen.
     * @return array<int, string> Danh sach slug quyen cua vai tro.
     */
    public function getPermissionSlugsByRoleId($roleId)
    {
        $sql = "SELECT p.slug
                FROM role_permissions rp
                JOIN permissions p ON p.id = rp.permission_id
                WHERE rp.role_id = :role_id
                AND p.deleted_at IS NULL
                ORDER BY p.slug ASC";

        return $this->db->query($sql, ['role_id' => $roleId])->fetchAll(PDO::FETCH_COLUMN);
    }
}

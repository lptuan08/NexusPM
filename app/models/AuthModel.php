<?php
namespace App\models;

use App\core\Model;
use PDO;
class AuthModel extends Model
{
    protected $table = 'users';

    public function findEmailUser($email)
    {
        $sql = "SELECT u.*, r.slug as role_slug, r.name as role_name 
                FROM {$this->table} u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.email = :email AND u.deleted_at IS NULL";
        $stmt = $this->db->query($sql, ['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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

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
}

<?php
class AuthModel extends Model
{
    protected $table = 'users';
    public function findEmailUser($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND deleted_at IS NULL";
        $stmt = $this->db->query($sql, ['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

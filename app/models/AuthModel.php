<?php
class AuthModel extends Model
{
    public function findEmailUser($email)
    {
        $sql = "SELECT * FROM users WHERE email = :email AND deleted_at IS NULL";
        $stmt = $this->db->query($sql, ['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

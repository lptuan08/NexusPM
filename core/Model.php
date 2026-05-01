<?php
namespace App\core;

use App\core\Database;
use PDO;

abstract class Model
{
    protected $db;
    protected $table; // Sẽ được định nghĩa ở các Model con (ví dụ: 'users')

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Lấy toàn bộ bản ghi từ bảng
     */
    public function all()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm một bản ghi theo ID
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL";
        return $this->db->query($sql, ['id' => $id])->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm bản ghi mới (Alias cho insert)
     */
    public function create($data)
    {
        return $this->insert($data);
    }

    /**
     * Thêm bản ghi mới
     */
    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Cập nhật bản ghi theo ID
     */
    public function update($id, $data)
    {
        $condition = "id = :id";
        return $this->db->update($this->table, $data, $condition, ['id' => $id]);
    }

    /**
     * Xóa mềm bản ghi (Soft Delete)
     * Cập nhật thời gian vào cột deleted_at
     */
    public function delete($id)
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    // /**
    //  * Xóa vĩnh viễn bản ghi khỏi database (Hard Delete)
    //  */
    // public function forceDelete($id)
    // {
    //     $condition = "id = :id";
    //     return $this->db->delete($this->table, $condition, ['id' => $id]);
    // }

    /**
     * Đếm tổng số bản ghi (không bao gồm các bản ghi đã xóa mềm)
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE deleted_at IS NULL";
        return (int)$this->db->query($sql)->fetchColumn();
    }
}

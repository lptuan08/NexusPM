<?php
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
     * Xóa bản ghi theo ID (Hard Delete)
     */
    public function delete($id)
    {
        $condition = "id = :id";
        return $this->db->delete($this->table, $condition, ['id' => $id]);
    }
}

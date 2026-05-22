<?php

namespace App\models;

use App\core\Model;
use PDO;
use Exception;

class JobModel extends Model
{
    protected $table = 'job_titles';

    /**
     * =============================================================
     * NHOM TRUY VAN CHUC DANH
     * =============================================================
     *
     * @return array<int, array<string, mixed>> Danh sach chuc danh chua bi xoa.
     */
    public function getJobAll()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * =============================================================
     * NHOM TAO MOI CHUC DANH
     * =============================================================
     *
     * @param array<string, mixed> $data Du lieu chuc danh moi, can co key name.
     * @return mixed Ket qua truy van insert tu database layer.
     */
    public function createJob(array $data)
    {
        $sql = "INSERT INTO {$this->table} (name) VALUES (:name)";
        $params = ['name' => $data['name']];
        return $this->db->query($sql, $params);
    }

    /**
     * =============================================================
     * NHOM CAP NHAT CHUC DANH
     * =============================================================
     *
     * @param int|string $id ID chuc danh can cap nhat.
     * @param array<string, mixed> $data Du lieu cap nhat, can co key name.
     * @return mixed Ket qua truy van update tu database layer.
     */
    public function updateJob($id, $data)
    {
        $sql = "UPDATE {$this->table} SET name = :name WHERE id = :id";
        $params = [
            'id' => $id,
            'name' => $data['name']
        ];
        return $this->db->query($sql, $params);
    }

    /**
     * =============================================================
     * NHOM XOA CHUC DANH
     * =============================================================
     *
     * @param int|string $id ID chuc danh can xoa mem.
     * @return mixed Ket qua truy van update deleted_at tu database layer.
     */
    public function deleteJob($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }
}

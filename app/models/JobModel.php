<?php

namespace App\models;

use App\core\Model;
use PDO;
use Exception;

class JobModel extends Model
{
    protected $table = 'job_titles';
    public function getJobAll()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY id DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function createJob(array $data)
    {
        $sql = "INSERT INTO {$this->table} (name) VALUES (:name)";
        $params = ['name' => $data['name']];
        return $this->db->query($sql, $params);
    }
    public function updateJob($id, $data)
    {
        $sql = "UPDATE {$this->table} SET name = :name WHERE id = :id";
        $params = [
            'id' => $id,
            'name' => $data['name']
        ];
        return $this->db->query($sql, $params);
    }

    public function deleteJob($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }
}

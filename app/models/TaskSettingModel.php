<?php

namespace App\models;

use App\core\Model;
use Exception;
use PDO;

use function PHPSTORM_META\type;

class TaskSettingModel extends Model
{
    protected $table = 'task_statuses';
    // get list project (is_deleted = NULL)
    public function listProject()
    {
        $sql = "SELECT id, name, project_code FROM projects WHERE deleted_at is NULL ORDER BY created_at DESC";
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * Lấy danh sách trạng thái lọc theo dự án
     */
    public function getStatuses($projectId = null)
    {
        $sql = "SELECT ts.*, p.name as project_name, p.project_code 
                FROM {$this->table} ts 
                LEFT JOIN projects p ON ts.project_id = p.id 
                WHERE ts.deleted_at IS NULL";
        $params = [];

        if ($projectId) {
            $sql .= " AND ts.project_id = :project_id";
            $params['project_id'] = $projectId;
        } else {
            $sql .= " AND ts.project_id IS NULL";
        }

        $sql .= " ORDER BY ts.position ASC";
        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function isSlugExists($slug, $project_id, $excludeId = null)
    {
        // dùng SELECT EXITST kiểm tra tồn tại
        $sql = "SELECT EXISTS(
                SELECT 1
                FROM {$this->table}
                WHERE slug = :slug
                AND deleted_at IS NULL";

        $params = ['slug' => $slug];

        if ($project_id === null) {
            $sql .= " AND project_id IS NULL";
        } else {
            $sql .= " AND project_id = :project_id";
            $params['project_id'] = $project_id;
        }

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $sql .= " LIMIT 1)";
        $result = $this->db->query($sql, $params)->fetchColumn();
        return (bool)$result;
    }

    public function add($data, $project_id = NULL)
    {
        try {
            $this->db->beginTransaction();
            //1. Kiểm tra lấy giá trị position
            $sqlMaxPos = "SELECT MAX(position) as max_pos FROM {$this->table} WHERE" . " ";
            $params = [];
            if ($project_id == null) {
                $sqlMaxPos .= "project_id IS NULL FOR UPDATE";
            } else {
                $sqlMaxPos .= "project_id = :project_id FOR UPDATE";
                $params['project_id'] = $project_id;
            }
            $max = $this->db->query($sqlMaxPos, $params)->fetch()['max_pos'];
            $position = ($max ?? 0) + 1;

            //2. Insert record
            $sql = "INSERT INTO {$this->table} (name, project_id, slug, color, position, is_active) 
                    VALUES (:name, :project_id, :slug, :color, :position, :is_active)";
            $prams = [
                'name'      => $data['name'],
                'slug'      => $data['slug'],
                'color'     => $data['color'],
                'project_id' => $project_id,
                'position'  => $position,
                'is_active' => (int)$data['is_active']
            ];
            $this->db->query($sql, $prams);
            // Nếu mọi thứ ổn, xác nhận lưu vĩnh viễn các thay đổi
            $this->db->commit();
            return $max;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Lỗi khi thêm trạng thái công việc: " . $e->getMessage(), 500);
        }
    }
}

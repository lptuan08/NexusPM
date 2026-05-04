<?php

namespace App\models;

use App\core\Model;
use PDO;

class TaskSettingModel extends Model
{

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
        $sql = "SELECT * FROM task_statuses WHERE deleted_at IS NULL";
        $params = [];

        if ($projectId) {
            $sql .= " AND project_id = :project_id";
            $params['project_id'] = $projectId;
        } else {
            $sql .= " AND project_id IS NULL";
        }

        $sql .= " ORDER BY position ASC";
        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
}

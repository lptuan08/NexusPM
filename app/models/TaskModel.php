<?php

namespace App\models;

use App\core\Model;
use PDO;

class TaskModel extends Model
{
    protected $table = 'tasks';

    public function getAllTask()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY id DESC";
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * Lấy toàn bộ danh sách công việc theo bộ lọc (Không phân trang)
     */
    public function getAllTasks($filters = [])
    {
        $whereClauses = ["t.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['search'])) {
            $whereClauses[] = "(t.title LIKE :search OR t.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['project_id'])) {
            $whereClauses[] = "t.project_id = :project_id";
            $params[':project_id'] = (int)$filters['project_id'];
        }
        if (!empty($filters['assigned_to'])) {
            $whereClauses[] = "t.assigned_to = :assigned_to";
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['status_id'])) {
            $whereClauses[] = "t.status_id = :status_id";
            $params[':status_id'] = (int)$filters['status_id'];
        }

        $whereSql = implode(' AND ', $whereClauses);
        $sql = "SELECT t.*, 
                       p.name as project_name, 
                       u.name as assigned_name, u.avatar as assigned_avatar,
                       ts.name as status_name, ts.slug as status_slug, ts.color as status_color
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN task_statuses ts ON t.status_id = ts.id
                WHERE $whereSql ORDER BY t.id DESC";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskByIdProject($id = null)
    {
        //project_id = id
        if ($id == null) {
            $sql = "SELECT * FROM {$this->table} WHERE project_id IS NULL AND deleted_at IS NULL ORDER BY id DESC";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sql = "SELECT * FROM {$this->table} WHERE project_id = :id AND deleted_at IS NULL ORDER BY id DESC";
            $params = ['id' => $id];
            return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function create($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE :search OR description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['project_id'])) {
            $sql .= " AND project_id = :project_id";
            $params[':project_id'] = $filters['project_id'];
        }
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        if (!empty($filters['status_id'])) {
            $sql .= " AND status_id = :status_id";
            $params[':status_id'] = $filters['status_id'];
        }

        $result = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    public function getTasksByPage($page, $perPage, $filters = [])
    {
        $offset = ($page - 1) * $perPage;
        $whereClauses = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['search'])) {
            $whereClauses[] = "(t.title LIKE :search OR t.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['project_id'])) {
            $whereClauses[] = "t.project_id = :project_id";
            $params[':project_id'] = (int)$filters['project_id'];
        }
        if (!empty($filters['assigned_to'])) {
            $whereClauses[] = "t.assigned_to = :assigned_to";
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['status_id'])) {
            $whereClauses[] = "t.status_id = :status_id";
            $params[':status_id'] = (int)$filters['status_id'];
        }

        $whereSql = implode(' AND ', $whereClauses);
        $sql = "SELECT t.*, 
                       p.name as project_name, 
                       u.name as assigned_name, u.avatar as assigned_avatar,
                       ts.name as status_name, ts.slug as status_slug, ts.color as status_color
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN task_statuses ts ON t.status_id = ts.id
                WHERE $whereSql ORDER BY t.id DESC LIMIT :offset, :perPage";

        $params[':offset'] = $offset;
        $params[':perPage'] = $perPage;

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
}

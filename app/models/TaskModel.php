<?php

namespace App\models;

use App\core\Model;
use PDO;

class TaskModel extends Model
{
    protected $table = 'tasks';

    /**
     * Cột chọn chung cho danh sách task (JOIN project, assignee, trạng thái).
     */
    private function selectListColumns(): string
    {
        return "t.*,
                p.name AS project_name,
                u.name AS assigned_name,
                u.avatar AS assigned_avatar,
                ts.name AS status_name,
                ts.slug AS status_slug,
                ts.color AS status_color,
                ts.is_done AS status_is_done";
    }

    /**
     * JOIN: assignee lấy từ task_assignments (bản ghi mới nhất theo assigned_at).
     */
    private function fromListJoins(): string
    {
        return "FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON u.id = (
                    SELECT ta_sub.user_id
                    FROM task_assignments ta_sub
                    WHERE ta_sub.task_id = t.id
                    ORDER BY ta_sub.assigned_at DESC, ta_sub.user_id DESC
                    LIMIT 1
                )
                LEFT JOIN task_statuses ts ON t.status_id = ts.id";
    }

    /**
     * Gán người thực hiện (bảng task_assignments).
     */
    public function assignUserToTask(int $taskId, int $userId): bool
    {
        $sql = "INSERT INTO task_assignments (task_id, user_id, assigned_at)
                VALUES (:task_id, :user_id, NOW())";
        return (bool) $this->db->query($sql, [
            'task_id' => $taskId,
            'user_id' => $userId,
        ]);
    }

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
            $whereClauses[] = "EXISTS (
                SELECT 1 FROM task_assignments ta_f
                WHERE ta_f.task_id = t.id AND ta_f.user_id = :assigned_to
            )";
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['status_id'])) {
            $whereClauses[] = "t.status_id = :status_id";
            $params[':status_id'] = (int)$filters['status_id'];
        }

        $whereSql = implode(' AND ', $whereClauses);
        $sql = 'SELECT ' . $this->selectListColumns() . '
                ' . $this->fromListJoins() . "
                WHERE $whereSql ORDER BY t.id DESC";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskByIdProject($id = null)
    {
        $whereClauses = ['t.deleted_at IS NULL'];
        $params = [];

        if ($id === null) {
            $whereClauses[] = 't.project_id IS NULL';
        } else {
            $whereClauses[] = 't.project_id = :id';
            $params['id'] = $id;
        }

        $whereSql = implode(' AND ', $whereClauses);
        $sql = 'SELECT ' . $this->selectListColumns() . '
                ' . $this->fromListJoins() . "
                WHERE $whereSql ORDER BY t.id DESC";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
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
            $sql .= " AND EXISTS (
                SELECT 1 FROM task_assignments ta_f
                WHERE ta_f.task_id = {$this->table}.id AND ta_f.user_id = :assigned_to
            )";
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
        $whereClauses = ['t.deleted_at IS NULL'];
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
            $whereClauses[] = "EXISTS (
                SELECT 1 FROM task_assignments ta_f
                WHERE ta_f.task_id = t.id AND ta_f.user_id = :assigned_to
            )";
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['status_id'])) {
            $whereClauses[] = "t.status_id = :status_id";
            $params[':status_id'] = (int)$filters['status_id'];
        }

        $whereSql = implode(' AND ', $whereClauses);
        $sql = 'SELECT ' . $this->selectListColumns() . '
                ' . $this->fromListJoins() . "
                WHERE $whereSql ORDER BY t.id DESC LIMIT :offset, :perPage";

        $params[':offset'] = $offset;
        $params[':perPage'] = $perPage;

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
}

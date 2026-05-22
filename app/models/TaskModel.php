<?php

namespace App\models;

use App\core\Model;
use PDO;

class TaskModel extends Model
{
    protected $table = 'tasks';

    /**
     * Cột chọn chung cho danh sách task (JOIN project, assignee, trạng thái).
     *
     * =============================================================
     * NHOM CAU HINH TRUY VAN DANH SACH
     * =============================================================
     *
     * @return string Danh sach cot SELECT dung chung cho man hinh task.
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
     *
     * @return string Doan SQL JOIN dung chung cho danh sach task.
     */
    private function fromListJoins(): string
    {
        return "FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON u.id = (
                    SELECT ta_sub.user_id
                    FROM task_assignments ta_sub
                    WHERE ta_sub.task_id = t.id
                      AND ta_sub.deleted_at IS NULL
                    ORDER BY ta_sub.assigned_at DESC, ta_sub.user_id DESC
                    LIMIT 1
                )
                LEFT JOIN task_statuses ts ON t.status_id = ts.id";
    }

    /**
     * Gán người thực hiện (bảng task_assignments).
     *
     * =============================================================
     * NHOM PHAN CONG NGUOI THUC HIEN
     * =============================================================
     *
     * @param int $taskId ID cong viec can gan.
     * @param int $userId ID nguoi duoc gan.
     * @param int $assignedBy ID nguoi thuc hien thao tac gan.
     * @return bool True neu gan nguoi thuc hien thanh cong.
     */
    public function assignUserToTask(int $taskId, int $userId, int $assignedBy): bool
    {
        $sql = "INSERT INTO task_assignments (task_id, user_id, assigned_at, assigned_by, deleted_at)
                VALUES (:task_id, :user_id, NOW(), :assigned_by, NULL)
                ON DUPLICATE KEY UPDATE
                    assigned_at = NOW(),
                    assigned_by = VALUES(assigned_by),
                    deleted_at = NULL";
        return (bool) $this->db->query($sql, [
            'task_id' => $taskId,
            'user_id' => $userId,
            'assigned_by' => $assignedBy,
        ]);
    }

    /**
     * Xóa mềm các người thực hiện hiện tại trước khi gán lại người mới.
     *
     * @param int $taskId ID cong viec can xoa phan cong hien tai.
     * @return bool True neu xoa mem phan cong thanh cong.
     */
    public function removeAssignments(int $taskId): bool
    {
        $sql = "UPDATE task_assignments
                SET deleted_at = NOW()
                WHERE task_id = :task_id
                  AND deleted_at IS NULL";

        return (bool) $this->db->query($sql, ['task_id' => $taskId]);
    }

    /**
     * Helper chung để xây dựng WHERE clauses cho các phương thức lấy danh sách
     *
     * =============================================================
     * NHOM BO LOC DANH SACH CONG VIEC
     * =============================================================
     *
     * @param array<string, mixed> $filters Bo loc danh sach cong viec.
     * @return array{0:array<int, string>, 1:array<string, mixed>} WHERE clauses va params bind.
     */
    private function buildFilterWhere(array $filters): array
    {
        $where = ["t.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(t.title LIKE :search OR t.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['project_id'])) {
            $where[] = "t.project_id = :project_id";
            $params[':project_id'] = (int)$filters['project_id'];
        }
        if (!empty($filters['assigned_to'])) {
            $where[] = "EXISTS (
                SELECT 1
                FROM task_assignments ta_f
                WHERE ta_f.task_id = t.id
                  AND ta_f.user_id = :assigned_to
                  AND ta_f.deleted_at IS NULL
            )";
            $params[':assigned_to'] = (int)$filters['assigned_to'];
        }
        if (!empty($filters['status_id'])) {
            $where[] = "t.status_id = :status_id";
            $params[':status_id'] = (int)$filters['status_id'];
        }
        return [$where, $params];
    }

    /**
     * Lấy toàn bộ danh sách công việc theo bộ lọc (Không phân trang)
     *
     * =============================================================
     * NHOM TRUY VAN CONG VIEC
     * =============================================================
     *
     * @param array<string, mixed> $filters Bo loc cong viec.
     * @return array<int, array<string, mixed>> Danh sach cong viec.
     */
    public function getAllTasks($filters = [])
    {
        [$where, $params] = $this->buildFilterWhere($filters);
        $whereSql = implode(' AND ', $where);
        $sql = 'SELECT ' . $this->selectListColumns() . '
                ' . $this->fromListJoins() . "
                WHERE $whereSql ORDER BY t.id DESC";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int|string|null $id ID du an, hoac null de lay task khong thuoc du an.
     * @return array<int, array<string, mixed>> Danh sach cong viec theo du an.
     */
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

    /**
     * @param array<string, mixed> $filters Bo loc dung de dem cong viec.
     * @return int Tong so cong viec thoa bo loc.
     */
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
                WHERE ta_f.task_id = {$this->table}.id
                  AND ta_f.user_id = :assigned_to
                  AND ta_f.deleted_at IS NULL
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

    /**
     * @param int $page Trang hien tai.
     * @param int $perPage So ban ghi tren moi trang.
     * @param array<string, mixed> $filters Bo loc cong viec.
     * @return array<int, array<string, mixed>> Danh sach cong viec theo trang.
     */
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
                WHERE ta_f.task_id = t.id
                  AND ta_f.user_id = :assigned_to
                  AND ta_f.deleted_at IS NULL
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

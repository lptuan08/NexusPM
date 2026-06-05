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
     * Xây dựng điều kiện WHERE dùng chung cho các truy vấn danh sách task.
     *
     * Ngoài các bộ lọc UI như search/project/assignee/status, hàm còn nhận
     * visibility_user_id, visibility_project và visibility_own để giới hạn dữ liệu
     * theo quyền xem task hiện tại.
     *
     * =============================================================
     * NHOM BO LOC DANH SACH CONG VIEC
     * =============================================================
     *
     * @param array<string, mixed> $filters Bo loc danh sach cong viec.
     * @return array{0:array<int, string>, 1:array<string, mixed>} WHERE clauses va params bind.
     */
    /**
     * Lay ID nguoi thuc hien active moi nhat cua task.
     *
     * @param int $taskId ID cong viec can lay nguoi thuc hien.
     * @return int|null ID nguoi thuc hien hoac null neu task chua duoc giao.
     */
    public function getActiveAssigneeId(int $taskId): ?int
    {
        $sql = "SELECT user_id
                FROM task_assignments
                WHERE task_id = :task_id
                  AND deleted_at IS NULL
                ORDER BY assigned_at DESC, user_id DESC
                LIMIT 1";

        $assigneeId = $this->db->query($sql, ['task_id' => $taskId])->fetchColumn();

        return $assigneeId !== false ? (int) $assigneeId : null;
    }

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

        if (!empty($filters['visibility_user_id'])) {
            $userId = (int) $filters['visibility_user_id'];
            $visibility = [];

            if (!empty($filters['visibility_project'])) {
                $visibility[] = "(p.owner_id = :visibility_project_owner_id OR EXISTS (
                    SELECT 1
                    FROM project_members pm_v
                    WHERE pm_v.project_id = t.project_id
                      AND pm_v.user_id = :visibility_project_member_id
                      AND pm_v.is_active = 1
                      AND pm_v.left_at IS NULL
                ))";
                $params[':visibility_project_owner_id'] = $userId;
                $params[':visibility_project_member_id'] = $userId;
            }

            if (!empty($filters['visibility_own'])) {
                $visibility[] = "(t.created_by = :visibility_own_created_by OR EXISTS (
                    SELECT 1
                    FROM task_assignments ta_o
                    WHERE ta_o.task_id = t.id
                      AND ta_o.user_id = :visibility_own_user_id
                      AND ta_o.deleted_at IS NULL
                ))";
                $params[':visibility_own_created_by'] = $userId;
                $params[':visibility_own_user_id'] = $userId;
            }

            if ($visibility !== []) {
                $where[] = '(' . implode(' OR ', $visibility) . ')';
            } else {
                $where[] = '1 = 0';
            }
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
     * Lấy danh sách task thuộc một dự án cụ thể.
     *
     * Nếu $id là null, hàm trả về các task không gắn với dự án nào.
     *
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
     * Đếm số lượng task thỏa bộ lọc và quyền xem.
     *
     * @param array<string, mixed> $filters Bo loc dung de dem cong viec.
     * @return int Tong so cong viec thoa bo loc.
     */
    public function countAll($filters = [])
    {
        [$where, $params] = $this->buildFilterWhere($filters);
        $whereSql = implode(' AND ', $where);
        $sql = 'SELECT COUNT(*) as count
                ' . $this->fromListJoins() . "
                WHERE $whereSql";
        $result = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }

    /**
     * Lấy danh sách task theo trang, bộ lọc và quyền xem.
     *
     * @param int $page Trang hien tai.
     * @param int $perPage So ban ghi tren moi trang.
     * @param array<string, mixed> $filters Bo loc cong viec.
     * @return array<int, array<string, mixed>> Danh sach cong viec theo trang.
     */
    public function getTasksByPage($page, $perPage, $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        [$where, $params] = $this->buildFilterWhere($filters);
        $whereSql = implode(' AND ', $where);
        $sql = 'SELECT ' . $this->selectListColumns() . '
                ' . $this->fromListJoins() . "
                WHERE $whereSql ORDER BY t.id DESC LIMIT :offset, :perPage";

        $params[':offset'] = $offset;
        $params[':perPage'] = $perPage;

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cập nhật task và ghi lịch sử nếu trạng thái thay đổi.
     *
     * @param int $taskId ID công việc cần cập nhật.
     * @param array<string, mixed> $data Dữ liệu cập nhật.
     * @param int $changedBy ID người thực hiện thay đổi.
     * @return bool True nếu cập nhật thành công.
     */
    public function updateWithStatusHistory(int $taskId, array $data, int $changedBy): bool
    {
        $hasStatusChange = array_key_exists('status_id', $data);
        $oldStatusId = null;

        if ($hasStatusChange) {
            $oldStatusId = $this->db->query(
                "SELECT status_id FROM {$this->table} WHERE id = :id AND deleted_at IS NULL FOR UPDATE",
                ['id' => $taskId]
            )->fetchColumn();
            $oldStatusId = $oldStatusId !== false ? (int) $oldStatusId : null;
        }

        $result = $this->update($taskId, $data);

        if ($hasStatusChange && $oldStatusId !== null) {
            $newStatusId = (int) $data['status_id'];
            if ($newStatusId > 0 && $newStatusId !== $oldStatusId) {
                $this->recordStatusHistory($taskId, $oldStatusId, $newStatusId, $changedBy);
            }
        }

        return (bool) $result;
    }

    /**
     * Ghi một dòng lịch sử trạng thái cho task.
     *
     * @param int $taskId ID công việc.
     * @param int|null $fromStatusId Trạng thái cũ, null nếu là trạng thái khởi tạo.
     * @param int $toStatusId Trạng thái mới.
     * @param int $changedBy ID người thao tác, null trên DB nếu không xác định.
     * @return bool True nếu ghi thành công.
     */
    public function recordStatusHistory(int $taskId, ?int $fromStatusId, int $toStatusId, int $changedBy): bool
    {
        if ($taskId <= 0 || $toStatusId <= 0 || ($fromStatusId !== null && $fromStatusId === $toStatusId)) {
            return false;
        }

        $sql = "INSERT INTO task_status_histories
                    (task_id, from_status_id, to_status_id, changed_by, changed_at)
                VALUES
                    (:task_id, :from_status_id, :to_status_id, :changed_by, NOW())";

        return (bool) $this->db->query($sql, [
            'task_id' => $taskId,
            'from_status_id' => $fromStatusId,
            'to_status_id' => $toStatusId,
            'changed_by' => $changedBy > 0 ? $changedBy : null,
        ]);
    }

    /**
     * Lấy dữ liệu biểu đồ nhịp công việc theo ngày.
     *
     * =============================================================
     * NHOM DASHBOARD
     * =============================================================
     *
     * Dataset "created" đếm task được tạo mới theo created_at.
     * Dataset "completed" đếm các lần task chuyển từ trạng thái chưa hoàn thành
     * sang trạng thái có task_statuses.is_done = 1.
     *
     * @param array<string, mixed> $filters Bộ lọc quyền xem task.
     * @param \DateTimeInterface $startAt Mốc bắt đầu.
     * @param \DateTimeInterface $endAt Mốc kết thúc.
     * @return array{created:array<string, int>, completed:array<string, int>}
     */
    public function getTaskTrendByDate(array $filters, \DateTimeInterface $startAt, \DateTimeInterface $endAt): array
    {
        [$where, $params] = $this->buildFilterWhere($filters);
        $whereSql = implode(' AND ', $where);

        $createdParams = $params;
        $createdParams[':trend_start'] = $startAt->format('Y-m-d H:i:s');
        $createdParams[':trend_end'] = $endAt->format('Y-m-d H:i:s');

        $createdSql = "SELECT DATE(t.created_at) AS trend_day, COUNT(*) AS total
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE {$whereSql}
                  AND t.created_at BETWEEN :trend_start AND :trend_end
                GROUP BY DATE(t.created_at)";

        $completedParams = $params;
        $completedParams[':trend_start'] = $startAt->format('Y-m-d H:i:s');
        $completedParams[':trend_end'] = $endAt->format('Y-m-d H:i:s');

        $completedSql = "SELECT DATE(h.changed_at) AS trend_day, COUNT(DISTINCT h.task_id) AS total
                FROM task_status_histories h
                INNER JOIN {$this->table} t ON t.id = h.task_id
                LEFT JOIN projects p ON t.project_id = p.id
                INNER JOIN task_statuses to_status ON to_status.id = h.to_status_id
                LEFT JOIN task_statuses from_status ON from_status.id = h.from_status_id
                WHERE {$whereSql}
                  AND h.from_status_id IS NOT NULL
                  AND to_status.is_done = 1
                  AND COALESCE(from_status.is_done, 0) = 0
                  AND h.changed_at BETWEEN :trend_start AND :trend_end
                GROUP BY DATE(h.changed_at)";

        return [
            'created' => $this->fetchTrendMap($createdSql, $createdParams),
            'completed' => $this->fetchTrendMap($completedSql, $completedParams),
        ];
    }

    /**
     * Chuyển kết quả trend SQL thành map YYYY-mm-dd => total.
     *
     * @param string $sql Câu truy vấn trend.
     * @param array<string, mixed> $params Tham số bind.
     * @return array<string, int>
     */
    private function fetchTrendMap(string $sql, array $params): array
    {
        $rows = $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
        $map = [];

        foreach ($rows as $row) {
            $day = (string) ($row['trend_day'] ?? '');
            if ($day !== '') {
                $map[$day] = (int) ($row['total'] ?? 0);
            }
        }

        return $map;
    }

    /**
     * Lấy số liệu tổng hợp cho các thẻ thống kê trên Dashboard.
     *
     * =============================================================
     * NHOM DASHBOARD
     * =============================================================
     *
     * Hàm này chỉ phục vụ Dashboard: đếm task mở, task quá hạn, task hoàn thành,
     * task đến hạn hôm nay và task mới trong 7 ngày gần nhất theo đúng phạm vi
     * quyền xem task của user hiện tại.
     *
     * @param array<string, mixed> $filters Bộ lọc quyền xem task.
     * @param \DateTimeInterface $today Ngày hiện tại theo timezone Dashboard.
     * @return array<string, int> Số liệu tổng hợp cho các thẻ thống kê.
     */
    public function getDashboardTaskSummary(array $filters, \DateTimeInterface $today): array
    {
        [$where, $params] = $this->buildFilterWhere($filters);
        $whereSql = implode(' AND ', $where);

        $params[':today'] = $today->format('Y-m-d');
        $params[':recent_start'] = $today->modify('-6 days')->format('Y-m-d 00:00:00');
        $params[':recent_end'] = $today->format('Y-m-d 23:59:59');

        $sql = 'SELECT
                    COUNT(*) AS total_tasks,
                    SUM(CASE WHEN COALESCE(ts.is_done, 0) = 0 THEN 1 ELSE 0 END) AS open_tasks,
                    SUM(CASE WHEN COALESCE(ts.is_done, 0) = 1 THEN 1 ELSE 0 END) AS completed_tasks,
                    SUM(CASE
                        WHEN t.due_date IS NOT NULL
                         AND t.due_date < :today
                         AND COALESCE(ts.is_done, 0) = 0
                        THEN 1 ELSE 0
                    END) AS overdue_tasks,
                    SUM(CASE
                        WHEN t.due_date = :today
                         AND COALESCE(ts.is_done, 0) = 0
                        THEN 1 ELSE 0
                    END) AS due_today_tasks,
                    SUM(CASE
                        WHEN t.created_at BETWEEN :recent_start AND :recent_end
                        THEN 1 ELSE 0
                    END) AS created_recent_tasks
                ' . $this->fromListJoins() . "
                WHERE {$whereSql}";

        $row = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_tasks' => (int) ($row['total_tasks'] ?? 0),
            'open_tasks' => (int) ($row['open_tasks'] ?? 0),
            'completed_tasks' => (int) ($row['completed_tasks'] ?? 0),
            'overdue_tasks' => (int) ($row['overdue_tasks'] ?? 0),
            'due_today_tasks' => (int) ($row['due_today_tasks'] ?? 0),
            'created_recent_tasks' => (int) ($row['created_recent_tasks'] ?? 0),
        ];
    }

    /**
     * Lấy danh sách công việc ưu tiên hiển thị trên Dashboard.
     *
     * Hàm này chỉ phục vụ Dashboard: ưu tiên task chưa hoàn thành, quá hạn/đến hạn
     * gần, priority cao và vẫn tôn trọng phạm vi quyền xem task của user hiện tại.
     *
     * @param array<string, mixed> $filters Bộ lọc quyền xem task.
     * @param \DateTimeInterface $today Ngày hiện tại theo timezone Dashboard.
     * @param int $limit Số công việc tối đa cần lấy.
     * @return array<int, array<string, mixed>> Danh sách công việc ưu tiên.
     */
    public function getDashboardPriorityTasks(array $filters, \DateTimeInterface $today, int $limit = 4): array
    {
        [$where, $params] = $this->buildFilterWhere($filters);
        $where[] = 'COALESCE(ts.is_done, 0) = 0';
        $whereSql = implode(' AND ', $where);

        $params[':today'] = $today->format('Y-m-d');
        $params[':limit'] = max(1, $limit);

        $sql = 'SELECT ' . $this->selectListColumns() . ',
                       CASE t.priority
                           WHEN \'urgent\' THEN 1
                           WHEN \'high\' THEN 2
                           WHEN \'medium\' THEN 3
                           ELSE 4
                       END AS priority_rank
                ' . $this->fromListJoins() . "
                WHERE {$whereSql}
                ORDER BY
                    CASE
                        WHEN t.due_date IS NOT NULL AND t.due_date < :today THEN 0
                        WHEN t.due_date = :today THEN 1
                        WHEN t.due_date IS NOT NULL THEN 2
                        ELSE 3
                    END ASC,
                    priority_rank ASC,
                    CASE WHEN t.due_date IS NULL THEN 1 ELSE 0 END ASC,
                    t.due_date ASC,
                    t.updated_at DESC
                LIMIT :limit";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra user có đang được phân công active vào task hay không.
     *
     * @param int $taskId ID task cần kiểm tra.
     * @param int $userId ID user cần kiểm tra.
     * @return bool True nếu user đang được phân công vào task.
     */
    public function isTaskAssignedToUser(int $taskId, int $userId): bool
    {
        $sql = "SELECT COUNT(*)
                FROM task_assignments
                WHERE task_id = :task_id
                  AND user_id = :user_id
                  AND deleted_at IS NULL";

        return (int) $this->db->query($sql, [
            'task_id' => $taskId,
            'user_id' => $userId,
        ])->fetchColumn() > 0;
    }
}

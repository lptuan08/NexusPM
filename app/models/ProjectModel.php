<?php

namespace App\models;

use App\core\Model;
use PDO;
use Exception;


class ProjectModel extends Model
{
    /**
     * @var \App\core\Database
     */
    protected $db;

    // Tên bảng tương ứng trong cơ sở dữ liệu
    protected $table = 'projects';
    protected $tableProjectMember = 'project_members';

    /**
     * Áp dụng bộ lọc danh sách project bằng tham số bind để tránh ghép SQL thủ công.
     */
    private function applyProjectFilters(string &$sql, array &$params, array $filters, string $alias = 'p'): void
    {
        if (!empty($filters['status_id']) && is_array($filters['status_id'])) {
            $placeholders = [];
            foreach (array_values($filters['status_id']) as $index => $statusId) {
                $key = "status_id_{$index}";
                $placeholders[] = ':' . $key;
                $params[$key] = (int) $statusId;
            }

            if ($placeholders !== []) {
                $sql .= " AND {$alias}.status_id IN (" . implode(',', $placeholders) . ")";
            }
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND {$alias}.start_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND {$alias}.due_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
    }

    /**
     * Lấy danh sách dự án có phân trang
     * 
     * @param int $page Trang hiện tại
     * @param int $perPage Số bản ghi trên mỗi trang
     * @return array Danh sách dự án kèm thông tin người sở hữu
     */
    public function getProjectsByPage($page, $perPage, $filters = [])
    {
        // Tính toán vị trí bắt đầu lấy dữ liệu
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, 
                       u.name AS manager_name, 
                       u.email AS owner_email, 
                       ps.name as status_name, 
                       ps.color as status_color, 
                       ps.slug as status_slug
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                LEFT JOIN project_statuses ps ON p.status_id = ps.id
                WHERE p.deleted_at IS NULL";

        $params = [];

        $this->applyProjectFilters($sql, $params, $filters);

        $sql .= " ORDER BY p.id DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = (int)$perPage;
        $params['offset'] = (int)$offset;
        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy toàn bộ dự án có lọc theo tìm kiếm và trạng thái
     * 
     * @param array $filters Mảng chứa 'search' và 'status'
     * @return array
     */
    public function getAllProjects()
    {
        $sql = "SELECT id, name, project_code FROM {$this->table} WHERE deleted_at IS NULL ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllProjectsWithFilters($filters = [])
    {
        $sql = "SELECT p.*, u.name AS owner_name, u.email AS owner_email, ps.name as status_name, ps.color as status_color, ps.slug as status_slug,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as task_count,
                (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) as member_count,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'done') as completed_task_count
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                LEFT JOIN project_statuses ps ON p.status_id = ps.id
                WHERE p.deleted_at IS NULL";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.project_code LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status_id'])) {
            $sql .= " AND p.status_id = :status_id";
            $params['status_id'] = $filters['status_id'];
        }

        $sql .= " ORDER BY p.id DESC";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm kiếm một dự án theo ID
     * 
     * @param int $id ID của dự án
     * @return array|bool Thông tin dự án hoặc false nếu không tìm thấy
     */
    public function find($id)
    {
        $sql = "SELECT p.*, u.name AS owner_name, u.email AS owner_email, u.avatar AS owner_avatar, ps.name as status_name, ps.color as status_color, ps.slug as status_slug
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                LEFT JOIN project_statuses ps ON p.status_id = ps.id
                WHERE p.id = :id AND p.deleted_at IS NULL";

        return $this->db->query($sql, ['id' => (int) $id])->fetch(PDO::FETCH_ASSOC);
    }





    /**
     * Lấy danh sách thành viên của một dự án
     * 
     * @param int $projectId ID dự án
     * @return array Danh sách thành viên và vai trò của họ
     */
    public function getProjectMembers($projectId)
    {
        $sql = "SELECT u.id, u.name, u.avatar, u.email, pm.role, pm.joined_at, pm.is_active, pm.left_at 
                FROM project_members pm
                JOIN users u ON pm.user_id = u.id
                WHERE pm.project_id = :project_id AND u.deleted_at IS NULL";

        return $this->db->query($sql, ['project_id' => (int) $projectId])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách công việc thuộc dự án
     * 
     * @param int $projectId ID dự án
     * @return array Danh sách các công việc được sắp xếp theo thời gian tạo mới nhất
     */
    public function getProjectTasks($projectId)
    {
        $sql = "SELECT 
                        t.id AS task_id,
                        ta.user_id,
                        ta.assigned_at,
                        ta.assigned_by,
                        u.name AS assigned_name,
                        u.avatar AS assigned_avatar,
                        t.title,
                        t.project_id,
                        t.status_id,
                        t.priority,
                        t.due_date,
                        ts.name AS status_name,
                        ts.slug AS status_slug,
                        ts.color AS status_color,
                        ts.is_done AS status_is_done
                    FROM tasks t
                    LEFT JOIN task_assignments ta
                        ON ta.task_id = t.id
                        AND ta.assigned_at = (
                            SELECT MAX(ta_latest.assigned_at)
                            FROM task_assignments ta_latest
                            WHERE ta_latest.task_id = t.id
                        )
                    LEFT JOIN users u ON ta.user_id = u.id
                    LEFT JOIN task_statuses ts ON t.status_id = ts.id
                    WHERE t.project_id = :project_id
                      AND t.deleted_at IS NULL
                    ORDER BY t.id DESC";

        return $this->db->query($sql, ['project_id' => (int) $projectId])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tạo dự án mới và tự động sinh mã dự án (Project Code)
     * Sử dụng Transaction để đảm bảo tính toàn vẹn dữ liệu
     * 
     * @param array $data Dữ liệu dự án
     * @return int ID của dự án vừa tạo
     */
    public function createWithProjectCode($data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Chèn thông tin dự án cơ bản
            $sql = "INSERT INTO {$this->table} (name, description, status_id, owner_id, start_date, due_date) 
                    VALUES (:name, :description, :status_id, :owner_id, :start_date, :due_date)";

            $this->db->query($sql, [
                'name'        => $data['name'],
                'description' => $data['description'],
                'status_id'      => $data['status_id'],
                'owner_id'    => $data['owner_id'],
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date']
            ]);

            $projectId = $this->db->lastInsertId();
            $projectCode = 'PRJ' . str_pad($projectId, 5, '0', STR_PAD_LEFT);

            // 2. Cập nhật mã dự án dựa trên ID vừa tạo
            $this->db->query(
                "UPDATE {$this->table} SET project_code = :project_code WHERE id = :id",
                [
                    'project_code' => $projectCode,
                    'id'           => $projectId,
                ]
            );

            $this->db->commit();
            return (int) $projectId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Lỗi khi tạo dự án: " . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật thông tin dự án
     * 
     * @param int $id ID dự án cần cập nhật
     * @param array $data Dữ liệu mới
     * @return PDOStatement
     */
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                name = :name, 
                description = :description, 
                status_id = :status_id, 
                owner_id = :owner_id,
                start_date = :start_date, 
                due_date = :due_date,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $params = [
            'name'        => $data['name'],
            'description' => $data['description'],
            'status_id'   => $data['status_id'],
            'owner_id'    => $data['owner_id'],
            'start_date'  => $data['start_date'],
            'due_date'    => $data['due_date'],
            'id'          => (int) $id
        ];
        return $this->db->query($sql, $params);
    }

    /**
     * Thêm thành viên vào dự án
     */
    public function addMember($projectId, $userId, $role)
    {
        $role = in_array((string) $role, ['manager', 'member', 'viewer'], true) ? (string) $role : 'member';

        $sql = "INSERT INTO project_members (project_id, user_id, role, joined_at) 
                VALUES (:project_id, :user_id, :role, CURRENT_TIMESTAMP)";

        return $this->db->query($sql, [
            'project_id' => (int) $projectId,
            'user_id'    => (int) $userId,
            'role'       => $role
        ]);
    }

    /**
     * Kiểm tra xem người dùng đã là thành viên dự án chưa
     */
    public function isMemberExists($projectId, $userId)
    {
        $sql = "SELECT COUNT(*) FROM project_members 
                WHERE project_id = :project_id AND user_id = :user_id";

        return (int)$this->db->query($sql, ['project_id' => (int) $projectId, 'user_id' => (int) $userId])->fetchColumn() > 0;
    }

    public function isActiveMember($projectId, $userId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->tableProjectMember}
                WHERE project_id = :project_id
                AND user_id = :user_id
                AND left_at IS NULL";

        return (int)$this->db->query($sql, [
            'project_id' => (int)$projectId,
            'user_id' => (int)$userId
        ])->fetchColumn() > 0;
    }

    /**
     * Thực hiện xóa mềm dự án (Cập nhật trường deleted_at)
     * 
     * @param int $id ID dự án
     * @return bool
     */
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        return (bool)$this->db->query($sql, ['id' => (int) $id]);
    }

    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} p WHERE p.deleted_at IS NULL";
        $params = [];

        $this->applyProjectFilters($sql, $params, $filters);

        return (int)$this->db->query($sql, $params)->fetchColumn();
    }

    public function getProjectsByPageForJoinedUser($userId, $page, $perPage, $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, 
                       u.name AS manager_name, 
                       u.email AS owner_email, 
                       ps.name as status_name, 
                       ps.color as status_color, 
                       ps.slug as status_slug
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                LEFT JOIN project_statuses ps ON p.status_id = ps.id
                WHERE p.deleted_at IS NULL
                AND (
                    p.owner_id = :owner_user_id
                    OR EXISTS (
                        SELECT 1
                        FROM {$this->tableProjectMember} pm
                        WHERE pm.project_id = p.id
                        AND pm.user_id = :member_user_id
                        AND pm.left_at IS NULL
                    )
                )";

        $params = [
            'owner_user_id' => (int)$userId,
            'member_user_id' => (int)$userId,
        ];

        $this->applyProjectFilters($sql, $params, $filters);

        $sql .= " ORDER BY p.id DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = (int)$perPage;
        $params['offset'] = (int)$offset;

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countForJoinedUser($userId, $filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} p
                WHERE p.deleted_at IS NULL
                AND (
                    p.owner_id = :owner_user_id
                    OR EXISTS (
                        SELECT 1
                        FROM {$this->tableProjectMember} pm
                        WHERE pm.project_id = p.id
                        AND pm.user_id = :member_user_id
                        AND pm.left_at IS NULL
                    )
                )";

        $params = [
            'owner_user_id' => (int)$userId,
            'member_user_id' => (int)$userId,
        ];

        $this->applyProjectFilters($sql, $params, $filters);

        return (int)$this->db->query($sql, $params)->fetchColumn();
    }
}

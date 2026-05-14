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

        // Lọc theo danh sách trạng thái (Checklist)
        if (!empty($filters['status_id'])) {
            $statusIds = array_map('intval', $filters['status_id']);
            $sql .= " AND p.status_id IN (" . implode(',', $statusIds) . ")";
        }

        // Lọc theo khoảng thời gian
        if (!empty($filters['start_date'])) {
            $sql .= " AND p.start_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND p.due_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

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

        return $this->db->query($sql, ['id' => $id])->fetch(PDO::FETCH_ASSOC);
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

        return $this->db->query($sql, ['project_id' => $projectId])->fetchAll(PDO::FETCH_ASSOC);
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
                        ta.task_id,
                        ta.user_id,
                        ta.assigned_at,
                        ta.assigned_by,
                        u.name AS assigned_name,
                        u.avatar AS assigned_avatar,
                        t.title,
                        t.project_id,
                        t.status_id,
                        t.priority
                    FROM task_assignments ta
                    INNER JOIN tasks t 
                        ON ta.task_id = t.id
                    LEFT JOIN users u 
                        ON ta.user_id = u.id
                    WHERE t.project_id = :project_id
                    ORDER BY ta.assigned_at DESC";

        return $this->db->query($sql, ['project_id' => $projectId])->fetchAll(PDO::FETCH_ASSOC);
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
            return $projectId;
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
            'id'          => $id
        ];
        return $this->db->query($sql, $params);
    }

    /**
     * Thêm thành viên vào dự án
     */
    public function addMember($projectId, $userId, $role)
    {

        $sql = "INSERT INTO project_members (project_id, user_id, role, joined_at) 
                VALUES (:project_id, :user_id, :role, CURRENT_TIMESTAMP)";

        return $this->db->query($sql, [
            'project_id' => $projectId,
            'user_id'    => $userId,
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

        return (int)$this->db->query($sql, ['project_id' => $projectId, 'user_id' => $userId])->fetchColumn() > 0;
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
        return (bool)$this->db->query($sql, ['id' => $id]);
    }

    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} p WHERE p.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status_id'])) {
            $statusIds = array_map('intval', $filters['status_id']);
            $sql .= " AND p.status_id IN (" . implode(',', $statusIds) . ")";
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND p.start_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND p.due_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }

        return (int)$this->db->query($sql, $params)->fetchColumn();
    }
}

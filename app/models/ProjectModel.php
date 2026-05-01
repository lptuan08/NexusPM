<?php
namespace App\models;

use App\core\Model;
use PDO;
use Exception;

/**
 * Model ProjectModel - Xử lý các tương tác cơ sở dữ liệu liên quan đến dự án
 */
class ProjectModel extends Model
{
    // Tên bảng tương ứng trong cơ sở dữ liệu
    protected $table = 'projects';

    /**
     * Lấy danh sách dự án có phân trang
     * 
     * @param int $page Trang hiện tại
     * @param int $perPage Số bản ghi trên mỗi trang
     * @return array Danh sách dự án kèm thông tin người sở hữu
     */
    public function getProjectsByPage($page, $perPage)
    {
        // Tính toán vị trí bắt đầu lấy dữ liệu
        $offset = ($page - 1) * $perPage;
        $perPage = (int)$perPage;
        $offset = (int)$offset;

        $sql = "SELECT p.*, u.name AS owner_name, u.email AS owner_email
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                WHERE p.deleted_at IS NULL 
                ORDER BY p.id DESC 
                LIMIT {$perPage} OFFSET {$offset}";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy toàn bộ dự án có lọc theo tìm kiếm và trạng thái
     */
    public function getAllProjectsWithFilters()
    {
        $sql = "SELECT p.*, u.name AS owner_name, u.email AS owner_email,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as task_count,
                (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) as member_count,
                (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'done') as completed_task_count
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                WHERE p.deleted_at IS NULL";

        $sql .= " ORDER BY p.id DESC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tìm kiếm một dự án theo ID
     * 
     * @param int $id ID của dự án
     * @return array|bool Thông tin dự án hoặc false nếu không tìm thấy
     */
    public function find($id)
    {
        $sql = "SELECT p.*, u.name AS owner_name, u.email AS owner_email, u.avatar AS owner_avatar
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
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
        $sql = "SELECT u.id, u.name, u.avatar, u.email, pm.role, pm.joined_at 
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
        $sql = "SELECT t.*, u.name as assigned_name, u.avatar as assigned_avatar
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = :project_id
                ORDER BY t.created_at DESC";

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
            $sql = "INSERT INTO {$this->table} (name, description, status, owner_id, start_date, due_date) 
                    VALUES (:name, :description, :status, :owner_id, :start_date, :due_date)";

            $this->db->query($sql, [
                'name'        => $data['name'],
                'description' => $data['description'],
                'status'      => $data['status'],
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
                status = :status, 
                owner_id = :owner_id,
                start_date = :start_date, 
                due_date = :due_date,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $data['id'] = $id;
        return $this->db->query($sql, $data);
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
}

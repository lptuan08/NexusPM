<?php
namespace App\models;

use App\core\Model;
use PDO;
use Exception;

class UserModel extends Model
{
    protected $table = 'users';
    
    // =========================================================================
    // 1. NHÓM CRUD NHÂN VIÊN (DANH SÁCH, CHI TIẾT, THÊM, SỬA, XÓA)
    // =========================================================================

    /**
     * Lấy danh sách toàn bộ nhân viên kèm theo tên chức danh
     */

    public function getAllUsers()
    {
        $sql = "SELECT u.id, u.employee_code, u.name, u.email, u.avatar, u.role_id, u.is_active,
                       jt.name AS job_title, r.name AS role_name, r.slug AS role_slug
                FROM {$this->table} AS u
                LEFT JOIN job_titles AS jt ON jt.id = u.job_title_id
                LEFT JOIN roles AS r ON r.id = u.role_id
                WHERE u.deleted_at IS NULL
                ORDER BY u.id DESC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} u 
                LEFT JOIN job_titles jt ON u.job_title_id = jt.id
                WHERE u.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.employee_code LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['job_title'])) {
            $placeholders = [];
            foreach ($filters['job_title'] as $i => $title) {
                $key = "jt_" . $i;
                $placeholders[] = ":" . $key;
                $params[$key] = $title;
            }
            $sql .= " AND jt.name IN (" . implode(',', $placeholders) . ")";
        }
        if (!empty($filters['role_id'])) {
            $placeholders = [];
            foreach ($filters['role_id'] as $i => $role) {
                $key = "role_id_" . $i;
                $placeholders[] = ":" . $key;
                $params[$key] = $role;
            }
            $sql .= " AND u.role_id IN (" . implode(',', $placeholders) . ")";
        }
        if (!empty($filters['created_at_start'])) {
            $sql .= " AND u.created_at >= :start";
            $params['start'] = $filters['created_at_start'] . ' 00:00:00';
        }
        if (!empty($filters['created_at_end'])) {
            $sql .= " AND u.created_at <= :end";
            $params['end'] = $filters['created_at_end'] . ' 23:59:59';
        }

        return (int)$this->db->query($sql, $params)->fetchColumn();
    }

    /**
     * Lấy thông tin chi tiết một nhân viên theo ID
     */
    public function getUserById($id)
    {
        $sql = "SELECT u.*, jt.name AS job_title, r.name AS role_name, r.slug AS role_slug
                FROM {$this->table} AS u
                LEFT JOIN job_titles AS jt ON u.job_title_id = jt.id
                LEFT JOIN roles AS r ON r.id = u.role_id
                WHERE u.id = :id AND u.deleted_at IS NULL";

        return $this->db->query($sql, ['id' => $id])->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm nhân viên và tự động tạo mã nhân viên trong một Transaction
     */
    public function createWithEmployeeCode($data)
    {
        try {
            // Bắt đầu giao dịch
            $this->db->beginTransaction();

            // 1. Thêm nhân viên mới
            $this->insert($data);

            // 2. Lấy ID vừa tạo từ dòng vừa insert
            $userId = $this->db->lastInsertId();

            // 3. Tạo mã nhân viên dựa trên ID (Ví dụ: MNV00001)
            $employeeCode = 'MNV' . str_pad($userId, 5, '0', STR_PAD_LEFT);
            // 4. Cập nhật mã nhân viên vào chính bản ghi vừa tạo
            $this->update($userId, ['employee_code' => $employeeCode]);

            // Nếu mọi thứ ổn, xác nhận lưu vĩnh viễn các thay đổi
            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            // Nếu có bất kỳ lỗi nào xảy ra, hủy bỏ toàn bộ các thay đổi trong transaction này
            $this->db->rollBack();
            // Ném lỗi tiếp ra ngoài để Controller hoặc ErrorHandler xử lý hiển thị            
            throw new Exception("Lỗi khi tạo nhân viên: " . $e->getMessage(), 500);
        }
    }

    public function getLastUser()
    {
        return $this->db->lastInsertId();
    }

    /**
     * Kiểm tra Email đã tồn tại trong hệ thống chưa
     * @param string $email
     * @param int|null $excludeId ID cần loại trừ (dùng khi cập nhật)
     * @return bool
     */
    public function isEmailExists($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email AND deleted_at IS NULL";
        $params = ['email' => $email];
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        return (int)$this->db->query($sql, $params)->fetchColumn() > 0;
    }

    // =========================================================================
    // 2. NHÓM DỮ LIỆU DANH MỤC (HỖ TRỢ FORM)
    // =========================================================================

    /**
     * Lấy danh sách chức danh cho thẻ Select
     */
    public function getJobTitle()
    {
        $sql = "SELECT id, name FROM job_titles ORDER BY name ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProjectOwnerOptions()
    {
        $sql = "SELECT id, name, email
                FROM {$this->table}
                WHERE deleted_at IS NULL
                ORDER BY name ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // 3. NHÓM QUAN HỆ VÀ HIỆU SUẤT (DỰ ÁN, CÔNG VIỆC)
    // =========================================================================

    /**
     * Lấy danh sách các dự án mà một nhân viên cụ thể đang tham gia
     */
    public function getUserProjects($userId)
    {
        $sql = "SELECT
                p.id, 
                p.name,
                p.description,
                p.status_id,
                ps.slug AS status,
                p.start_date,
                p.due_date, 
                pm.role, 
                pm.joined_at
                FROM project_members pm
                JOIN projects p ON pm.project_id = p.id
                LEFT JOIN project_statuses ps ON p.status_id = ps.id
                WHERE pm.user_id = :user_id";
        return $this->db->query($sql, ['user_id' => $userId])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách công việc mà nhân viên đó được giao (Assigned To)
     */
    public function getUserTasks($userId)
    {
        $sql = "SELECT 
                t.title, 
                t.due_date, 
                t.priority, 
                t.status_id,
                ts.slug AS status,
                p.name as project_name
                FROM tasks t
                JOIN projects p ON t.project_id = p.id
                LEFT JOIN task_statuses ts ON t.status_id = ts.id
                WHERE t.assigned_to = :user_id
                ORDER BY t.due_date ASC";
        return $this->db->query($sql, ['user_id' => $userId])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserByPage($page, $perPage, $filters = [])
    {
        // công thức tính phân trang
        $offset = ($page - 1) * $perPage;

        // câu lệnh sql
        $sql = "SELECT u.*, jt.name AS job_title, r.name AS role_name, r.slug AS role_slug
                FROM {$this->table} AS u
                LEFT JOIN job_titles AS jt ON u.job_title_id = jt.id
                LEFT JOIN roles AS r ON r.id = u.role_id
                WHERE u.deleted_at IS NULL";
        
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.employee_code LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['job_title'])) {
            $placeholders = [];
            foreach ($filters['job_title'] as $i => $title) {
                $key = "jt_" . $i;
                $placeholders[] = ":" . $key;
                $params[$key] = $title;
            }
            $sql .= " AND jt.name IN (" . implode(',', $placeholders) . ")";
        }
        if (!empty($filters['role_id'])) {
            $placeholders = [];
            foreach ($filters['role_id'] as $i => $roleId) {
                $key = "role_id_" . $i;
                $placeholders[] = ":" . $key;
                $params[$key] = $roleId;
            }
            $sql .= " AND u.role_id IN (" . implode(',', $placeholders) . ")";
        }
        if (!empty($filters['created_at_start'])) {
            $sql .= " AND u.created_at >= :start";
            $params['start'] = $filters['created_at_start'] . ' 00:00:00';
        }
        if (!empty($filters['created_at_end'])) {
            $sql .= " AND u.created_at <= :end";
            $params['end'] = $filters['created_at_end'] . ' 23:59:59';
        }

        $sql .= " ORDER BY u.id DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = (int)$perPage;
        $params['offset'] = (int)$offset;

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUniqueJobTitles() {
        return $this->db->query("SELECT DISTINCT name FROM job_titles ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    }
}

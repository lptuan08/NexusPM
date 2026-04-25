<?php
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
        $sql = "SELECT u.id, u.employee_code, u.name, u.email, u.avatar, u.role, 
                       jt.name AS job_title 
                FROM {$this->table} AS u
                JOIN job_titles AS jt ON jt.id = u.job_title_id
                WHERE u.deleted_at IS NULL
                ORDER BY u.id DESC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thông tin chi tiết một nhân viên theo ID
     */
    public function getUserById($id)
    {
        $sql = "SELECT u.*, jt.name AS job_title 
                FROM {$this->table} AS u
                LEFT JOIN job_titles AS jt ON u.job_title_id = jt.id
                WHERE u.id = :id AND u.deleted_at IS NULL";

        return $this->db->query($sql, ['id' => $id])->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Thêm mới nhân viên
     */
    public function addUser($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Thêm nhân viên và tự động tạo mã nhân viên trong một Transaction
     */
    public function createWithEmployeeCode($data)
    {
        try {
            // Bắt đầu giao dịch
            $this->db->beginTransaction();

            // 1. Thêm nhân viên mới (employee_code lúc này chưa có)
            $this->db->insert($this->table, $data);

            // 2. Lấy ID vừa tạo từ dòng vừa insert
            $userId = $this->db->lastInsertId();

            // 3. Tạo mã nhân viên dựa trên ID (Ví dụ: MNV00001)
            $employeeCode = 'MNV' . str_pad($userId, 5, '0', STR_PAD_LEFT);
            // 4. Cập nhật mã nhân viên vào chính bản ghi vừa tạo
            $this->updateUser($userId, ['employee_code' => $employeeCode]);

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
     * Cập nhật thông tin nhân viên
     */
    public function updateUser($id, $data)
    {
        // Sử dụng prepared statement cho điều kiện WHERE để tránh SQL Injection
        $condition = "id = :id";
        return $this->db->update($this->table, $data, $condition, ['id' => $id]);
    }

    /**
     * Xóa nhân viên
     */
    public function deleteUser($id)
    {
        // Sử dụng prepared statement cho điều kiện WHERE để tránh SQL Injection
        $condition = "id = :id";
        return $this->db->delete($this->table, $condition, ['id' => $id]);
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
                p.status,
                p.start_date,
                p.due_date, 
                pm.role, 
                pm.joined_at
                FROM project_members pm
                JOIN projects p ON pm.project_id = p.id
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
                t.status, 
                p.name as project_name
                FROM tasks t
                JOIN projects p ON t.project_id = p.id
                WHERE t.assigned_to = :user_id
                ORDER BY t.due_date ASC";
        return $this->db->query($sql, ['user_id' => $userId])->fetchAll(PDO::FETCH_ASSOC);
    }

    public function softDeleteUser($id)
    {
        $sql = "UPDATE users SET deleted_at = NOW() WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }


    public function getUserByPage($page, $perPage)
    {

        // công thức tính phân trang
        $offset = ($page - 1) * $perPage;
        // mặc định câu truy vấn sẽ được bind sang kiểu string sql truyền bằng tham số
        // chuyển sang kiểu int, truyền thẳng vẫn an toàn
        $perPage = (int)$perPage;
        $offset = (int)$offset;
        // câu lệnh sql
        $sql = "SELECT * FROM users WHERE deleted_at IS NULL LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $stmt;
    }
    public function getTotalUser()
    {
        $sql = "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL";
        $result = $this->db->query($sql);
        return (int)$result->fetchColumn();
    }
}

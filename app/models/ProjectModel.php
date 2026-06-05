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
     *
     * =============================================================
     * NHOM BO LOC DU AN
     * =============================================================
     *
     * @param string $sql Cau SQL duoc noi them dieu kien loc.
     * @param array<string, mixed> $params Danh sach tham so bind.
     * @param array<string, mixed> $filters Bo loc du an.
     * @param string $alias Alias bang projects trong cau SQL.
     * @return void
     */
    private function applyProjectFilters(string &$sql, array &$params, array $filters, string $alias = 'p'): void
    {
        if (!empty($filters['search'])) {
            $sql .= " AND ({$alias}.name LIKE :project_search OR {$alias}.project_code LIKE :project_search)";
            $params['project_search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (!empty($filters['owner_id'])) {
            $sql .= " AND {$alias}.owner_id = :owner_id";
            $params['owner_id'] = (int) $filters['owner_id'];
        }

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
     *
     * =============================================================
     * NHOM TRUY VAN DANH SACH DU AN
     * =============================================================
     *
     * @param int $page Trang hien tai.
     * @param int $perPage So ban ghi tren moi trang.
     * @param array<string, mixed> $filters Bo loc du an.
     * @return array<int, array<string, mixed>> Danh sach du an theo trang.
     */
    public function getProjectsByPage($page, $perPage, $filters = [])
    {
        // Tính toán vị trí bắt đầu lấy dữ liệu
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, 
                       u.name AS owner_name,
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
     * Lấy danh sách dự án rút gọn cho select/filter.
     *
     * @return array<int, array<string, mixed>> Danh sach du an rut gon cho select/filter.
     */
    public function getAllProjects()
    {
        $sql = "SELECT id, name, project_code FROM {$this->table} WHERE deleted_at IS NULL ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy toàn bộ dự án kèm thông tin owner, trạng thái và số liệu tổng hợp.
     *
     * @param array<string, mixed> $filters Bo loc tim kiem va trang thai.
     * @return array<int, array<string, mixed>> Danh sach du an kem thong tin tong hop.
     */
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
     *
     * =============================================================
     * NHOM TRUY VAN CHI TIET DU AN
     * =============================================================
     *
     * @param int|string $id ID du an can tim.
     * @return array<string, mixed>|false Thong tin du an, hoac false neu khong tim thay.
     */
    public function find($id)
    {
        $sql = "SELECT p.*,
                       u.name AS owner_name,
                       u.email AS owner_email,
                       u.avatar AS owner_avatar,
                       created_user.name AS created_by_name,
                       created_user.email AS created_by_email,
                       updated_user.name AS updated_by_name,
                       updated_user.email AS updated_by_email,
                       ps.name as status_name,
                       ps.color as status_color,
                       ps.slug as status_slug
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                LEFT JOIN users created_user ON p.created_by = created_user.id
                LEFT JOIN users updated_user ON p.updated_by = updated_user.id
                LEFT JOIN project_statuses ps ON p.status_id = ps.id
                WHERE p.id = :id AND p.deleted_at IS NULL";

        return $this->db->query($sql, ['id' => (int) $id])->fetch(PDO::FETCH_ASSOC);
    }





    /**
     * Lấy danh sách thành viên của một dự án
     * 
     * @param int $projectId ID dự án
     * @return array Danh sách thành viên và vai trò của họ
     *
     * =============================================================
     * NHOM THANH VIEN DU AN
     * =============================================================
     *
     * @param int|string $projectId ID du an can lay thanh vien.
     * @return array<int, array<string, mixed>> Danh sach thanh vien du an.
     */
    public function getProjectMembers($projectId)
    {
        $sql = "SELECT u.id, u.employee_code, u.name, u.avatar, u.email, jt.name AS job_title, pm.role, pm.joined_at, pm.is_active, pm.left_at
                FROM project_members pm
                JOIN users u ON pm.user_id = u.id
                LEFT JOIN job_titles jt ON jt.id = u.job_title_id
                WHERE pm.project_id = :project_id AND u.deleted_at IS NULL";

        return $this->db->query($sql, ['project_id' => (int) $projectId])->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thông tin một thành viên trong dự án.
     *
     * @param int|string $projectId ID dự án.
     * @param int|string $userId ID nhân viên.
     * @return array<string, mixed>|false Thông tin thành viên hoặc false nếu không tồn tại.
     */
    public function getProjectMember($projectId, $userId)
    {
        $sql = "SELECT pm.project_id,
                       pm.user_id,
                       pm.role,
                       pm.joined_at,
                       pm.is_active,
                       pm.left_at,
                       u.id,
                       u.employee_code,
                       u.name,
                       u.email,
                       u.avatar,
                       jt.name AS job_title,
                       r.name AS role_name,
                       r.slug AS role_slug
                FROM {$this->tableProjectMember} pm
                JOIN users u ON pm.user_id = u.id
                LEFT JOIN job_titles jt ON jt.id = u.job_title_id
                LEFT JOIN roles r ON r.id = u.role_id
                WHERE pm.project_id = :project_id
                  AND pm.user_id = :user_id
                  AND u.deleted_at IS NULL
                LIMIT 1";

        return $this->db->query($sql, [
            'project_id' => (int) $projectId,
            'user_id' => (int) $userId,
        ])->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách công việc thuộc dự án
     * 
     * @param int $projectId ID dự án
     * @return array Danh sách các công việc được sắp xếp theo thời gian tạo mới nhất
     *
     * =============================================================
     * NHOM CONG VIEC THUOC DU AN
     * =============================================================
     *
     * @param int|string $projectId ID du an can lay cong viec.
     * @return array<int, array<string, mixed>> Danh sach cong viec cua du an.
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
                        AND ta.deleted_at IS NULL
                        AND ta.assigned_at = (
                            SELECT MAX(ta_latest.assigned_at)
                            FROM task_assignments ta_latest
                            WHERE ta_latest.task_id = t.id
                              AND ta_latest.deleted_at IS NULL
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
     *
     * =============================================================
     * NHOM GHI DU LIEU DU AN
     * =============================================================
     *
     * @param array<string, mixed> $data Du lieu du an can tao.
     * @return int ID du an vua tao.
     */
    public function createWithProjectCode($data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Chèn thông tin dự án cơ bản
            $sql = "INSERT INTO {$this->table} (name, description, status_id, owner_id, start_date, due_date, created_by, updated_by)
                    VALUES (:name, :description, :status_id, :owner_id, :start_date, :due_date, :created_by, :updated_by)";

            $this->db->query($sql, [
                'name'        => $data['name'],
                'description' => $data['description'],
                'status_id'      => $data['status_id'],
                'owner_id'    => $data['owner_id'],
                'start_date' => $data['start_date'],
                'due_date' => $data['due_date'],
                'created_by' => !empty($data['created_by']) ? (int) $data['created_by'] : null,
                'updated_by' => !empty($data['updated_by']) ? (int) $data['updated_by'] : (!empty($data['created_by']) ? (int) $data['created_by'] : null),
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
     *
     * @param int|string $id ID du an can cap nhat.
     * @param array<string, mixed> $data Du lieu moi cua du an.
     * @return mixed Ket qua truy van update tu database layer.
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
                updated_by = :updated_by,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $params = [
            'name'        => $data['name'],
            'description' => $data['description'],
            'status_id'   => $data['status_id'],
            'owner_id'    => $data['owner_id'],
            'start_date'  => $data['start_date'],
            'due_date'    => $data['due_date'],
            'updated_by'  => !empty($data['updated_by']) ? (int) $data['updated_by'] : null,
            'id'          => (int) $id
        ];
        return $this->db->query($sql, $params);
    }

    /**
     * Thêm thành viên vào dự án
     *
     * @param int|string $projectId ID du an can them thanh vien.
     * @param int|string $userId ID nguoi dung duoc them.
     * @param string $role Vai tro thanh vien trong du an.
     * @return mixed Ket qua truy van insert tu database layer.
     */
    public function addMember($projectId, $userId, $role, ?string $joinedAt = null, int $isActive = 1, ?string $leftAt = null)
    {
        $role = strtolower(trim((string) $role));
        $role = in_array($role, ['manager', 'member', 'viewer'], true) ? $role : 'member';

        $sql = "INSERT INTO {$this->tableProjectMember} (project_id, user_id, role, joined_at, is_active, left_at)
                VALUES (:project_id, :user_id, :role, :joined_at, :is_active, :left_at)";

        return $this->db->query($sql, [
            'project_id' => (int) $projectId,
            'user_id'    => (int) $userId,
            'role'       => $role,
            'joined_at'  => $joinedAt ?: date('Y-m-d H:i:s'),
            'is_active'  => $isActive === 1 ? 1 : 0,
            'left_at'    => $leftAt,
        ]);
    }

    /**
     * Cập nhật vai trò và trạng thái tham gia của thành viên dự án.
     *
     * @param int|string $projectId ID dự án.
     * @param int|string $userId ID nhân viên.
     * @param array<string, mixed> $data Dữ liệu thành viên đã chuẩn hóa.
     * @return mixed Kết quả truy vấn update từ database layer.
     */
    public function updateProjectMember($projectId, $userId, array $data)
    {
        $role = strtolower(trim((string) ($data['role'] ?? 'member')));
        $role = in_array($role, ['manager', 'member', 'viewer'], true) ? $role : 'member';

        $sql = "UPDATE {$this->tableProjectMember}
                SET role = :role,
                    joined_at = :joined_at,
                    is_active = :is_active,
                    left_at = :left_at
                WHERE project_id = :project_id
                  AND user_id = :user_id";

        return $this->db->query($sql, [
            'role' => $role,
            'joined_at' => $data['joined_at'] ?? date('Y-m-d H:i:s'),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'left_at' => $data['left_at'] ?? null,
            'project_id' => (int) $projectId,
            'user_id' => (int) $userId,
        ]);
    }

    /**
     * Kiểm tra xem người dùng đã là thành viên dự án chưa
     *
     * @param int|string $projectId ID du an can kiem tra.
     * @param int|string $userId ID nguoi dung can kiem tra.
     * @return bool True neu nguoi dung da la thanh vien du an.
     */
    public function isMemberExists($projectId, $userId)
    {
        $sql = "SELECT COUNT(*) FROM project_members 
                WHERE project_id = :project_id AND user_id = :user_id";

        return (int)$this->db->query($sql, ['project_id' => (int) $projectId, 'user_id' => (int) $userId])->fetchColumn() > 0;
    }

    /**
     * Kiểm tra một user có đang là thành viên active của dự án hay không.
     *
     * @param int|string $projectId ID du an can kiem tra.
     * @param int|string $userId ID nguoi dung can kiem tra.
     * @return bool True neu nguoi dung dang la thanh vien active.
     */
    public function isActiveMember($projectId, $userId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->tableProjectMember}
                WHERE project_id = :project_id
                AND user_id = :user_id
                AND is_active = 1
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
     *
     * @param int|string $id ID du an can xoa mem.
     * @return bool True neu xoa mem thanh cong.
     */
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        return (bool)$this->db->query($sql, ['id' => (int) $id]);
    }

    /**
     * Áp dụng phạm vi xem dự án cho các truy vấn Dashboard.
     *
     * =============================================================
     * NHOM DASHBOARD
     * =============================================================
     *
     * @param string $sql SQL đang được xây dựng.
     * @param array<string, mixed> $params Tham số bind.
     * @param array<string, mixed> $filters Bộ lọc quyền xem dự án.
     * @param string $alias Alias bảng projects.
     * @return void
     */
    private function applyDashboardProjectVisibility(string &$sql, array &$params, array $filters, string $alias = 'p'): void
    {
        if (empty($filters['visibility_user_id'])) {
            return;
        }

        $sql .= " AND (
            {$alias}.owner_id = :dashboard_project_owner_id
            OR EXISTS (
                SELECT 1
                FROM {$this->tableProjectMember} pm_dashboard
                WHERE pm_dashboard.project_id = {$alias}.id
                  AND pm_dashboard.user_id = :dashboard_project_member_id
                  AND pm_dashboard.is_active = 1
                  AND pm_dashboard.left_at IS NULL
            )
        )";

        $params['dashboard_project_owner_id'] = (int) $filters['visibility_user_id'];
        $params['dashboard_project_member_id'] = (int) $filters['visibility_user_id'];
    }

    /**
     * Lấy số liệu dự án cho các thẻ thống kê trên Dashboard.
     *
     * Hàm này chỉ phục vụ Dashboard: đếm dự án chưa hoàn thành và dự án cần chú ý
     * theo phạm vi quyền xem dự án của user hiện tại.
     *
     * @param array<string, mixed> $filters Bộ lọc quyền xem dự án.
     * @param \DateTimeInterface $today Ngày hiện tại theo timezone Dashboard.
     * @return array<string, int> Số liệu tổng hợp dự án.
     */
    public function getDashboardProjectSummary(array $filters, \DateTimeInterface $today): array
    {
        $sql = "SELECT
                    COUNT(*) AS total_projects,
                    SUM(CASE
                        WHEN COALESCE(ps.slug, '') <> 'completed'
                        THEN 1 ELSE 0
                    END) AS active_projects,
                    SUM(CASE
                        WHEN COALESCE(ps.slug, '') IN ('at_risk', 'on_hold')
                          OR (
                              p.due_date IS NOT NULL
                              AND p.due_date < :today
                              AND COALESCE(ps.slug, '') <> 'completed'
                          )
                        THEN 1 ELSE 0
                    END) AS attention_projects
                FROM {$this->table} p
                LEFT JOIN project_statuses ps ON p.status_id = ps.id
                WHERE p.deleted_at IS NULL";

        $params = [
            'today' => $today->format('Y-m-d'),
        ];

        $this->applyDashboardProjectVisibility($sql, $params, $filters);

        $row = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total_projects' => (int) ($row['total_projects'] ?? 0),
            'active_projects' => (int) ($row['active_projects'] ?? 0),
            'attention_projects' => (int) ($row['attention_projects'] ?? 0),
        ];
    }

    /**
     * Đếm số dự án đang hoạt động thỏa bộ lọc.
     *
     * =============================================================
     * NHOM DEM VA PHAN TRANG DU AN
     * =============================================================
     *
     * @param array<string, mixed> $filters Bo loc du an.
     * @return int Tong so du an thoa bo loc.
     */
    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} p WHERE p.deleted_at IS NULL";
        $params = [];

        $this->applyProjectFilters($sql, $params, $filters);

        return (int)$this->db->query($sql, $params)->fetchColumn();
    }

    /**
     * Lấy danh sách dự án theo trang dành cho user chỉ được xem dự án tham gia.
     *
     * @param int|string $userId ID user dang xem danh sach du an tham gia.
     * @param int $page Trang hien tai.
     * @param int $perPage So ban ghi tren moi trang.
     * @param array<string, mixed> $filters Bo loc du an.
     * @return array<int, array<string, mixed>> Danh sach du an user duoc tham gia.
     */
    public function getProjectsByPageForJoinedUser($userId, $page, $perPage, $filters = [])
    {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, 
                       u.name AS owner_name,
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
                        AND pm.is_active = 1
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

    /**
     * Đếm số dự án user được phép xem theo phạm vi tham gia.
     *
     * @param int|string $userId ID user can dem du an tham gia.
     * @param array<string, mixed> $filters Bo loc du an.
     * @return int Tong so du an user duoc tham gia.
     */
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
                        AND pm.is_active = 1
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

    /**
     * Lấy danh sách dự án mà user đang tham gia.
     *
     * Đây là wrapper giữ tương thích với tên hàm cũ, logic thực tế dùng
     * getProjectsForUser().
     *
     * @param int|string $userId ID user cần lấy danh sách dự án.
     * @return array<int, array<string, mixed>> Danh sách dự án user được tham gia.
     */
    public function getProjectsJoinedUser($userId)
    {
        return $this->getProjectsForUser((int) $userId);
    }

    /**
     * Lấy danh sách dự án mà user đang tham gia, có thể giới hạn theo role trong project.
     *
     * @param int $userId ID người dùng.
     * @param array<int, string>|null $memberRoles Danh sách role hợp lệ trong project_members.
     * @return array<int, array<string, mixed>>
     */
    public function getProjectsForUser(int $userId, ?array $memberRoles = null): array
    {
        if ($userId <= 0) {
            return [];
        }

        $restrictToMemberRoles = is_array($memberRoles);
        if ($restrictToMemberRoles && $memberRoles === []) {
            return [];
        }

        $params = [
            'member_user_id' => $userId,
        ];
        if (!$restrictToMemberRoles) {
            $params['owner_user_id'] = $userId;
        }
        $roleSql = '';
        $ownerVisibilitySql = !$restrictToMemberRoles ? 'p.owner_id = :owner_user_id OR' : '';

        if ($restrictToMemberRoles) {
            $rolePlaceholders = [];
            foreach (array_values($memberRoles) as $index => $role) {
                $key = "member_role_{$index}";
                $rolePlaceholders[] = ':' . $key;
                $params[$key] = (string) $role;
            }
            $roleSql = ' AND pm.role IN (' . implode(',', $rolePlaceholders) . ')';
        }

        $sql = "SELECT p.id,
                       p.name,
                       p.project_code,
                       p.status_id,
                       ps.name AS status_name,
                       ps.color AS status_color,
                       ps.slug AS status_slug
                FROM {$this->table} p
                LEFT JOIN project_statuses ps ON p.status_id = ps.id
                WHERE p.deleted_at IS NULL
                AND (
                    {$ownerVisibilitySql}
                    EXISTS (
                        SELECT 1
                        FROM {$this->tableProjectMember} pm
                        WHERE pm.project_id = p.id
                        AND pm.user_id = :member_user_id
                        AND pm.is_active = 1
                        AND pm.left_at IS NULL
                        {$roleSql}
                    )
                )
                ORDER BY p.created_at DESC";

        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy vai trò của user trong một dự án.
     *
     * Chỉ trả về role được lưu trong project_members; Project Sponsor không tự động là manager.
     *
     * @param int|string $projectId ID dự án cần kiểm tra.
     * @param int|string $userId ID user cần kiểm tra.
     * @return string|null Vai trò manager/member/viewer hoặc null nếu không tham gia.
     */
    public function getUserProjectRole($projectId, $userId)
    {
        if ((int) $userId <= 0 || (int) $projectId <= 0) {
            return null;
        }

        if (!$this->find($projectId)) {
            return null;
        }

        $sql = "SELECT role
            FROM {$this->tableProjectMember}
            WHERE project_id = :project_id
            AND user_id = :user_id
            AND is_active = 1
            AND left_at IS NULL
            LIMIT 1";

        $role = $this->db->query($sql, [
            'project_id' => (int) $projectId,
            'user_id' => (int) $userId,
        ])->fetchColumn();

        return $role !== false ? (string) $role : null;
    }
}

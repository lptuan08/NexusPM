<?php

namespace App\models;

use App\core\Model;
use Exception;
use PDO;

class TaskStatusModel extends Model
{
    protected $table = 'task_statuses';
    // get list project (is_deleted = NULL)
    /**
     * =============================================================
     * NHOM TRUY VAN DU AN CHO TRANG THAI CONG VIEC
     * =============================================================
     *
     * @return array<int, array<string, mixed>> Danh sach du an chua bi xoa.
     */
    public function listProject()
    {
        $sql = "SELECT id, name, project_code FROM projects WHERE deleted_at is NULL ORDER BY created_at DESC";
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * Lấy danh sách trạng thái lọc theo dự án
     *
     * =============================================================
     * NHOM TRUY VAN TRANG THAI CONG VIEC
     * =============================================================
     *
     * @param int|string|null $projectId ID du an, hoac null de lay trang thai he thong.
     * @return array<int, array<string, mixed>> Danh sach trang thai cong viec.
     */
    public function getStatuses($projectId = null)
    {
        $sql = "SELECT ts.*, p.name as project_name, p.project_code 
                FROM {$this->table} ts 
                LEFT JOIN projects p ON ts.project_id = p.id 
                WHERE ts.deleted_at IS NULL";
        $params = [];

        if ($projectId) {
            $sql .= " AND ts.project_id = :project_id";
            $params['project_id'] = $projectId;
        } else {
            $sql .= " AND ts.project_id IS NULL";
        }

        $sql .= " ORDER BY ts.position ASC";
        return $this->db->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * =============================================================
     * NHOM KIEM TRA TRUNG LAP TRANG THAI
     * =============================================================
     *
     * @param string $slug Slug can kiem tra.
     * @param int|null $project_id ID du an, hoac null voi trang thai he thong.
     * @param int|string|null $excludeId ID trang thai can loai tru khi cap nhat.
     * @return bool True neu slug da ton tai trong pham vi du an.
     */
    public function isSlugExists(string $slug, ?int $project_id, $excludeId = null)
    {
        // dùng SELECT EXITST kiểm tra tồn tại
        $sql = "SELECT EXISTS(
                SELECT 1
                FROM {$this->table}
                WHERE slug = :slug
                AND deleted_at IS NULL";

        $params = ['slug' => $slug];

        if ($project_id === null) {
            $sql .= " AND project_id IS NULL";
        } else {
            $sql .= " AND project_id = :project_id";
            $params['project_id'] = $project_id;
        }

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $sql .= " LIMIT 1)";
        $result = $this->db->query($sql, $params)->fetchColumn();
        return (bool)$result;
    }

    /**
     * =============================================================
     * NHOM GHI DU LIEU TRANG THAI CONG VIEC
     * =============================================================
     *
     * @param array<string, mixed> $taskStatus Du lieu trang thai cong viec can tao.
     * @return void
     */
    public function add(array $taskStatus)
    {
        try {
            $this->db->beginTransaction();

            $position = $this->getNextPosition($taskStatus['project_id']);

            if ($taskStatus['is_default']) {
                $this->checkStatusFlags($taskStatus['project_id'], 'is_default');
            }
            if ($taskStatus['is_done']) {
                $this->checkStatusFlags($taskStatus['project_id'], 'is_done');
            }


            //2. Insert record
            $sql = "INSERT INTO {$this->table} (name, project_id, slug, color, position, is_active, is_default, is_done) 
                    VALUES (:name, :project_id, :slug, :color, :position, :is_active, :is_default, :is_done)";
            $params = [
                'name'      => $taskStatus['name'],
                'project_id' => $taskStatus['project_id'],
                'slug'      => $taskStatus['slug'],
                'color'     => $taskStatus['color'],
                'position'  => $position,
                'is_active' => $taskStatus['is_active'],
                'is_default' => $taskStatus['is_default'],
                'is_done'   => $taskStatus['is_done'],
            ];
            $this->db->query($sql, $params);

            // Nếu mọi thứ ổn, xác nhận lưu vĩnh viễn các thay đổi
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Lỗi khi thêm trạng thái công việc: " . $e->getMessage(), 500);
        }
    }

    /**
     * @param int|string $id ID trang thai can cap nhat.
     * @param array<string, mixed> $taskStatus Du lieu trang thai moi.
     * @return void
     */
    public function update($id, $taskStatus)
    {
        try {
            $this->db->beginTransaction();
            if ($taskStatus['is_default']) {
                $this->checkStatusFlags($taskStatus['project_id'], 'is_default', $taskStatus['id']);
            }
            if ($taskStatus['is_done']) {
                $this->checkStatusFlags($taskStatus['project_id'], 'is_done', $taskStatus['id']);
            }

            $condition = "id = :id";
            $conditionParams = ['id' => $id];
            $this->db->update($this->table, $taskStatus, $condition, $conditionParams);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Lỗi khi chỉnh sửa trạng thái công việc: " . $e->getMessage(), 500);
        }
    }
    /**
     * =============================================================
     * NHOM DAM BAO CO MAC DINH VA HOAN THANH
     * =============================================================
     *
     * @param int|null $project_id ID du an, hoac null voi trang thai he thong.
     * @param string $statusFlags Ten cot flag can dam bao duy nhat.
     * @param int|string|null $excludeId ID trang thai can loai tru khi cap nhat.
     * @return void
     */
    public function checkStatusFlags(?int $project_id, string $statusFlags, $excludeId = null) // $statusFlasg = 'is_default' or 'is_done'
    {

        if ($project_id == null) {
            $condition = "project_id IS NULL";
            $params = [];
        } else {
            $condition = "project_id = :project_id";
            $params['project_id'] = $project_id;
        }

        $sql = "SELECT id FROM {$this->table} WHERE {$condition} AND {$statusFlags} = 1 AND deleted_at IS NULL";
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $id = $this->db->query($sql, $params)->fetchColumn();
        if ($id) {
            $this->delStatusFlags($id, $statusFlags);
        }
    }
    /**
     * @param int $id ID trang thai can tat flag.
     * @param string $statusFlags Ten cot flag can tat.
     * @return mixed Ket qua truy van update tu database layer.
     */
    public function delStatusFlags(int $id, $statusFlags)
    {
        $sql = "UPDATE {$this->table} SET {$statusFlags} = 0 WHERE id = :id";
        $params = ['id' => $id];
        $result = $this->db->query($sql, $params);
        return $result;
    }

    /**
     * @param int|string $id ID trang thai can xoa mem.
     * @return bool True neu xoa mem thanh cong.
     */
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        return (bool)$this->db->query($sql, ['id' => $id]);
    }

    /**
     * Tính toán vị trí tiếp theo cho trạng thái mới
     *
     * =============================================================
     * NHOM SAP XEP THU TU TRANG THAI
     * =============================================================
     *
     * @param int|string|null $projectId ID du an, hoac null voi trang thai he thong.
     * @return int Vi tri tiep theo.
     */
    private function getNextPosition($projectId)
    {
        $sql = "SELECT MAX(position) as max_pos FROM {$this->table} WHERE ";
        $params = [];

        if ($projectId === null) {
            $sql .= "project_id IS NULL";
        } else {
            $sql .= "project_id = :project_id";
            $params['project_id'] = $projectId;
        }

        $sql .= " FOR UPDATE";
        $result = $this->db->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
        $max = $result['max_pos'] ?? 0;

        return (int)$max + 1;
    }

    // /**
    //  * Cập nhật thứ tự vị trí hàng loạt cho các trạng thái công việc
    //  * @param array $order Mảng chứa các mảng con ['id' => status_id, 'position' => new_position]
    //  */
    /**
     * @param array<int, array{id:int|string, position:int}> $order Danh sach id va position moi.
     * @return void
     */
    public function updateOrder(array $order)
    {
        try {
            $this->db->beginTransaction();
            $sql = "UPDATE {$this->table} SET position = :position WHERE id = :id";
            foreach ($order as $item) {
                $this->db->query($sql, [
                    'position' => $item['position'],
                    'id'       => $item['id']
                ]);
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e; // Ném lại lỗi để Controller xử lý
        }
    }


    /**
     * Kiểm tra trạng thái công việc có thuộc đúng dự án (workflow theo project) hay không.
     *
     * =============================================================
     * NHOM KIEM TRA QUAN HE VOI DU AN
     * =============================================================
     *
     * @param int $statusId ID trang thai can kiem tra.
     * @param int $projectId ID du an can doi chieu.
     * @return bool True neu trang thai thuoc dung du an.
     */
    public function belongsToProject(int $statusId, int $projectId): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}
                WHERE id = :id AND project_id = :project_id AND deleted_at IS NULL";
        $count = (int) $this->db->query($sql, [
            'id'          => $statusId,
            'project_id'  => $projectId,
        ])->fetchColumn();

        return $count > 0;
    }

    // TAKS CONTROLLER
    /**
     * @param int|string|null $id ID du an, hoac null de lay trang thai he thong.
     * @return array<int, array<string, mixed>> Danh sach trang thai rut gon cho TaskController.
     */
    public function getList($id = null)
    {
        if ($id == null) {
            $sql = "SELECT id, name, slug, color, is_done FROM {$this->table} WHERE project_id IS NULL AND deleted_at IS NULL ORDER BY position DESC";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sql = "SELECT id, name, slug, color, is_done FROM {$this->table} WHERE project_id = :project_id AND deleted_at IS NULL ORDER BY position ASC";
            return $this->db->query($sql, ['project_id' => $id])->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    /**
     * Sao chép toàn bộ trạng thái công việc mặc định hệ thống (project_id NULL) sang một dự án.
     *
     * =============================================================
     * NHOM NHAN BAN WORKFLOW TRANG THAI
     * =============================================================
     *
     * @param int $projectId ID du an can nhan ban trang thai he thong.
     * @return void
     */
    public function cloneGlobalStatusesToProject(int $projectId): void
    {
        $globals = $this->getStatuses(null);
        foreach ($globals as $row) {
            $this->add([
                'name'       => $row['name'],
                'slug'       => $row['slug'],
                'color'      => $row['color'],
                'project_id' => $projectId,
                'is_active'  => (int) ($row['is_active'] ?? 1),
                'is_default' => (int) ($row['is_default'] ?? 0),
                'is_done'    => (int) ($row['is_done'] ?? 0),
            ]);
        }
    }

    // API
    /**
     * =============================================================
     * NHOM API TRANG THAI MAC DINH
     * =============================================================
     *
     * @return array<int, array<string, mixed>> Danh sach trang thai mac dinh he thong.
     */
    public function getTaskStatusDefault(){
        $sql = "SELECT id, name, slug, color FROM {$this->table} WHERE project_id IS NULL AND deleted_at IS NULL ORDER BY position ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}

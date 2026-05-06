<?php

namespace App\models;

use App\core\Model;
use Exception;
use PDO;

class TaskSettingModel extends Model
{
    protected $table = 'task_statuses';
    // get list project (is_deleted = NULL)
    public function listProject()
    {
        $sql = "SELECT id, name, project_code FROM projects WHERE deleted_at is NULL ORDER BY created_at DESC";
        $data = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    /**
     * Lấy danh sách trạng thái lọc theo dự án
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

    public function isSlugExists($slug, $project_id, $excludeId = null)
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

    public function add($taskStatus)
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
    public function checkStatusFlags($project_id, $statusFlags, $excludeId = null) // $statusFlasg = 'is_default' or 'is_done'
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
    public function delStatusFlags($id, $statusFlags)
    {
        $sql = "UPDATE {$this->table} SET {$statusFlags} = 0 WHERE id = :id";
        $params = ['id' => $id];
        $result = $this->db->query($sql, $params);
        return $result;
    }

    /**
     * Tính toán vị trí tiếp theo cho trạng thái mới
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
    public function updateOrder($order)
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
}

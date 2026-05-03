<?php

namespace App\models;

use App\core\Model;

use PDO;
use Exception;

class SettingModel extends Model
{
    protected $table = 'project_statuses';

    public function getList()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY position ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }


    public function isSlugExits($slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug AND deleted_at IS NULL";
        $params = ['slug' => $slug];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        return (int)$this->db->query($sql, $params)->fetchColumn() > 0;
    }
    public function addProjectStatus($data)
    {
        

        try {
            $this->db->beginTransaction();
            // 1, Thêm nhân viên mới
            $sqlMaxPos = "SELECT MAX(position) as max_pos FROM {$this->table} FOR UPDATE";
            $max = $this->db->query($sqlMaxPos)->fetch()['max_pos'];
            var_dump($max);
            $position = ($max ?? 0) + 1;

            // insert record
            $sql = "INSERT INTO {$this->table} (name, slug, color, position, is_active) 
                    VALUES (:name, :slug, :color, :position, :is_active)";
            $this->db->query($sql, [
                'name'      => $data['name'],
                'slug'      => $data['slug'],
                'color'     => $data['color'],
                'position'  => $position,
                'is_active' => $data['is_active']
            ]);
            // Nếu mọi thứ ổn, xác nhận lưu vĩnh viễn các thay đổi
            $this->db->commit();
            return $max;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Lỗi khi thêm trạng thái dự án: " . $e->getMessage(), 500);
        }
    }

    public function updateProjectStatus($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                name = :name, 
                slug = :slug, 
                color = :color, 
                is_active = :is_active 
                WHERE id = :id";
        return $this->db->query($sql, [
            'name'      => $data['name'],
            'slug'      => $data['slug'],
            'color'     => $data['color'],
            'is_active' => $data['is_active'],
            'id'        => $id
        ]);
    }

    /**
     * Cập nhật thứ tự vị trí hàng loạt
     */
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
            throw $e;
        }
    }
}

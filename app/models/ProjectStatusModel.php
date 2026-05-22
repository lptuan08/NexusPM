<?php

namespace App\models;

use App\core\Model;

use PDO;
use Exception;

class ProjectStatusModel extends Model
{
    protected $table = 'project_statuses';

    /**
     * =============================================================
     * NHOM TRUY VAN TRANG THAI DU AN
     * =============================================================
     *
     * @return array<int, array<string, mixed>> Danh sach trang thai du an chua bi xoa.
     */
    public function getList()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY position ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * =============================================================
     * NHOM KIEM TRA TRUNG LAP
     * =============================================================
     *
     * @param string $slug Slug can kiem tra.
     * @param int|string|null $excludeId ID trang thai can loai tru khi cap nhat.
     * @return bool True neu slug da ton tai.
     */
    public function isSlugExists(string $slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug AND deleted_at IS NULL";
        $params = ['slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        return (int)$this->db->query($sql, $params)->fetchColumn() > 0;
    }
    /**
     * =============================================================
     * NHOM GHI DU LIEU TRANG THAI DU AN
     * =============================================================
     *
     * @param array<string, mixed> $data Du lieu trang thai moi.
     * @return void
     */
    public function addProjectStatus(array $data)
    {


        try {
            $this->db->beginTransaction();
            //1. Kiểm tra lấy giá trị position
            $sqlMaxPos = "SELECT MAX(position) as max_pos FROM {$this->table} FOR UPDATE";
            $max = $this->db->query($sqlMaxPos)->fetch()['max_pos'];
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
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Lỗi khi thêm trạng thái dự án: " . $e->getMessage(), 500);
        }
    }

    /**
     * @param int|string $id ID trang thai can cap nhat.
     * @param array<string, mixed> $data Du lieu trang thai moi.
     * @return mixed Ket qua truy van update tu database layer.
     */
    public function updateProjectStatus(string $id, array $data)
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
     *
     * =============================================================
     * NHOM SAP XEP THU TU TRANG THAI
     * =============================================================
     *
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
            throw $e;
        }
    }

    //getAllStatus
    /**
     * @return array<int, array<string, mixed>> Danh sach trang thai rut gon cho API/form.
     */
    public function getAllStatus()
    {
        $sql = "SELECT id, name, slug, color FROM {$this->table} WHERE deleted_at IS NULL ORDER BY position ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}

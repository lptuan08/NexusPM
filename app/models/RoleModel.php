<?php

namespace App\models;

use App\core\Model;

use PDO;
use Exception;

class RoleModel extends Model
{
    protected $table = 'roles';

    /**
     * =============================================================
     * NHOM TRUY VAN VAI TRO
     * =============================================================
     *
     * @return array<int, array<string, mixed>> Danh sach vai tro chua bi xoa.
     */
    public function getRoles()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY id ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param int|string $id ID vai tro can tim.
     * @return array<string, mixed>|false Thong tin vai tro, hoac false neu khong ton tai.
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL";
        return $this->db->query($sql, ['id' => $id])->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * =============================================================
     * NHOM KIEM TRA TRUNG LAP
     * =============================================================
     *
     * @param string $slug Slug can kiem tra.
     * @param int|string|null $excludeId ID vai tro can loai tru khi cap nhat.
     * @return bool True neu slug da ton tai.
     */
    public function isSlugExists(string $slug, $excludeId = null)
    {
        // dùng SELECT EXISTS kiểm tra tồn tại
        $sql = "SELECT EXISTS(
                SELECT 1
                FROM {$this->table}
                WHERE slug = :slug";

        $params = ['slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $sql .= " LIMIT 1)";
        $result = $this->db->query($sql, $params)->fetchColumn();
        return (bool)$result;
    }

    /**
     * @param string $name Ten vai tro can kiem tra.
     * @param int|string|null $excludeId ID vai tro can loai tru khi cap nhat.
     * @return bool True neu ten vai tro da ton tai.
     */
    public function isNameExists(string $name, $excludeId = null)
    {
        $sql = "SELECT EXISTS(
                SELECT 1
                FROM {$this->table}
                WHERE name = :name
                AND deleted_at IS NULL";
        $params = ['name' => $name];

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
     * NHOM GHI DU LIEU VAI TRO
     * =============================================================
     *
     * @param array<string, mixed> $data Du lieu vai tro moi.
     * @return array<string, mixed> Tham so da dung de tao vai tro.
     */
    public function add(array $data)
    {
        $sql = "INSERT INTO {$this->table} (name, slug, description, is_active) VALUES (:name, :slug, :description, :is_active)";
        $params =  [
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'description' => $data['description'],
            'is_active'   => $data['is_active']
        ];

        $this->db->query($sql, $params);
        return $params;
    }

    /**
     * @param int|string $id ID vai tro can cap nhat.
     * @param array<string, mixed> $data Du lieu moi cua vai tro.
     * @return mixed Ket qua truy van update tu database layer.
     */
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET 
                name = :name, 
                slug = :slug, 
                description = :description, 
                is_active = :is_active,
                is_system = :is_system,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $data['id'] = $id;
        return $this->db->query($sql, $data);
    }

    /**
     * @param int|string $id ID vai tro can xoa mem.
     * @return bool True neu xoa mem thanh cong.
     */
    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        return (bool)$this->db->query($sql, ['id' => $id]);
    }
}

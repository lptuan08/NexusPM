<?php

namespace App\models;

use App\core\Model;

use PDO;
use Exception;

class RoleModel extends Model
{
    protected $table = 'roles';
    public function getRoles()
    {
        $sql = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL ORDER BY id ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL";
        return $this->db->query($sql, ['id' => $id])->fetch(PDO::FETCH_ASSOC);
    }




    public function isSlugExists(string $slug, $excludeId = null)
    {
        // dùng SELECT EXISTS kiểm tra tồn tại
        $sql = "SELECT EXISTS(
                SELECT 1
                FROM {$this->table}
                WHERE slug = :slug
                AND deleted_at IS NULL";

        $params = ['slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $sql .= " LIMIT 1)";
        $result = $this->db->query($sql, $params)->fetchColumn();
        return (bool)$result;
    }
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

    public function add($data)
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

    public function delete($id)
    {
        $sql = "UPDATE {$this->table} SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id";
        return (bool)$this->db->query($sql, ['id' => $id]);
    }
}

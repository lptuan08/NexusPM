<?php
class ProjectModel extends Model
{
    protected $table = 'projects';

    /**
     * Lấy danh sách dự án có phân trang
     */
    public function getProjectsByPage($page, $perPage)
    {
        $offset = ($page - 1) * $perPage;
        $perPage = (int)$perPage;
        $offset = (int)$offset;

        $sql = "SELECT * FROM {$this->table} 
                WHERE deleted_at IS NULL 
                ORDER BY id DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách thành viên của một dự án
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
}
<?php

namespace App\controllers\api;

use App\core\Controller;
use App\core\Response;
use App\models\UserModel;
use App\models\ProjectStatusModel;
use App\models\TaskStatusModel;

/**
 * API dữ liệu cho trang tạo dự án wizard (createWizard).
 */
class ProjectApiController extends Controller
{
    protected UserModel $userModel;
    protected ProjectStatusModel $projectStatus;
    protected TaskStatusModel $taskStatus;

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
    public function __construct()
    {
        parent::__construct();

        $this->userModel = $this->model('UserModel');
        $this->projectStatus = $this->model('ProjectStatusModel');
        $this->taskStatus = $this->model('TaskStatusModel');
    }

    /**
     * Trả về owner/status/mẫu task status/danh sách user/vai trò thành viên dự án (đúng key với createWizard.js).
     *
     * =============================================================
     * NHOM API WIZARD DU AN
     * =============================================================
     */
    public function getWizardData(): void
    {
        $ownerOptions = $this->userModel->getProjectOwnerOptions();
        $statusOptions = $this->projectStatus->getAllStatus();
        $globalRows = $this->taskStatus->getStatuses(null);

        $globalTaskStatuses = [];
        foreach ($globalRows as $row) {
            $globalTaskStatuses[] = [
                'name'       => $row['name'] ?? '',
                'slug'       => $row['slug'] ?? '',
                'color'      => $row['color'] ?? '#64748b',
                'is_default' => (int) ($row['is_default'] ?? 0),
                'is_done'    => (int) ($row['is_done'] ?? 0),
            ];
        }

        $memberUserOptions = [];
        foreach ($this->userModel->getAllUsers() as $u) {
            $memberUserOptions[] = [
                'id'    => (int) ($u['id'] ?? 0),
                'name'  => (string) ($u['name'] ?? ''),
                'email' => (string) ($u['email'] ?? ''),
            ];
        }

        $memberRoles = [
            ['slug' => 'manager', 'name' => 'Quản lý'],
            ['slug' => 'member', 'name' => 'Thành viên'],
            ['slug' => 'viewer', 'name' => 'Chỉ xem'],
        ];

        Response::success([
            'ownerOptions'        => $ownerOptions,
            'statusOptions'       => $statusOptions,
            'globalTaskStatuses'  => $globalTaskStatuses,
            'memberUserOptions'   => $memberUserOptions,
            'memberRoles'         => $memberRoles,
        ]);
    }
}

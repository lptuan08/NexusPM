<?php
/**
 * Cấu hình hiển thị trạng thái và độ ưu tiên
 * Việc định nghĩa ở đây giúp code bên dưới gọn gàng và dễ sửa đổi khi cần thêm trạng thái mới.
 */
$projectStatusMap = [
    1           => ['text' => 'Mới', 'class' => 'status-planning'],
    2           => ['text' => 'Đang thực hiện', 'class' => 'status-active'],
    3           => ['text' => 'Hoàn thành', 'class' => 'status-completed'],
    'planning'  => ['text' => 'Lên kế hoạch', 'class' => 'status-planning'],
    'active'    => ['text' => 'Đang thực hiện', 'class' => 'status-active'],
    'completed' => ['text' => 'Hoàn thành', 'class' => 'status-completed'],
];

$priorityMap = [
    'high'   => 'priority-high',
    'medium' => 'priority-medium',
    'low'    => 'priority-low',
];

$taskStatusMap = [
    'todo'        => ['text' => 'Chưa làm', 'class' => 'status-muted'],
    'in_progress' => ['text' => 'Đang làm', 'class' => 'status-active'],
    'done'        => ['text' => 'Xong', 'class' => 'status-completed'],
];
?>

<!-- BREADCRUMB - ĐỒNG BỘ VỚI LIST.PHP -->
<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/users" class="text-decoration-none text-slate-500 hover-text-primary">Nhân viên</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title"><?= htmlspecialchars($user['name']) ?></span>
    </div>

    <div class="page-actions">
        <a href="<?= URLROOT ?>/users/<?= $user['id'] ?>/edit" class="btn btn-outline-secondary">
            <i data-lucide="edit-3"></i>
            <span>Chỉnh sửa</span>
        </a>
        <div class="dropdown">
            <button class="btn btn-outline-secondary px-2" type="button" data-bs-toggle="dropdown">
                <i data-lucide="more-horizontal" size="18"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li>
                    <a class="dropdown-item text-danger d-flex align-items-center gap-2 py-2" 
                       href="javascript:void(0)" 
               onclick="showDeleteModal('<?= URLROOT ?>/users/<?= (int) $user['id'] ?>/delete', <?= htmlspecialchars(json_encode('Bạn có chắc chắn muốn xóa hồ sơ nhân viên ' . ($user['name'] ?? '') . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                       <i data-lucide="trash-2"></i>
                        Xóa hồ sơ nhân viên
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- MODAL XÁC NHẬN XÓA DÙNG CHUNG -->
<div class="modal fade modal-confirm" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-confirm-dialog">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-body text-center">
                <div class="icon-box">
                    <i data-lucide="alert-triangle" size="32"></i>
                </div>
                <h5 class="fw-bold text-slate-800 mb-2">Xác nhận xóa</h5>
                <p class="text-slate-500 small mb-4" id="deleteConfirmMessage">Hành động này không thể hoàn tác. Bạn có chắc chắn?</p>
                
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Hủy bỏ</button>
                    <form id="deleteForm" method="POST" action="" class="w-100 m-0">
                        <?php App\helpers\SecurityHelper::csrfInput();?> <!-- Thêm CSRF token vào form xóa -->
                        <button type="submit" class="btn btn-danger w-100">Xác nhận xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid p-0">

    <div class="row g-4">
        <!-- Cột bên trái: Thông tin cá nhân -->
        <div class="col-md-4">
            <div class="ui-card mb-4 overflow-hidden">
                <div class="card-body text-center pt-4">
                    <?php
                        $physicalPath = APPROOT . '/public/uploads/avatars/' . ($user['avatar'] ?? '');
                        $avatarUrl = (!empty($user['avatar']) && file_exists($physicalPath)) 
                            ? URLROOT . '/uploads/avatars/' . $user['avatar']
                            : "https://ui-avatars.com/api/?name=" . urlencode($user['name'] ?? 'N/A') . "&background=e8f0fe&color=1a73e8&rounded=true&size=120";
                    ?>
                    <div class="profile-avatar-wrapper mb-3">
                        <img src="<?= $avatarUrl ?>" alt="Avatar" class="avatar-lg shadow-sm border border-4 border-white">
                    </div>
                    
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($user['name']) ?></h5>
                    <p class="text-muted text-xs"><?= htmlspecialchars($user['job_title'] ?? 'Chưa xác định') ?></p>
                </div>
                <div class="info-list">
                    <div class="info-list-item">
                        <span class="info-label">Trạng thái</span>
                        <span class="badge rounded-pill <?php echo $user['is_active'] ? 'account-status-active' : 'account-status-locked'; ?>">
                            <span class="status-dot"></span>
                            <?php echo $user['is_active'] ? 'Đang hoạt động' : 'Tạm khóa'; ?>
                        </span>
                    </div>
                    <div class="info-list-item">
                        <span class="info-label">Mã nhân viên</span>
                        <span class="info-value"><?= htmlspecialchars($user['employee_code']) ?></span>
                    </div>
                    <div class="info-list-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="info-list-item">
                        <span class="info-label">Vai trò</span>
                        <span class="badge-role <?= ($user['role'] === 'admin') ? 'role-director' : 'role-staff' ?>"><?= ($user['role'] === 'admin') ? 'Quản trị viên' : 'Nhân viên' ?></span>
                    </div>
                    <div class="info-list-item">
                        <span class="info-label">Ngày gia nhập</span>
                        <span class="info-value"><?= isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-' ?></span>
                    </div>
                    <div class="info-list-item">
                        <span class="info-label">Cập nhật gần nhất</span>
                        <span class="info-value"><?= isset($user['updated_at']) ? date('d/m/Y', strtotime($user['updated_at'])) : '-' ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột bên phải: Dự án & Công việc -->
        <div class="col-md-8">
            <ul class="nav nav-pills mb-3 bg-white p-2 rounded-4 shadow-sm" id="userTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-4" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects">Dự án tham gia</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks">Công việc được giao</button>
                </li>
            </ul>

            <div class="tab-content" id="userTabContent">
                <div class="tab-pane fade show active" id="projects">
                    <div class="table-container">
                        <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="ps-4">Tên dự án</th>
                                    <th>Vai trò</th>
                                    <th>Trạng thái</th>
                                    <th class="pe-4">Ngày tham gia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($projects)): foreach ($projects as $pj): ?>
                                <tr>
                                    <td class="ps-4 fw-bold"><?= htmlspecialchars($pj['name']) ?></td>
                                    <td><span class="text-muted small"><?= htmlspecialchars($pj['role']) ?></span></td>
                                    <td>
                                        <?php $st = $projectStatusMap[$pj['status']] ?? ['text' => $pj['status'], 'class' => 'status-muted']; ?>
                                        <span class="status-pill <?= $st['class'] ?>"><?= $st['text'] ?></span>
                                    </td>
                                    <td class="pe-4 small"><?= date('d/m/Y', strtotime($pj['joined_at'])) ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="table-empty">Chưa tham gia dự án nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tasks">
                    <div class="table-container">
                        <div class="table-responsive">
                        <table class="table table-custom align-middle">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="ps-4">Công việc</th>
                                    <th>Dự án</th>
                                    <th>Độ ưu tiên</th>
                                    <th>Hạn xử lý</th>
                                    <th class="pe-4">Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tasks)): foreach ($tasks as $task): ?>
                                <tr>
                                    <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($task['title']) ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($task['project_name']) ?></small></td>
                                    <td>
                                        <span class="status-pill <?= $priorityMap[$task['priority']] ?? 'status-muted' ?>">
                                            <?= ucfirst($task['priority']) ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= $task['due_date'] ? date('d/m/Y', strtotime($task['due_date'])) : '-' ?></td>
                                    <td class="pe-4">
                                        <?php $ts = $taskStatusMap[$task['status']] ?? ['text' => $task['status'], 'class' => 'status-muted']; ?>
                                        <span class="status-pill <?= $ts['class'] ?>"><?= $ts['text'] ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="table-empty">Chưa có công việc được giao.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

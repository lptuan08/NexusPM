<?php
/**
 * Cấu hình hiển thị trạng thái và độ ưu tiên
 * Việc định nghĩa ở đây giúp code bên dưới gọn gàng và dễ sửa đổi khi cần thêm trạng thái mới.
 */
$projectStatusMap = [
    1           => ['text' => 'Mới', 'class' => 'bg-info'],
    2           => ['text' => 'Đang thực hiện', 'class' => 'bg-primary'],
    3           => ['text' => 'Hoàn thành', 'class' => 'bg-success'],
    'planning'  => ['text' => 'Lên kế hoạch', 'class' => 'bg-info'],
    'active'    => ['text' => 'Đang thực hiện', 'class' => 'bg-primary'],
    'completed' => ['text' => 'Hoàn thành', 'class' => 'bg-success'],
];

$priorityMap = [
    'high'   => 'bg-danger',
    'medium' => 'bg-warning',
    'low'    => 'bg-secondary',
];

$taskStatusMap = [
    'todo'        => ['text' => 'Chưa làm', 'class' => 'bg-secondary'],
    'in_progress' => ['text' => 'Đang làm', 'class' => 'bg-primary'],
    'done'        => ['text' => 'Xong', 'class' => 'bg-success'],
];
?>

<!-- BREADCRUMB - ĐỒNG BỘ VỚI LIST.PHP -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/users" class="text-decoration-none text-slate-500 hover-text-primary">Nhân viên</a>
        <span class="mx-2 text-slate-400 d-flex align-items-center" style="width: 16px;"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="fw-medium text-slate-800 fs-5"><?= htmlspecialchars($user['name']) ?></span>
    </div>

    <div class="d-flex align-items-center gap-2">
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
                       onclick="showDeleteModal('<?= URLROOT ?>/users/<?= $user['id'] ?>/delete', 'Bạn có chắc chắn muốn xóa hồ sơ nhân viên <?= htmlspecialchars($user['name']) ?>?')">
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
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
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
            <div class="card border-0 shadow-sm rounded-5 mb-4 overflow-hidden">
                <div class="card-body text-center pt-4">
                    <?php
                        $physicalPath = APPROOT . '/public/uploads/avatars/' . ($user['avatar'] ?? '');
                        $avatarUrl = (!empty($user['avatar']) && file_exists($physicalPath)) 
                            ? URLROOT . '/uploads/avatars/' . $user['avatar']
                            : "https://ui-avatars.com/api/?name=" . urlencode($user['name'] ?? 'N/A') . "&background=e8f0fe&color=1a73e8&rounded=true&size=120";
                    ?>
                    <div class="profile-avatar-wrapper mb-3">
                        <img src="<?= $avatarUrl ?>" alt="Avatar" class="profile-avatar shadow-sm border border-4 border-white" style="width: 120px; height: 120px;">
                    </div>
                    
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($user['name']) ?></h5>
                    <p class="text-muted x-small"><?= htmlspecialchars($user['job_title'] ?? 'Chưa xác định') ?></p>
                </div>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted small fw-medium">Trạng thái</span>
                        <span class="badge rounded-pill <?php echo $user['is_active'] ? 'status-active' : 'status-locked'; ?>">
                            <span class="status-dot"></span>
                            <?php echo $user['is_active'] ? 'Đang hoạt động' : 'Tạm khóa'; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted small fw-medium">Mã nhân viên</span>
                        <span class="small text-dark"><?= htmlspecialchars($user['employee_code']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted small fw-medium">Email</span>
                        <span class="small text-dark"><?= htmlspecialchars($user['email']) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted small fw-medium">Vai trò</span>
                        <span class="badge border text-dark bg-light"><?= ($user['role'] === 'admin') ? 'Quản trị viên' : 'Nhân viên' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3">
                        <span class="text-muted small fw-medium">Ngày gia nhập</span>
                        <span class="small text-dark"><?= isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '-' ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-4 py-3 border-bottom-0">
                        <span class="text-muted small fw-medium">Cập nhật gần nhất</span>
                        <span class="small text-dark"><?= isset($user['updated_at']) ? date('d/m/Y', strtotime($user['updated_at'])) : '-' ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Cột bên phải: Dự án & Công việc -->
        <div class="col-md-8">
            <ul class="nav nav-pills mb-3 bg-white p-2 rounded-5 shadow-sm" id="userTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active rounded-pill px-4" id="projects-tab" data-bs-toggle="tab" data-bs-target="#projects">Dự án tham gia</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link rounded-pill px-4" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks">Công việc được giao</button>
                </li>
            </ul>

            <div class="tab-content" id="userTabContent">
                <div class="tab-pane fade show active" id="projects">
                    <div class="table-responsive bg-white shadow-sm rounded-5 overflow-hidden">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
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
                                        <?php $st = $projectStatusMap[$pj['status']] ?? ['text' => $pj['status'], 'class' => 'bg-secondary']; ?>
                                        <span class="badge <?= $st['class'] ?>"><?= $st['text'] ?></span>
                                    </td>
                                    <td class="pe-4 small"><?= date('d/m/Y', strtotime($pj['joined_at'])) ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted small">Chưa tham gia dự án nào.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tasks">
                    <div class="table-responsive bg-white shadow-sm rounded-5 overflow-hidden">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
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
                                        <span class="badge <?= $priorityMap[$task['priority']] ?? 'bg-info' ?>">
                                            <?= ucfirst($task['priority']) ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= $task['due_date'] ? date('d/m/Y', strtotime($task['due_date'])) : '-' ?></td>
                                    <td class="pe-4">
                                        <?php $ts = $taskStatusMap[$task['status']] ?? ['text' => $task['status'], 'class' => 'bg-dark']; ?>
                                        <span class="badge <?= $ts['class'] ?>"><?= $ts['text'] ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted small">Chưa có công việc được giao.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
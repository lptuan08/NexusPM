<?php
/**
 * Giao diện chi tiết công việc
 * 
 * @var array $task Thông tin công việc
 */
$task = $task ?? [];

$priorityBadgeMap = [
    'low'    => 'bg-secondary',
    'medium' => 'bg-info',
    'high'   => 'bg-warning',
    'urgent' => 'bg-danger',
];

$priorityTextMap = [
    'low'    => 'Thấp',
    'medium' => 'Trung bình',
    'high'   => 'Cao',
    'urgent' => 'Khẩn cấp',
];
?>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/tasks" class="text-decoration-none text-slate-500 hover-text-primary">Công việc</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title"><?= htmlspecialchars($task['title'] ?? 'Chi tiết công việc') ?></span>
    </div>

    <div class="page-actions">
        <a href="<?= URLROOT ?>/tasks/<?= $task['id'] ?>/edit" class="btn btn-outline-secondary">
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
                        onclick="showDeleteModal('<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/delete', <?= htmlspecialchars(json_encode('Bạn có chắc chắn muốn xóa công việc ' . ($task['title'] ?? '') . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                        <i data-lucide="trash-2"></i>
                        Xóa
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- MODAL XÁC NHẬN XÓA -->
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
                        <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                        <button type="submit" class="btn btn-danger w-100">Xác nhận xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-body p-4 p-lg-5">
                <div class="mb-4">
                    <div>
                        <h2 class="fw-bold text-slate-900 mb-2"><?= htmlspecialchars($task['title'] ?? '') ?></h2>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="badge rounded-pill <?= $priorityBadgeMap[$task['priority'] ?? 'low'] ?>">
                                <?= $priorityTextMap[$task['priority'] ?? 'low'] ?>
                            </span>
                            <span class="text-slate-500 text-sm">
                                <i data-lucide="folder" size="14" class="me-1"></i>
                                <?= htmlspecialchars($task['project_name'] ?? 'Dự án: ' . ($task['project_id'] ?? 'N/A')) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="task-description text-slate-700 fs-6 lh-lg mb-5">
                    <h6 class="fw-bold text-slate-900 text-uppercase text-xs tracking-wider mb-3">Mô tả công việc</h6>
                    <div class="bg-slate-50 p-4 rounded-4 border border-slate-100 text-wrap text-break" style="white-space: pre-wrap; line-height: 1.6; min-height: 100px;">
                        <?= !empty($task['description']) ? htmlspecialchars($task['description']) : '<span class="text-slate-400 italic">Chưa có mô tả chi tiết cho công việc này.</span>' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-slate-900 text-uppercase text-xs tracking-wider mb-4">Thông tin chi tiết</h6>
                
                <div class="d-flex flex-column gap-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-3 text-slate-600">
                            <i data-lucide="check-circle-2"></i>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500 fw-medium">Trạng thái</div>
                            <div class="fw-bold text-slate-900 fs-6"><?= htmlspecialchars($task['status_name'] ?? 'N/A') ?></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-3 text-slate-600">
                            <i data-lucide="calendar"></i>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500 fw-medium">Hạn chót</div>
                            <div class="fw-bold fs-6 <?= (!empty($task['due_date']) && strtotime($task['due_date']) < time()) ? 'text-danger' : 'text-slate-900' ?>">
                                <?= !empty($task['due_date']) ? date('d/m/Y', strtotime($task['due_date'])) : 'Chưa thiết lập' ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 bg-slate-100 rounded-3 text-slate-600">
                            <i data-lucide="clock"></i>
                        </div>
                        <div>
                            <div class="text-sm text-slate-500 fw-medium">Thời gian dự kiến</div>
                            <div class="fw-bold text-slate-900 fs-6"><?= number_format((float)($task['estimated_hours'] ?? 0), 1) ?> giờ</div>
                        </div>
                    </div>

                    <hr class="my-1 border-slate-100">

                    <div>
                        <div class="text-sm text-slate-500 fw-medium mb-3">Người phụ trách</div>
                        <div class="d-flex align-items-center gap-2">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($task['assigned_name'] ?? 'U') ?>&background=E2E8F0&color=0F172A&rounded=true&size=32" alt="" class="rounded-circle" width="32" height="32">
                            <span class="fw-semibold text-slate-900"><?= htmlspecialchars($task['assigned_name'] ?? 'Chưa giao') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
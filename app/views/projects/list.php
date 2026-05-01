<?php
$projectStatusMap = [
    'planning'  => ['text' => 'Lên kế hoạch', 'color' => '#6366f1', 'bg' => '#eef2ff', 'icon' => 'clipboard-list'],
    'active'    => ['text' => 'Đang triển khai', 'color' => '#0ea5e9', 'bg' => '#f0f9ff', 'icon' => 'activity'],
    'on_hold'   => ['text' => 'Tạm dừng', 'color' => '#f59e0b', 'bg' => '#fffbeb', 'icon' => 'pause-circle'],
    'completed' => ['text' => 'Đã hoàn thành', 'color' => '#10b981', 'bg' => '#ecfdf5', 'icon' => 'check-circle'],
];
?>

<style>
    .project-card {
        border-radius: 1.25rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e2e8f0;
        background: #fff;
        position: relative;
        overflow: hidden;
    }

    .project-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.1), 0 10px 10px -5px rgba(15, 23, 42, 0.04);
        border-color: #cbd5e1;
    }

    .project-title {
        color: #1e293b;
        transition: color 0.2s;
    }

    .project-card:hover .project-title {
        color: #2563eb;
    }

    .lead-box {
        background-color: #f8fafc;
        border-radius: 0.75rem;
        padding: 0.75rem;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .edit-link {
        opacity: 0;
        transform: translateX(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .project-card:hover .edit-link {
        opacity: 1;
        transform: translateX(0);
    }
</style>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center text-slate-600">
        <h4 class="fw-bold text-slate-900 mb-0">Quản lý dự án</h4>
    </div>

    <a href="<?= URLROOT; ?>/projects/create" class="btn btn-primary px-4 shadow-sm rounded-3">
        <i data-lucide="plus" class="me-2" style="width:18px;height:18px;"></i>
        <span>Tạo dự án mới</span>
    </a>
</div>

<div class="row g-4 mb-5">
    <?php if (!empty($projects)): ?>
        <?php foreach ($projects as $project): ?>
            <?php
            $st = $projectStatusMap[$project['status']] ?? ['text' => $project['status'], 'color' => '#94a3b8', 'bg' => '#f1f5f9', 'icon' => 'help-circle'];
            $isExpired = (!empty($project['due_date']) && $project['due_date'] < date('Y-m-d') && $project['status'] !== 'completed');
            $progressPercent = $project['task_count'] > 0 ? (int)round(($project['completed_task_count'] / $project['task_count']) * 100) : 0;
            ?>
            <div class="col-xl-4 col-md-6">
                <div class="card project-card h-100 shadow-sm">
                    <div class="card-body p-4">
                        <!-- Header: Mã dự án & Thao tác nhanh -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-slate-100 text-slate-500 fw-bold px-2 py-1" style="font-size: 0.7rem;">
                                <?= htmlspecialchars($project['project_code'] ?? '-') ?>
                            </span>

                            <a href="<?= URLROOT ?>/projects/<?= $project['id'] ?>/edit"
                                class="edit-link btn btn-sm fw-bold border-0 rounded-pill px-3 py-1 d-flex align-items-center" 
                                style="font-size: 0.75rem; background-color: #eff6ff; color: #2563eb;">
                                <i data-lucide="pencil" class="me-1" style="width: 14px; height: 14px;"></i>
                                <span>Chỉnh sửa</span>
                            </a>
                        </div>

                        <!-- Trạng thái & Tiêu đề -->
                        <div class="mb-3">
                            <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded-pill mb-2" style="background: <?= $st['bg'] ?>; color: <?= $st['color'] ?>; font-size: 0.75rem; font-weight: 700;">
                                <i data-lucide="<?= $st['icon'] ?>" style="width:14px;height:14px;"></i>
                                <?= $st['text'] ?>
                            </div>
                            <a href="<?= URLROOT ?>/projects/<?= $project['id'] ?>" class="project-title d-block text-decoration-none h5 fw-bold mb-2">
                                <?= htmlspecialchars($project['name']) ?>
                            </a>
                            <p class="text-slate-500 small line-clamp-2 mb-0">
                                <?= htmlspecialchars($project['description'] ?? 'Dự án này chưa có mô tả chi tiết.') ?>
                            </p>
                        </div>

                        <!-- Thanh tiến độ -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-slate-400" style="font-size: 0.75rem; font-weight: 600;">TIẾN ĐỘ</span>
                                <span class="text-slate-900 fw-bold" style="font-size: 0.75rem;"><?= $progressPercent ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px; border-radius: 3px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progressPercent ?>%; border-radius: 3px;"></div>
                            </div>
                        </div>

                        <!-- Thông số dự án -->
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="d-flex align-items-center gap-2 text-slate-600" title="Thành viên">
                                <i data-lucide="users" style="width:16px;height:16px;" class="text-slate-400"></i>
                                <span class="small fw-bold"><?= $project['member_count'] ?? 0 ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-slate-600" title="Công việc">
                                <i data-lucide="list-checks" style="width:16px;height:16px;" class="text-slate-400"></i>
                                <span class="small fw-bold"><?= $project['task_count'] ?? 0 ?></span>
                            </div>
                            <div class="ms-auto d-flex align-items-center gap-2 text-slate-500" title="Thời gian: Bắt đầu - Kết thúc">
                                <i data-lucide="calendar" style="width:16px;height:16px;"></i>
                                <div class="small d-flex align-items-center gap-1">
                                    <span><?= $project['start_date'] ? date('d/m', strtotime($project['start_date'])) : '??' ?></span>
                                    <span class="text-slate-300">→</span>
                                    <span class="<?= $isExpired ? 'text-danger fw-bold' : '' ?>">
                                        <?= $project['due_date'] ? date('d/m', strtotime($project['due_date'])) : '??' ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Trưởng dự án (Lead) -->
                        <div class="lead-box d-flex align-items-center gap-3">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($project['owner_name'] ?? 'U') ?>&background=random&color=fff&rounded=true&size=32"
                                alt="Avatar" class="rounded-circle" style="width: 32px; height: 32px;">
                            <div class="overflow-hidden">
                                <div class="text-slate-400" style="font-size: 0.65rem; font-weight: 700; text-uppercase;">Trưởng dự án</div>
                                <div class="text-slate-900 fw-bold text-truncate small"><?= htmlspecialchars($project['owner_name'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5 text-slate-400">Không có dự án nào được tìm thấy.</div>
    <?php endif; ?>
</div>


<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-body text-center p-4">
                <i data-lucide="alert-triangle" class="text-danger mb-3" size="48"></i>
                <h5 class="fw-bold mb-2">Xác nhận xóa</h5>
                <p class="text-slate-500 small mb-4" id="deleteConfirmMessage"></p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Hủy</button>
                    <form id="deleteForm" method="POST" class="w-100 m-0">
                        <?php App\helpers\SecurityHelper::csrfInput();?>
                        <button type="submit" class="btn btn-danger w-100">Xác nhận</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
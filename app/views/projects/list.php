<?php
$projectStatusMap = [
    'planning'  => ['text' => 'Lên kế hoạch', 'class' => 'status-planning', 'icon' => 'clipboard-list'],
    'active'    => ['text' => 'Đang triển khai', 'class' => 'status-active', 'icon' => 'activity'],
    'on_hold'   => ['text' => 'Tạm dừng', 'class' => 'status-on-hold', 'icon' => 'pause-circle'],
    'completed' => ['text' => 'Đã hoàn thành', 'class' => 'status-completed', 'icon' => 'check-circle'],
];
?>

<style>
    .project-list-name {
        max-width: 360px;
    }

    .project-list-description {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
        max-width: 420px;
    }
</style>

<div class="page-toolbar">
    <div>
        <h1 class="page-title">Quản lý dự án</h1>
        <p class="page-subtitle">Theo dõi trạng thái, tiến độ và nhân sự phụ trách dự án.</p>
    </div>

    <div class="page-actions">
        <button class="btn btn-outline-secondary" title="Lọc dữ liệu">
            <i data-lucide="filter"></i>
            <span class="d-none d-md-inline">Bộ lọc</span>
        </button>
        <a href="<?= URLROOT; ?>/projects/create" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Tạo dự án mới</span>
        </a>
    </div>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="text-center col-stt">STT</th>
                    <th scope="col">Dự án</th>
                    <th scope="col">Mã dự án</th>
                    <th scope="col">Trưởng dự án</th>
                    <th scope="col">Tiến độ</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Thời hạn</th>
                    <th scope="col" class="text-center col-actions"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $index => $project): ?>
                        <?php
                        $status = $projectStatusMap[$project['status']] ?? [
                            'text' => $project['status'] ?? 'Không rõ',
                            'class' => 'status-muted',
                            'icon' => 'help-circle',
                        ];
                        $taskCount = (int) ($project['task_count'] ?? 0);
                        $completedTaskCount = (int) ($project['completed_task_count'] ?? 0);
                        $progressPercent = $taskCount > 0 ? (int) round(($completedTaskCount / $taskCount) * 100) : 0;
                        $ownerName = $project['owner_name'] ?? 'Chưa phân công';
                        $ownerAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($ownerName) . '&background=e8f0fe&color=1a73e8&rounded=true&size=32';
                        $isExpired = (!empty($project['due_date']) && $project['due_date'] < date('Y-m-d') && ($project['status'] ?? '') !== 'completed');
                        $deleteMessage = 'Bạn có chắc chắn muốn xóa dự án ' . ($project['name'] ?? '') . '?';
                        ?>
                        <tr>
                            <td class="text-center text-stt"><?= $index + 1 ?></td>
                            <td>
                                <a href="<?= URLROOT ?>/projects/<?= (int) $project['id'] ?>" class="project-list-name d-block text-decoration-none text-name">
                                    <?= htmlspecialchars($project['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <div class="project-list-description text-meta mt-1">
                                    <?= htmlspecialchars($project['description'] ?? 'Dự án này chưa có mô tả chi tiết.', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>
                            <td>
                                <span class="ui-badge status-muted"><?= htmlspecialchars($project['project_code'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= htmlspecialchars($ownerAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="avatar-sm">
                                    <div class="text-meta text-truncate owner-name-cell"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            </td>
                            <td class="col-progress">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                                    <span class="text-xs fw-bold text-slate-500"><?= $completedTaskCount ?>/<?= $taskCount ?> việc</span>
                                    <span class="text-xs fw-bold text-slate-800"><?= $progressPercent ?>%</span>
                                </div>
                                <div class="progress progress-thin">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progressPercent ?>%;"></div>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill <?= $status['class'] ?>">
                                    <i data-lucide="<?= $status['icon'] ?>"></i>
                                    <?= htmlspecialchars($status['text'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-meta <?= $isExpired ? 'text-danger fw-semibold' : '' ?>">
                                <?php if (!empty($project['start_date']) || !empty($project['due_date'])): ?>
                                    <?= !empty($project['start_date']) ? date('d/m/Y', strtotime($project['start_date'])) : '-' ?>
                                    <span class="text-slate-400">→</span>
                                    <?= !empty($project['due_date']) ? date('d/m/Y', strtotime($project['due_date'])) : '-' ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="dropdown position-static">
                                    <button class="btn btn-link btn-action shadow-none" data-bs-toggle="dropdown" aria-label="Mở hành động">
                                        <i data-lucide="more-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/projects/<?= (int) $project['id'] ?>">
                                                <i data-lucide="eye"></i> Chi tiết
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/projects/<?= (int) $project['id'] ?>/edit">
                                                <i data-lucide="edit-3"></i> Chỉnh sửa
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                               href="javascript:void(0)"
                                               onclick="showDeleteModal('<?= URLROOT ?>/projects/<?= (int) $project['id'] ?>/delete', <?= htmlspecialchars(json_encode($deleteMessage, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i data-lucide="trash-2"></i> Xóa
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="table-empty">Không có dự án nào được tìm thấy.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <span class="table-pagination-info">Hiển thị <?= count($projects ?? []) ?> dự án</span>
    </div>
</div>

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
                        <?php App\helpers\SecurityHelper::csrfInput(); ?>
                        <button type="submit" class="btn btn-danger w-100">Xác nhận xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

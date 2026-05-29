<?php

/**
 * @var array $projects
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalItem
 * @var int $totalPage
 * @var array $statusOptions
 * @var array $currentFilters
 */
$canCreateProject = \App\helpers\AuthHelper::can('projects.create.all');

$listTableConfig = \App\helpers\ListTableHelper::config();
$maxVisiblePages = max(1, (int) ($listTableConfig['max_visible_pages'] ?? 5));
$safeHexColor = static function (?string $color, string $fallback = '#94a3b8'): string {
    $color = trim((string) $color);
    if (!preg_match('/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
        return $fallback;
    }

    if (strlen($color) === 4) {
        return '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
    }

    return $color;
};
$formatDate = static function ($date, string $format = 'd/m/Y'): string {
    $timestamp = !empty($date) ? strtotime((string) $date) : false;

    return $timestamp !== false ? date($format, $timestamp) : '-';
};
?>
<style>
    .project-list-name {
        max-width: 360px;
    }

        /* Tính toán chiều cao dựa trên màn hình (trừ header/toolbar/footer) */
    /* Tùy chỉnh phân trang */
</style>

<div class="page-toolbar">
    <div>
        <h1 class="page-title">Dự án</h1>
    </div>

    <div class="page-actions">
        <button id="filterButton" class="btn btn-outline-secondary" title="Lọc dữ liệu" data-bs-toggle="modal"
            data-bs-target="#filterModal">
            <i data-lucide="filter"></i>
            <span class="d-none d-md-inline">Bộ lọc</span>
        </button>
        <?php if ($canCreateProject): ?>
        <a href="<?= URLROOT; ?>/projects/createWizard" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Thêm mới</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="table-container table-container-paginated mb-3">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="text-center col-stt">STT</th>
                    <th scope="col">Dự án</th>
                    <th scope="col">Mã dự án</th>
                    <th scope="col">Phụ trách</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Thời hạn</th>
                    <th scope="col" class="text-center col-actions"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($projects)): ?>
                    <?php foreach ($projects as $index => $project): ?>
                        <?php
                        $statusName = $project['status_name'] ?? 'Không rõ';
                        $statusColor = $safeHexColor($project['status_color'] ?? null);

                        $deleteMessage = 'Bạn có chắc chắn muốn xóa dự án ' . ($project['name'] ?? '') . '?';
                        ?>
                        <tr>
                            <td class="text-center text-stt"><?= ($currentPage - 1) * $perPage + $index + 1 ?></td>
                            <td>
                                <a href="<?= URLROOT ?>/projects/<?= (int) $project['id'] ?>" class="d-block text-decoration-none text-name">
                                    <?= htmlspecialchars((string) ($project['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td>
                                <span class="ui-badge status-muted"><?= htmlspecialchars((string) ($project['project_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-slate-100 text-slate-500 rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 10px; border: 1px solid #e2e8f0;">
                                        <i data-lucide="user" style="width: 12px; height: 12px;"></i>
                                    </div>
                                    <span class="small text-slate-600"><?= htmlspecialchars((string) ($project['manager_name'] ?? 'Chưa gán'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="status-chip" style="--status-color: <?= htmlspecialchars($statusColor, ENT_QUOTES, 'UTF-8') ?>;">
                                    <span class="status-chip-dot"></span>
                                    <span class="status-chip-label"><?= htmlspecialchars($statusName, ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                            </td>
                            <td class="text-meta">
                                <?php if (!empty($project['start_date']) || !empty($project['due_date'])): ?>
                                    <?= $formatDate($project['start_date'] ?? null) ?>
                                    <span class="text-slate-400">→</span>
                                    <?= $formatDate($project['due_date'] ?? null) ?>
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
                                        <?php if (!empty($project['can_update'])): ?>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/projects/<?= (int) $project['id'] ?>/edit">
                                                <i data-lucide="edit-3"></i> Chỉnh sửa
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <?php if (!empty($project['can_delete'])): ?>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                href="javascript:void(0)"
                                                onclick="showDeleteModal('<?= URLROOT ?>/projects/<?= (int) $project['id'] ?>/delete', <?= htmlspecialchars((string) json_encode($deleteMessage, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i data-lucide="trash-2"></i> Xóa
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="table-empty">Không có dự án nào được tìm thấy.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="table-footer-outside d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <?php
    // Hàm hỗ trợ tạo URL phân trang giữ lại các tham số filter hiện tại (search, status, v.v.)
    $buildPageUrl = function ($page) use ($currentFilters) {
        $queryParams = [];
        if (!empty($currentFilters['status_id'])) {
            $queryParams['status_id'] = array_map('intval', $currentFilters['status_id']);
        }
        if (!empty($currentFilters['start_date'])) {
            $queryParams['start_date'] = $currentFilters['start_date'];
        }
        if (!empty($currentFilters['end_date'])) {
            $queryParams['end_date'] = $currentFilters['end_date'];
        }
        $queryParams['page'] = $page;
        return '?' . http_build_query($queryParams);
    };
    ?>
    <?php
    $from = ($totalItem > 0) ? ($currentPage - 1) * $perPage + 1 : 0;
    $to = min($currentPage * $perPage, $totalItem);
    ?>
    <span class="table-pagination-info">
        Hiển thị <?= $from ?> đến <?= $to ?> của <?= $totalItem ?> kết quả
    </span>
    <div class="d-flex align-items-center gap-2">
        <nav aria-label="Điều hướng trang">
            <ul class="pagination pagination-sm m-0">
                <?php if ($currentPage == 1): ?>
                    <li class="page-item disabled"> <!-- Nút Previous bị disabled khi ở trang đầu -->
                        <span class="page-link">
                            <i data-lucide="chevron-left" class="size-4"></i>
                        </span>
                    </li>
                <?php else: ?>
                    <li class="page-item"> <!-- Sửa nút Previous khi trang > 1 -->
                        <a class="page-link" href="<?= $buildPageUrl($currentPage - 1) ?>">
                            <i data-lucide="chevron-left" class="size-4"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $max_visible = $maxVisiblePages;
                if ($totalPage <= $max_visible):
                    for ($i = 1; $i <= $totalPage; $i++): ?>
                        <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                            <?php if ($i == $currentPage): ?>
                                <span class="page-link"><?= $i ?></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= $buildPageUrl($i) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        </li>
                        <?php endfor;
                else:
                    // Xác định vị trí bắt đầu để giữ số lượng nút luôn là 5
                    $start = max(1, min($currentPage - 2, $totalPage - $max_visible + 1));
                    for ($j = 0; $j < $max_visible; $j++):
                        $p = $start + $j;
                        if ($j == 0 && $p > 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php elseif ($j == $max_visible - 1 && $p < $totalPage): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php else: ?>
                            <li class="page-item <?= ($p == $currentPage) ? 'active' : '' ?>">
                                <?php if ($p == $currentPage): ?>
                                    <span class="page-link"><?= $p ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="<?= $buildPageUrl($p) ?>"><?= $p ?></a>
                                <?php endif; ?>
                            </li>
                <?php endif;
                    endfor;
                endif; ?>

                <?php if ($currentPage >= $totalPage): ?>
                    <li class="page-item disabled"> <!-- Nút Next bị disabled khi ở trang cuối -->
                        <span class="page-link">
                            <i data-lucide="chevron-right" class="size-4"></i>
                        </span>
                    </li>
                <?php else: ?>
                    <li class="page-item"> <!-- Nút Next -->
                        <a class="page-link" href="<?= $buildPageUrl($currentPage + 1) ?>">
                            <i data-lucide="chevron-right" class="size-4"></i>
                        </a>
                    </li>
                <?php endif; ?>


            </ul>
        </nav>
    </div>

</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-confirm-dialog">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Bộ lọc dự án</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="GET">
                    <input type="hidden" name="page" value="1"> <!-- Reset to page 1 on filter -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Trạng thái dự án</label>
                        <?php if (!empty($statusOptions)): ?>
                            <?php foreach ($statusOptions as $status): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="status_id[]"
                                        value="<?= htmlspecialchars($status['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        id="statusCheck<?= htmlspecialchars($status['id'], ENT_QUOTES, 'UTF-8') ?>"
                                        <?= in_array((int) $status['id'], array_map('intval', $currentFilters['status_id'] ?? []), true) ? 'checked' : '' ?>>
                                    <label class="form-check-label"
                                        for="statusCheck<?= htmlspecialchars($status['id'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($status['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="small text-muted">Không có trạng thái nào.</p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="startDateFilter" class="form-label fw-semibold small">Ngày bắt đầu</label>
                        <input type="date" class="form-control form-control-sm" id="startDateFilter" name="start_date"
                            value="<?= htmlspecialchars($currentFilters['start_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="endDateFilter" class="form-label fw-semibold small">Ngày kết thúc</label>
                        <input type="date" class="form-control form-control-sm" id="endDateFilter" name="end_date"
                            value="<?= htmlspecialchars($currentFilters['end_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="d-flex gap-2 pt-3 border-top mt-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Áp dụng</button>
                        <a href="<?= URLROOT ?>/projects" class="btn btn-outline-secondary btn-sm w-100">Đặt lại</a>
                    </div>
                </form>
            </div>
        </div>
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

<?php
/**
 * Giao diện danh sách công việc - NexusPM
 * 
 * @var array $tasks Danh sách công việc
 * @var array $projects Danh sách dự án (cho bộ lọc)
 * @var array $users Danh sách nhân viên (cho bộ lọc)
 * @var array $statuses Danh sách trạng thái (cho bộ lọc)
 * @var array $filters Dữ liệu filter hiện tại từ request
 * @var array $pagination Thông tin phân trang (current_page, total_pages, etc.)
 * @var array|null $selectedProject Thông tin dự án chi tiết đang chọn
 */




$tasks = $tasks ?? [];
$projects = $projects ?? [];
$users = $users ?? [];
$statuses = $statuses ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? [];
$canCreateTask = $canCreateTask ?? false;
$listTableConfig = \App\helpers\ListTableHelper::config();
$maxVisiblePages = max(1, (int) ($listTableConfig['max_visible_pages'] ?? 5));

/**
 * Trích xuất thông tin phân trang
 */
$currentPage = $pagination['current_page'] ?? 1;
$perPage = $pagination['per_page'] ?? max(count($tasks), 1);
$totalItem = $pagination['total_items'] ?? count($tasks);
$totalPage = $pagination['total_pages'] ?? max((int) ceil($totalItem / $perPage), 1);
$currentListQuery = $_GET;
if (!empty($filters['project_id']) && empty($currentListQuery['project_id'])) {
    $currentListQuery['project_id'] = (int) $filters['project_id'];
}
$currentTaskListUrl = URLROOT . '/tasks' . ($currentListQuery ? '?' . http_build_query($currentListQuery) : '');
$resetFilterQuery = [];
if (!empty($filters['project_id'])) {
    $resetFilterQuery['project_id'] = (int) $filters['project_id'];
}
$resetFilterUrl = URLROOT . '/tasks' . ($resetFilterQuery ? '?' . http_build_query($resetFilterQuery) : '');
$activeFilterCount = 0;
foreach (['search', 'assigned_to', 'status_id'] as $filterKey) {
    if (!empty($filters[$filterKey])) {
        $activeFilterCount++;
    }
}

/**
 * Hàm closure để tạo URL ảnh đại diện hoặc UI Avatars nếu trống.
 */
$buildAvatar = static function (array $person, string $nameKey = 'name', string $avatarKey = 'avatar', int $size = 32): string {
    $avatar = $person[$avatarKey] ?? null;
    if (!empty($avatar) && file_exists(APPROOT . '/public/uploads/avatars/' . $avatar)) {
        return URLROOT . '/uploads/avatars/' . rawurlencode($avatar);
    }
    $name = $person[$nameKey] ?? 'User';
    return 'https://ui-avatars.com/api/?name=' . urlencode((string) $name) . '&background=E2E8F0&color=0F172A&rounded=true&size=' . $size;
};

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

?>

<style>
    .task-list-title {
        max-width: 360px;
    }

    .project-context-detail-link {
        align-items: center;
        border-radius: 999px;
        color: var(--slate-500);
        display: inline-flex;
        font-size: 0.6875rem;
        font-weight: 700;
        gap: 0.25rem;
        line-height: 1;
        padding: 0.2rem 0.35rem;
        text-decoration: none;
        transition: background-color 0.18s ease, color 0.18s ease;
    }

    .project-context-detail-link:hover,
    .project-context-detail-link:focus {
        background: var(--slate-100);
        color: var(--primary-600);
    }

    .project-context-detail-link svg {
        height: 12px;
        width: 12px;
    }

    /* Tùy chỉnh phân trang */
    /* Thiết lập chiều cao cố định và thanh cuộn cho container bảng */
    /* Cố định tiêu đề bảng (Sticky Header) */
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/tasks" class="text-decoration-none text-slate-500 hover-text-primary">Công việc</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Danh sách</span>
    </div>

</div>
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 px-1">
    <?php
    $projectSwitcherAllowAll = true;
    $projectSwitcherMode = 'list';
    $projectSwitcherAllUrl = URLROOT . '/tasks?project_id=';
    $projectSwitcherTitle = $selectedProject ? (string) $selectedProject['name'] : 'Tất cả công việc';
    $projectSwitcherTaskCount = $selectedProject ? $totalItem : null;
    $projectSwitcherDetailUrl = !empty($selectedProject['id']) ? URLROOT . '/projects/' . (int) $selectedProject['id'] : null;
    $projectSwitcherDetailLabel = 'Chi tiết dự án';
    require VIEW_PATH . '/partials/project_switcher.php';
    ?>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="dropdown filter-dropdown">
            <button id="filterButton" class="btn btn-outline-secondary" type="button" title="Lọc dữ liệu" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                <i data-lucide="filter"></i>
                <span class="d-none d-md-inline">Bộ lọc</span>
                <?php if ($activeFilterCount > 0): ?>
                    <span class="filter-count"><?= $activeFilterCount ?></span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end filter-menu" aria-labelledby="filterButton">
                <form action="<?= URLROOT ?>/tasks" method="GET" class="filter-form">
                    <input type="hidden" name="page" value="1">
                    <?php if (!empty($filters['project_id'])): ?>
                        <input type="hidden" name="project_id" value="<?= (int) $filters['project_id'] ?>">
                    <?php endif; ?>
                    <div class="filter-header">
                        <span class="filter-title">Bộ lọc công việc</span>
                        <?php if ($activeFilterCount > 0): ?>
                            <span class="ui-badge status-muted py-0 px-2" style="font-size: 11px;"><?= $activeFilterCount ?> đang bật</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-slate-600">Tìm kiếm tiêu đề</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-slate-400"><i data-lucide="search" size="16"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="Nhập từ khóa..." value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-slate-600">Người thực hiện</label>
                        <select name="assigned_to" class="form-select form-select-sm">
                            <option value="">-- Tất cả nhân viên --</option>
                            <?php foreach($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= (isset($filters['assigned_to']) && $filters['assigned_to'] == $u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-slate-600">Trạng thái</label>
                        <select name="status_id" class="form-select form-select-sm">
                            <option value="">-- Tất cả trạng thái --</option>
                            <?php foreach($statuses as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= (isset($filters['status_id']) && $filters['status_id'] == $s['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <a href="<?= htmlspecialchars($resetFilterUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm w-100">Đặt lại</a>
                        <button type="submit" class="btn btn-primary btn-sm w-100">Áp dụng</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($selectedProject): ?>
            <a href="<?= URLROOT ?>/tasks/<?= (int) $selectedProject['id'] ?>/kanban" class="btn btn-outline-secondary">
                <i data-lucide="columns-3"></i>
                <span class="d-none d-md-inline">Bảng Kanban</span>
            </a>
        <?php endif; ?>

        <?php if ($canCreateTask): ?>
        <a href="<?= URLROOT ?>/tasks/create<?= $selectedProject ? '?project_id=' . (int) $selectedProject['id'] : '' ?>" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Thêm mới</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Danh sách Công việc -->
<div class="table-container table-container-paginated mb-3">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th class="col-stt text-center">STT</th>
                    <th>Tiêu đề công việc</th>
                    <th>Dự án</th>
                    <th>Người thực hiện</th>
                    <th>Trạng thái</th>
                    <th>Mức độ ưu tiên</th>
                    <th>Hạn chót</th>
                    <th class="col-actions text-center"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tasks)): ?>
                    <?php foreach ($tasks as $index => $task): ?>
                        <?php
                        // Xử lý nhãn và class cho mức độ ưu tiên
                        $priorityLabel = match($task['priority'] ?? '') {
                            'urgent' => 'Khẩn cấp',
                            'high'   => 'Cao',
                            'medium' => 'Trung bình',
                            'low'    => 'Thấp',
                            default  => 'N/A'
                        };
                        $priorityClass = match($task['priority'] ?? '') {
                            'urgent', 'high' => 'priority-high',
                            'medium'         => 'priority-medium',
                            'low'            => 'priority-low',
                            default          => 'status-muted'
                        };
                        
                        $isDoneStatus = !empty($task['status_is_done']) || ($task['status_slug'] ?? '') === 'done';
                        $isOverdue = !empty($task['due_date']) && strtotime($task['due_date']) < strtotime(date('Y-m-d')) && !$isDoneStatus;
                        ?>
                        <tr>
                            <td class="text-center text-stt"><?= ($currentPage - 1) * $perPage + $index + 1 ?></td>
                            <td>
                                <a href="<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/edit" class="text-decoration-none text-name d-inline-block text-truncate task-list-title">
                                    <?= htmlspecialchars($task['title']) ?>
                                </a>
                            </td>
                            <td class="text-meta"><?= htmlspecialchars($task['project_name'] ?? '-') ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= $buildAvatar(['name' => $task['assigned_name'] ?? 'Chưa giao', 'avatar' => $task['assigned_avatar'] ?? null]) ?>" class="avatar" alt="">
                                    <span class="text-meta"><?= htmlspecialchars($task['assigned_name'] ?? 'Chưa giao') ?></span>
                                </div>
                            </td>
                            <td>
                                <?php $taskStatusColor = $safeHexColor($task['status_color'] ?? null); ?>
                                <span class="status-chip" style="--status-color: <?= htmlspecialchars($taskStatusColor, ENT_QUOTES, 'UTF-8') ?>;">
                                    <span class="status-chip-dot"></span>
                                    <span class="status-chip-label"><?= htmlspecialchars($task['status_name'] ?? 'N/A') ?></span>
                                </span>
                            </td>
                            <td><span class="ui-badge <?= $priorityClass ?>"><?= $priorityLabel ?></span></td>
                            <td class="<?= $isOverdue ? 'text-danger fw-bold' : 'text-slate-600' ?>">
                                <?= !empty($task['due_date']) ? date('d/m/Y', strtotime($task['due_date'])) : '-' ?>
                            </td>
                            <td>
                                <?php if (!empty($task['can_update']) || !empty($task['can_delete'])): ?>
                                <div class="dropdown position-static">
                                    <button class="btn btn-link btn-action shadow-none"
                                        data-bs-toggle="dropdown"><i data-lucide="more-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if (!empty($task['can_update'])): ?>
                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/edit"><i data-lucide="edit-3" class="text-slate-600"></i> Chỉnh sửa</a></li>
                                        <?php endif; ?>
                                        <?php if (!empty($task['can_delete'])): ?>
                                        <?php if (!empty($task['can_update'])): ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <?php endif; ?>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                href="javascript:void(0)"
                                                onclick="showDeleteModal('<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/delete', <?= htmlspecialchars(json_encode('Bạn có chắc chắn muốn xóa công việc ' . ($task['title'] ?? '') . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i data-lucide="trash-2"></i> Xóa
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="table-empty">Không tìm thấy dữ liệu công việc.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Hiển thị phân trang -->
<div class="table-footer-outside d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <?php
    // Hàm hỗ trợ tạo URL phân trang giữ lại các tham số filter hiện tại
    $buildPageUrl = function ($page) {
        $queryParams = $_GET;
        $queryParams['page'] = $page;
        return '?' . http_build_query($queryParams);
    };

    $from = ($totalItem > 0) ? ($currentPage - 1) * $perPage + 1 : 0;
    $to = min($currentPage * $perPage, $totalItem);
    ?>
    <span class="table-pagination-info text-slate-500 small">
        Hiển thị <?= $from ?> đến <?= $to ?> của <?= $totalItem ?> kết quả
    </span>
    <div class="d-flex align-items-center gap-2">
        <nav aria-label="Điều hướng trang">
            <ul class="pagination pagination-sm m-0">
                <?php if ($currentPage == 1): ?>
                    <li class="page-item disabled">
                        <span class="page-link"><i data-lucide="chevron-left" size="16"></i></span>
                    </li>
                <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $buildPageUrl($currentPage - 1) ?>">
                            <i data-lucide="chevron-left" size="16"></i>
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
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i data-lucide="chevron-right" size="16"></i>
                        </span>
                    </li>
                <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $buildPageUrl($currentPage + 1) ?>">
                            <i data-lucide="chevron-right" size="16"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Delete Confirm Modal -->
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
                        <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($currentTaskListUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger w-100">Xác nhận xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Giao diện bảng Kanban - NexusPM
 *
 * @var array $project Thông tin dự án
 * @var array $statuses Danh sách trạng thái công việc của dự án
 * @var array $users Danh sách nhân viên cho bộ lọc
 * @var array $filters Dữ liệu filter hiện tại từ request
 * @var array $groupedTasks Công việc đã được nhóm theo status_id
 */

$project = $project ?? [];
$projects = $projects ?? [];
$statuses = $statuses ?? [];
$users = $users ?? [];
$filters = $filters ?? [];
$groupedTasks = $groupedTasks ?? [];
$selectedProject = $project;
$canCreateTask = $canCreateTask ?? false;
$canUpdateProjectTasks = $canUpdateProjectTasks ?? false;

if (empty($projects) && !empty($project)) {
    $projects = [$project];
}

$totalTasks = 0;
$today = strtotime(date('Y-m-d'));

foreach ($statuses as $status) {
    $totalTasks += count($groupedTasks[$status['id']] ?? []);
}

$kanbanProjectId = (int) ($selectedProject['id'] ?? 0);
$kanbanFilterUrl = URLROOT . "/tasks/{$kanbanProjectId}/kanban";
$activeFilterCount = 0;
foreach (['search', 'assigned_to'] as $filterKey) {
    if (!empty($filters[$filterKey])) {
        $activeFilterCount++;
    }
}

$buildAvatar = static function (array $task, int $size = 28): string {
    $avatar = $task['assigned_avatar'] ?? null;
    if (!empty($avatar) && file_exists(APPROOT . '/public/uploads/avatars/' . $avatar)) {
        return URLROOT . '/uploads/avatars/' . rawurlencode($avatar);
    }

    $name = $task['assigned_name'] ?? 'Chưa giao';
    return 'https://ui-avatars.com/api/?name=' . urlencode((string) $name) . '&background=E2E8F0&color=0F172A&rounded=true&size=' . $size;
};

$formatDate = static function (?string $date, string $format = 'd/m/Y'): string {
    return !empty($date) ? date($format, strtotime($date)) : '-';
};
?>

<style>
    .kanban-shell {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .kanban-board-wrap {
        overflow-x: auto;
        padding-bottom: 0.6rem;
        scrollbar-color: var(--slate-300) transparent;
        scrollbar-width: thin;
    }

    .kanban-board {
        align-items: flex-start;
        display: flex;
        gap: 0.75rem;
        min-height: calc(100vh - 275px);
        min-width: 100%;
        padding: 0.125rem 0 0.6rem;
        width: 100%;
    }

    .kanban-column {
        background: #ffffff;
        border: 1px solid rgba(218, 220, 224, 0.9);
        border-top: 3px solid var(--status-color, var(--slate-300));
        border-radius: var(--radius-md);
        box-shadow: none;
        display: flex;
        flex: 1 1 0;
        flex-direction: column;
        min-height: 18rem;
        min-width: 220px;
        overflow: visible;
    }

    .kanban-column-header {
        background: #ffffff;
        border-bottom: 1px solid var(--slate-100);
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        padding: 0.75rem 0.85rem;
    }

    .kanban-status-row {
        align-items: center;
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .kanban-status-name {
        align-items: center;
        color: var(--slate-900);
        display: flex;
        font-size: 0.925rem;
        font-weight: 700;
        gap: 0.5rem;
        min-width: 0;
    }

    .kanban-status-dot {
        background: var(--status-color, var(--slate-400));
        border-radius: 999px;
        flex: 0 0 0.55rem;
        height: 0.55rem;
        width: 0.55rem;
    }

    .kanban-status-name span:last-child {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .kanban-count {
        background: var(--slate-100);
        border: 1px solid var(--slate-200);
        border-radius: 999px;
        color: var(--slate-600);
        flex-shrink: 0;
        font-size: 0.75rem;
        font-weight: 700;
        line-height: 1;
        min-width: 1.7rem;
        padding: 0.35rem 0.55rem;
        text-align: center;
    }

    .kanban-tasks {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        gap: 0.6rem;
        min-height: 11rem;
        overflow-y: visible;
        padding: 0.65rem;
    }

    .kanban-empty {
        align-items: center;
        background: rgba(255, 255, 255, 0.56);
        border: 0;
        border-radius: var(--radius-sm);
        color: var(--slate-400);
        display: flex;
        font-size: 0.8125rem;
        justify-content: center;
        min-height: 5.5rem;
        padding: 1rem;
        text-align: center;
    }

    .kanban-tasks.has-task .kanban-empty {
        display: none;
    }

    .task-card {
        background: color-mix(in srgb, var(--status-color, var(--slate-300)) 10%, #ffffff);
        border: 1px solid color-mix(in srgb, var(--status-color, var(--slate-300)) 24%, #ffffff);
        border-left: 3px solid var(--status-color, var(--slate-300));
        border-radius: var(--radius-sm);
        box-shadow: none;
        color: inherit;
        cursor: grab;
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
        min-height: 9rem;
        padding: 0.9rem 0.85rem;
        text-decoration: none;
        transition: background-color 0.18s ease, border-color 0.18s ease;
    }

    .task-card:hover {
        border-color: color-mix(in srgb, var(--status-color, var(--primary-300)) 42%, #ffffff);
        background-color: color-mix(in srgb, var(--status-color, var(--slate-300)) 15%, #ffffff);
        color: inherit;
    }

    .task-card:active {
        cursor: grabbing;
    }

    .task-card-top {
        align-items: flex-start;
        display: flex;
        gap: 0.65rem;
        justify-content: space-between;
    }

    .task-card-title {
        color: var(--slate-900);
        display: -webkit-box;
        flex: 1 1 auto;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.4;
        overflow: hidden;
        text-decoration: none;
        transition: color 0.18s ease;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }

    .task-card-title:hover,
    .task-card:hover .task-card-title {
        color: var(--primary-600);
    }

    .task-card-menu {
        flex: 0 0 auto;
        margin-right: -0.35rem;
        margin-top: -0.35rem;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.16s ease, visibility 0.16s ease;
        visibility: hidden;
    }

    .task-card:hover .task-card-menu,
    .task-card:focus-within .task-card-menu {
        opacity: 1;
        pointer-events: auto;
        visibility: visible;
    }

    .task-card-menu .btn-action {
        background: transparent !important;
        border-color: transparent !important;
        color: var(--slate-500);
        height: 28px;
        padding: 0;
        width: 28px;
    }

    .task-card-menu .btn-action:hover,
    .task-card-menu .btn-action:focus {
        background: transparent !important;
        color: var(--slate-800);
    }

    .task-card-menu .dropdown-menu {
        min-width: 10.5rem;
    }

    .task-card-menu .dropdown-item {
        align-items: center;
        display: flex;
        gap: 0.5rem;
        padding: 0.55rem 0.85rem;
    }

    .task-card-menu .dropdown-item svg {
        height: 16px;
        width: 16px;
    }

    .task-card-menu .task-card-delete-item,
    .task-card-menu .task-card-delete-item svg {
        color: var(--md-error, var(--red-600));
    }

    .task-deadline,
    .task-card-footer {
        align-items: center;
        display: flex;
        gap: 0.55rem;
        min-width: 0;
    }

    .task-deadline {
        justify-content: flex-start;
    }

    .task-card-footer {
        justify-content: space-between;
        margin-top: auto;
    }

    .task-meta {
        align-items: center;
        color: var(--slate-500);
        display: inline-flex;
        font-size: 0.72rem;
        font-weight: 500;
        gap: 0.25rem;
        line-height: 1.3;
        min-width: 0;
    }

    .task-meta svg {
        flex: 0 0 12px;
        height: 12px;
        width: 12px;
    }

    .task-meta.is-overdue {
        color: var(--red-600);
        font-weight: 700;
    }

    .task-assignee {
        align-items: center;
        display: inline-flex;
        gap: 0.4rem;
        min-width: 0;
        justify-content: flex-end;
    }

    .task-assignee img {
        border: 1px solid var(--slate-200);
        border-radius: 50%;
        flex-shrink: 0;
        height: 24px;
        object-fit: cover;
        width: 24px;
    }

    .task-assignee span {
        color: var(--slate-600);
        font-size: 0.75rem;
        font-weight: 600;
        max-width: 7.5rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .kanban-state {
        align-items: center;
        background: #ffffff;
        border: 1px solid rgba(218, 220, 224, 0.7);
        border-radius: var(--radius-lg);
        box-shadow: none;
        color: var(--slate-500);
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        justify-content: center;
        min-height: 18rem;
        padding: 2rem;
        text-align: center;
    }

    .kanban-save-state {
        align-items: center;
        color: var(--slate-500);
        display: inline-flex;
        font-size: 0.8125rem;
        font-weight: 500;
        gap: 0.4rem;
        min-height: 1.5rem;
    }

    .kanban-save-state.text-danger {
        color: var(--red-600) !important;
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

    .sortable-ghost {
        background: var(--primary-50) !important;
        border: 1px dashed var(--primary-600) !important;
        opacity: 0.75;
    }

    .sortable-chosen {
        box-shadow: none !important;
    }

    .sortable-drag {
        opacity: 0.95 !important;
    }

    /* Material 3 productivity refinement */
    .kanban-board {
        gap: 0.75rem;
    }

    .kanban-column,
    .kanban-state {
        background: var(--md-content-surface);
        border: 0;
        border-radius: var(--radius-lg);
        box-shadow: none;
    }

    .kanban-column {
        border-top-width: 0;
    }

    .kanban-column-header {
        background: var(--md-content-surface-strong);
        border-bottom-color: transparent;
        border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    }

    .kanban-status-name {
        color: var(--md-on-surface);
        font-weight: 500;
    }

    .kanban-count {
        background: var(--md-surface);
        border: 0;
        color: var(--md-on-surface-variant);
    }

    .task-card {
        background: color-mix(in srgb, var(--status-color, var(--md-outline-variant)) 10%, var(--md-surface));
        border: 0;
        border-left: 3px solid var(--status-color, var(--md-outline-variant));
        border-radius: var(--radius-md);
        box-shadow: none;
    }

    .task-card:hover {
        background-color: color-mix(in srgb, var(--status-color, var(--md-outline-variant)) 15%, var(--md-surface-container-low));
        box-shadow: none;
    }

    .task-card-title {
        color: var(--md-on-surface);
        font-weight: 500;
    }

    .task-meta,
    .task-assignee span,
    .kanban-save-state {
        color: var(--md-on-surface-variant);
    }

    @media (hover: none) {
        .task-card-menu {
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
        }
    }

    @media (max-width: 767.98px) {
        .kanban-column {
            max-height: none;
            min-width: 180px;
        }

        .kanban-board {
            min-height: 28rem;
        }

        .kanban-column-header {
            padding: 0.65rem 0.7rem;
        }

        .kanban-status-name {
            font-size: 0.8125rem;
            gap: 0.4rem;
        }

        .kanban-count {
            font-size: 0.6875rem;
            min-width: 1.45rem;
            padding: 0.28rem 0.45rem;
        }

        .kanban-tasks {
            gap: 0.5rem;
            padding: 0.55rem;
        }

        .task-card {
            min-height: 8.5rem;
            padding: 0.75rem 0.7rem;
        }
    }

    @media (min-width: 768px) and (max-width: 1199.98px) {
        .kanban-column {
            min-width: 200px;
        }
    }

    @media (max-width: 480px) {
        .kanban-board {
            gap: 0.5rem;
        }

        .kanban-column {
            min-width: 172px;
        }
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/tasks" class="text-decoration-none text-slate-500 hover-text-primary">Công việc</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Kanban</span>
    </div>
</div>

<div class="kanban-shell">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-3 px-1">
        <?php
        $projectSwitcherAllowAll = false;
        $projectSwitcherMode = 'kanban';
        $projectSwitcherAllUrl = URLROOT . '/tasks?project_id=';
        $projectSwitcherTitle = !empty($selectedProject['name']) ? (string) $selectedProject['name'] : 'Chọn dự án';
        $projectSwitcherTaskCount = $totalTasks;
        $projectSwitcherDetailUrl = !empty($selectedProject['id']) ? URLROOT . '/projects/' . (int) $selectedProject['id'] : null;
        $projectSwitcherDetailLabel = 'Chi tiết dự án';
        require VIEW_PATH . '/partials/project_switcher.php';
        ?>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="dropdown filter-dropdown">
                <button id="filterButton" class="btn btn-outline-secondary" type="button" title="Lọc dữ liệu" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i data-lucide="filter" size="18"></i>
                    <span class="d-none d-md-inline">Bộ lọc</span>
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="filter-count"><?= $activeFilterCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end filter-menu" aria-labelledby="filterButton">
                    <form action="<?= htmlspecialchars($kanbanFilterUrl, ENT_QUOTES, 'UTF-8') ?>" method="GET" class="filter-form">
                        <div class="filter-header">
                            <span class="filter-title">Bộ lọc Kanban</span>
                            <?php if ($activeFilterCount > 0): ?>
                                <span class="ui-badge status-muted py-0 px-2" style="font-size: 11px;"><?= $activeFilterCount ?> đang bật</span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-slate-600">Tìm kiếm tiêu đề</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-slate-400"><i data-lucide="search" size="16"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="Nhập từ khóa..." value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-slate-600">Người thực hiện</label>
                            <select name="assigned_to" class="form-select form-select-sm">
                                <option value="">-- Tất cả nhân viên --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= (int) $u['id'] ?>" <?= (isset($filters['assigned_to']) && (string) $filters['assigned_to'] === (string) $u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-actions">
                            <a href="<?= htmlspecialchars($kanbanFilterUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm w-100">Đặt lại</a>
                            <button type="submit" class="btn btn-primary btn-sm w-100">Áp dụng</button>
                        </div>
                    </form>
                </div>
            </div>
            <a href="<?= URLROOT ?>/tasks/<?= (int) ($selectedProject['id'] ?? 0) ?>/list" class="btn btn-outline-secondary">
                <i data-lucide="list" size="18"></i>
                <span>Dạng list</span>
            </a>
            <?php if ($canCreateTask): ?>
            <a href="<?= URLROOT ?>/tasks/create?project_id=<?= (int) ($selectedProject['id'] ?? 0) ?>" class="btn btn-primary">
                <i data-lucide="plus" size="18"></i>
                <span>Thêm mới</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap px-1">
        <div class="text-slate-500 small d-flex align-items-center gap-2">
            <i data-lucide="grip" size="16"></i>
            <span>Kéo thả thẻ công việc giữa các cột để cập nhật trạng thái.</span>
        </div>
        <div class="kanban-save-state" id="kanban-save-state" aria-live="polite"></div>
    </div> -->

    <?php if (!empty($statuses)): ?>
        <div class="kanban-board-wrap">
            <div class="kanban-board" id="kanban-board">
                <?php foreach ($statuses as $status): ?>
                    <?php
                    $statusId = (int) $status['id'];
                    $statusColor = $status['color'] ?? '#9aa0a6';
                    $statusTasks = $groupedTasks[$statusId] ?? [];
                    $statusTaskCount = count($statusTasks);
                    ?>
                    <section class="kanban-column" data-status-id="<?= $statusId ?>" style="--status-color: <?= htmlspecialchars($statusColor, ENT_QUOTES, 'UTF-8') ?>;">
                        <header class="kanban-column-header">
                            <div class="kanban-status-row">
                                <div class="kanban-status-name" title="<?= htmlspecialchars((string) ($status['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="kanban-status-dot"></span>
                                    <span><?= htmlspecialchars((string) ($status['name'] ?? 'Chưa đặt tên'), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                                <span class="kanban-count" data-kanban-count><?= $statusTaskCount ?></span>
                            </div>
                        </header>

                        <div class="kanban-tasks <?= $statusTaskCount > 0 ? 'has-task' : '' ?>" id="tasks-status-<?= $statusId ?>">
                            <div class="kanban-empty">Thả công việc vào đây</div>

                            <?php foreach ($statusTasks as $task): ?>
                                <?php
                                $isDoneTask = !empty($task['status_is_done']) || ($task['status_slug'] ?? '') === 'done' || !empty($status['is_done']);
                                $isOverdue = !empty($task['due_date']) && strtotime($task['due_date']) < $today && !$isDoneTask;
                                ?>
                                <div class="task-card" data-task-id="<?= (int) $task['id'] ?>">
                                    <div class="task-card-top">
                                        <a class="task-card-title" href="<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/edit">
                                            <?= htmlspecialchars((string) ($task['title'] ?? 'Không có tiêu đề'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <?php if (!empty($task['can_update']) || !empty($task['can_delete'])): ?>
                                        <div class="dropdown task-card-menu">
                                            <button class="btn btn-action border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mở hành động">
                                                <i data-lucide="more-vertical" size="18"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <?php if (!empty($task['can_update'])): ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/edit">
                                                        <i data-lucide="edit-3" class="text-slate-600"></i>
                                                        <span>Chỉnh sửa</span>
                                                    </a>
                                                </li>
                                                <?php endif; ?>
                                                <?php if (!empty($task['can_delete'])): ?>
                                                <?php if (!empty($task['can_update'])): ?>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <?php endif; ?>
                                                <li>
                                                    <button type="button" class="dropdown-item task-card-delete-item" onclick="showDeleteModal('<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/delete', <?= htmlspecialchars(json_encode('Xác nhận xóa công việc ' . ($task['title'] ?? '') . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                                        <i data-lucide="trash-2"></i>
                                                        <span>Xóa</span>
                                                    </button>
                                                </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="task-deadline">
                                        <div class="task-meta <?= $isOverdue ? 'is-overdue' : '' ?>">
                                            <i data-lucide="calendar-check" size="12"></i>
                                            <span>Hạn hoàn thành: <?= $formatDate($task['due_date'] ?? null, 'd/m/Y') ?></span>
                                        </div>
                                    </div>

                                    <div class="task-card-footer">
                                        <div class="task-meta" title="Khối lượng công việc">
                                            <i data-lucide="clock-3" size="12"></i>
                                            <span><?= number_format((float) ($task['estimated_hours'] ?? 0), 1) ?> giờ</span>
                                        </div>
                                        <div class="task-assignee" title="<?= htmlspecialchars((string) ($task['assigned_name'] ?? 'Chưa giao'), ENT_QUOTES, 'UTF-8') ?>">
                                            <img src="<?= $buildAvatar($task) ?>" alt="">
                                            <span><?= htmlspecialchars((string) ($task['assigned_name'] ?? 'Chưa giao'), ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="kanban-state">
            <i data-lucide="columns-3" size="40"></i>
            <div>
                <div class="fw-bold text-slate-800 mb-1">Dự án chưa có trạng thái công việc</div>
                <div>Hãy cấu hình trạng thái cho dự án để bắt đầu dùng bảng Kanban.</div>
            </div>
        </div>
    <?php endif; ?>
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
                        <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                        <button type="submit" class="btn btn-danger w-100">Xác nhận xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const columns = document.querySelectorAll('.kanban-tasks');
    const saveState = document.getElementById('kanban-save-state');
    const csrfToken = '<?= htmlspecialchars(\App\helpers\SecurityHelper::generateToken(), ENT_QUOTES, 'UTF-8') ?>';
    const canUpdateProjectTasks = <?= json_encode((bool) $canUpdateProjectTasks) ?>;

    if (!canUpdateProjectTasks) {
        return;
    }

    function setSaveState(message, type = 'muted') {
        if (!saveState) return;

        const icon = type === 'danger' ? 'circle-alert' : (type === 'success' ? 'check-circle-2' : 'loader-circle');
        saveState.className = 'kanban-save-state' + (type === 'danger' ? ' text-danger' : '');
        saveState.innerHTML = message ? `<i data-lucide="${icon}" size="15"></i><span>${message}</span>` : '';

        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function refreshColumn(column) {
        const taskList = column.querySelector('.kanban-tasks');
        const count = taskList ? taskList.querySelectorAll('.task-card').length : 0;
        const countEl = column.querySelector('[data-kanban-count]');

        if (countEl) {
            countEl.textContent = count;
        }

        if (taskList) {
            taskList.classList.toggle('has-task', count > 0);
        }
    }

    function moveBack(item, sourceList, oldIndex) {
        const taskCards = Array.from(sourceList.querySelectorAll('.task-card'));
        const referenceNode = taskCards[oldIndex] || null;
        sourceList.insertBefore(item, referenceNode);
    }

    columns.forEach(function (column) {
        new Sortable(column, {
            group: 'kanban',
            animation: 160,
            draggable: '.task-card',
            filter: '.task-card-menu, .task-card-menu *',
            preventOnFilter: false,
            emptyInsertThreshold: 24,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',

            onStart: function () {
                setSaveState('');
            },

            onEnd: function (evt) {
                const item = evt.item;
                const taskId = item.getAttribute('data-task-id');
                const newColumn = evt.to.closest('.kanban-column');
                const oldColumn = evt.from.closest('.kanban-column');
                const newStatusId = newColumn ? newColumn.getAttribute('data-status-id') : '';
                const oldStatusId = oldColumn ? oldColumn.getAttribute('data-status-id') : '';

                refreshColumn(newColumn);
                if (oldColumn && oldColumn !== newColumn) {
                    refreshColumn(oldColumn);
                }

                if (!taskId || !newStatusId || (newStatusId === oldStatusId && evt.oldIndex === evt.newIndex)) {
                    return;
                }

                setSaveState('Đang lưu thay đổi...');

                const formData = new URLSearchParams();
                formData.append('task_id', taskId);
                formData.append('status_id', newStatusId);
                formData.append('csrf_token', csrfToken);

                fetch('<?= URLROOT ?>/tasks/update-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: formData.toString()
                })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Không thể cập nhật trạng thái.');
                            }
                            return data;
                        });
                    })
                    .then(function () {
                        setSaveState('Đã cập nhật trạng thái', 'success');
                        window.setTimeout(function () {
                            setSaveState('');
                        }, 1800);
                    })
                    .catch(function (error) {
                        moveBack(item, evt.from, evt.oldIndex);
                        refreshColumn(oldColumn);
                        refreshColumn(newColumn);
                        setSaveState(error.message || 'Có lỗi khi lưu thay đổi.', 'danger');
                    });
            }
        });
    });
});
</script>

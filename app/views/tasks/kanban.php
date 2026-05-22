<?php
/**
 * Giao diện bảng Kanban - NexusPM
 *
 * @var array $project Thông tin dự án
 * @var array $statuses Danh sách trạng thái công việc của dự án
 * @var array $groupedTasks Công việc đã được nhóm theo status_id
 */

$project = $project ?? [];
$projects = $projects ?? [];
$statuses = $statuses ?? [];
$groupedTasks = $groupedTasks ?? [];
$selectedProject = $project;

if (empty($projects) && !empty($project)) {
    $projects = [$project];
}

$totalTasks = 0;
$today = strtotime(date('Y-m-d'));

foreach ($statuses as $status) {
    $totalTasks += count($groupedTasks[$status['id']] ?? []);
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
        padding-bottom: 0.75rem;
        scrollbar-color: var(--slate-300) transparent;
        scrollbar-width: thin;
    }

    .kanban-board {
        align-items: flex-start;
        display: flex;
        gap: 1rem;
        min-height: calc(100vh - 275px);
        min-width: max-content;
        padding: 0.25rem 0.125rem 0.75rem;
    }

    .kanban-column {
        background: #ffffff;
        border: 1px solid rgba(218, 220, 224, 0.9);
        border-top: 4px solid var(--status-color, var(--slate-300));
        border-radius: var(--radius-md);
        box-shadow: var(--google-shadow-soft);
        display: flex;
        flex: 0 0 clamp(300px, 24vw, 360px);
        flex-direction: column;
        max-height: calc(100vh - 285px);
        min-height: 18rem;
        min-width: 300px;
        overflow: hidden;
    }

    .kanban-column-header {
        background: linear-gradient(180deg, rgba(248, 249, 250, 0.95), #ffffff);
        border-bottom: 1px solid var(--slate-100);
        padding: 0.9rem 1rem;
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
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--status-color, var(--slate-400)) 14%, transparent);
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

    .kanban-column-progress {
        background: var(--slate-100);
        border-radius: 999px;
        height: 5px;
        margin-top: 0.75rem;
        overflow: hidden;
    }

    .kanban-column-progress span {
        background: var(--status-color, var(--primary-600));
        border-radius: inherit;
        display: block;
        height: 100%;
        opacity: 0.85;
    }

    .kanban-tasks {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        gap: 0.75rem;
        min-height: 11rem;
        overflow-y: auto;
        padding: 0.85rem;
    }

    .kanban-empty {
        align-items: center;
        border: 1px dashed var(--slate-200);
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
        background: #ffffff;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-sm);
        box-shadow: 0 1px 2px rgba(60, 64, 67, 0.08);
        color: inherit;
        cursor: grab;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding: 0.9rem;
        text-decoration: none;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
    }

    .task-card:hover {
        border-color: var(--primary-200);
        box-shadow: var(--google-shadow-soft);
        color: inherit;
        transform: translateY(-1px);
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
        font-size: 0.925rem;
        font-weight: 700;
        line-height: 1.4;
        overflow: hidden;
        text-decoration: none;
        transition: color 0.18s ease;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .task-card-title:hover,
    .task-card:hover .task-card-title {
        color: var(--primary-600);
    }

    .task-card-menu {
        flex: 0 0 auto;
        margin-right: -0.35rem;
        margin-top: -0.35rem;
    }

    .task-card-menu .btn-action {
        color: var(--slate-500);
        height: 32px;
        width: 32px;
    }

    .task-card-menu .btn-action:hover,
    .task-card-menu .btn-action:focus {
        background-color: var(--slate-100);
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

    .task-date-range,
    .task-card-footer {
        align-items: center;
        display: flex;
        gap: 0.55rem;
        justify-content: space-between;
        min-width: 0;
    }

    .task-meta {
        align-items: center;
        color: var(--slate-500);
        display: inline-flex;
        font-size: 0.75rem;
        font-weight: 500;
        gap: 0.3rem;
        min-width: 0;
    }

    .task-date-range .task-meta {
        flex: 1 1 0;
    }

    .task-date-separator {
        color: var(--slate-300);
        flex: 0 0 auto;
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
        box-shadow: var(--google-shadow-soft);
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

    .sortable-ghost {
        background: var(--primary-50) !important;
        border: 1px dashed var(--primary-600) !important;
        opacity: 0.75;
    }

    .sortable-chosen {
        box-shadow: var(--google-shadow) !important;
    }

    .sortable-drag {
        opacity: 0.95 !important;
        transform: rotate(1deg);
    }

    @media (max-width: 767.98px) {
        .kanban-column {
            flex-basis: min(86vw, 320px);
            max-height: none;
        }

        .kanban-board {
            min-height: 28rem;
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
        $projectSwitcherAllUrl = URLROOT . '/tasks';
        $projectSwitcherTitle = !empty($selectedProject['name']) ? (string) $selectedProject['name'] : 'Chọn dự án';
        $projectSwitcherTaskCount = $totalTasks;
        require VIEW_PATH . '/partials/project_switcher.php';
        ?>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="<?= URLROOT ?>/tasks/<?= (int) ($selectedProject['id'] ?? 0) ?>/list" class="btn btn-outline-secondary">
                <i data-lucide="list" size="18"></i>
                <span>Dạng list</span>
            </a>
            <a href="<?= URLROOT ?>/projects/<?= (int) ($selectedProject['id'] ?? 0) ?>" class="btn btn-outline-secondary">
                <i data-lucide="folder-kanban" size="18"></i>
                <span>Dự án chi tiết</span>
            </a>
            <a href="<?= URLROOT ?>/tasks/create?project_id=<?= (int) ($selectedProject['id'] ?? 0) ?>" class="btn btn-primary">
                <i data-lucide="plus" size="18"></i>
                <span>Thêm mới</span>
            </a>
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
                    $columnPercent = $totalTasks > 0 ? round(($statusTaskCount / $totalTasks) * 100) : 0;
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
                            <div class="kanban-column-progress" title="<?= $columnPercent ?>% công việc của dự án">
                                <span style="width: <?= $columnPercent ?>%;"></span>
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
                                        <a class="task-card-title" href="<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>">
                                            <?= htmlspecialchars((string) ($task['title'] ?? 'Không có tiêu đề'), ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <div class="dropdown task-card-menu">
                                            <button class="btn btn-white btn-action border-0 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mở hành động">
                                                <i data-lucide="more-vertical" size="18"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <li>
                                                    <a class="dropdown-item" href="<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>">
                                                        <i data-lucide="eye" class="text-slate-600"></i>
                                                        <span>Chi tiết</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/edit">
                                                        <i data-lucide="edit-3" class="text-slate-600"></i>
                                                        <span>Chỉnh sửa</span>
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item text-danger" onclick="showDeleteModal('<?= URLROOT ?>/tasks/<?= (int) $task['id'] ?>/delete', <?= htmlspecialchars(json_encode('Xác nhận xóa công việc ' . ($task['title'] ?? '') . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                                        <i data-lucide="trash-2"></i>
                                                        <span>Xóa</span>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="task-date-range">
                                        <div class="task-meta">
                                            <i data-lucide="calendar-days" size="14"></i>
                                            <span><?= $formatDate($task['start_date'] ?? null, 'd/m/Y') ?></span>
                                        </div>
                                        <span class="task-date-separator">-</span>
                                        <div class="task-meta <?= $isOverdue ? 'is-overdue' : '' ?>">
                                            <i data-lucide="flag" size="14"></i>
                                            <span><?= $formatDate($task['due_date'] ?? null, 'd/m/Y') ?></span>
                                        </div>
                                    </div>

                                    <div class="task-card-footer">
                                        <div class="task-meta" title="Khối lượng công việc">
                                            <i data-lucide="clock-3" size="14"></i>
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const columns = document.querySelectorAll('.kanban-tasks');
    const saveState = document.getElementById('kanban-save-state');
    const csrfToken = '<?= htmlspecialchars(\App\helpers\SecurityHelper::generateToken(), ENT_QUOTES, 'UTF-8') ?>';

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

<?php

/**
 * @var array $projects Danh sách dự án
 * @var array $users Danh sách nhân viên
 * @var array $statuses Danh sách trạng thái
 * @var string $pageTitle
 * @var string $action_url
 */
$old = $old ?? [];
$errors = $errors ?? [];
$statuses = $statuses ?? [];
$statusesByProject = $statusesByProject ?? [];
$task = $task ?? null;
$isEditingTask = is_array($task) && !empty($task['id']);
$canDeleteTask = $canDeleteTask ?? false;
$taskId = $isEditingTask ? (int) ($task['id'] ?? 0) : 0;
$taskTitle = (string) ($old['title'] ?? ($task['title'] ?? ''));
$showTaskDeleteAction = $isEditingTask && $taskId > 0 && $canDeleteTask;
$taskDeleteUrl = URLROOT . '/tasks/' . $taskId . '/delete';
$taskDeleteMessage = 'Bạn có chắc chắn muốn xóa công việc ' . ($taskTitle !== '' ? $taskTitle : 'này') . '?';

$selectedProjectId = (string) ($old['project_id'] ?? ($_GET['project_id'] ?? ''));
$selectedStatusId = (string) ($old['status_id'] ?? '');
$selectedProject = null;
foreach ($projects as $projectOption) {
    if ($selectedProjectId !== '' && (string) ($projectOption['id'] ?? '') === $selectedProjectId) {
        $selectedProject = $projectOption;
        break;
    }
}

$projectSwitcherTitle = !empty($selectedProject['name']) ? (string) $selectedProject['name'] : 'Chọn dự án';
$normalizeSearchText = static function (string $text): string {
    return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
};
?>

<!-- Thêm CSS của Quill Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    /* Giao diện 100% chiều ngang */
    .form-main-container {
        max-width: 100%;
        margin: 0;
        width: 100%;
    }

    /* Làm cho editor lấp đầy khoảng trống để cân bằng với thẻ bên phải */
    .editor-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .editor-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 400px; /* Chiều cao tối thiểu đảm bảo giao diện đẹp */
    }

    .task-create-context-row {
        min-height: 52px;
    }

    .task-create-context-row .project-switcher-item {
        border: 0;
        background: transparent;
        text-align: left;
    }

    .task-create-context-error {
        padding-left: 0.5rem;
    }

    .task-create-context-row .project-switcher-trigger:disabled {
        cursor: not-allowed;
        opacity: 1;
    }

    .task-create-context-row .project-switcher-trigger:disabled .project-switcher-chevron {
        color: var(--slate-300);
    }

    .task-create-layout {
        align-items: stretch;
    }

    .task-create-layout > [class*="col-"] {
        display: flex;
    }

    .task-create-card {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        min-height: 100%;
        margin-bottom: 0;
        padding: 1.5rem;
        border: 0;
        border-radius: var(--radius-lg);
        background: var(--md-content-surface, #ffffff);
        box-shadow: none;
    }

    .task-create-main-card .form-body-stack,
    .task-create-main-card .editor-wrapper {
        flex: 1;
    }

    .task-create-side-card .form-group-stack:last-child {
        margin-bottom: 0;
    }

    .ql-toolbar.ql-snow {
        border-top-left-radius: var(--radius-lg);
        border-top-right-radius: var(--radius-lg);
        background: var(--slate-50);
        border-color: var(--slate-200);
    }

    .ql-container.ql-snow {
        flex: 1;
        border-bottom-left-radius: var(--radius-lg);
        border-bottom-right-radius: var(--radius-lg);
        border-color: var(--slate-200);
        font-family: inherit;
        font-size: 0.95rem;
    }

    @media (max-width: 991.98px) {
        .task-create-layout > [class*="col-"] {
            display: block;
        }

        .task-create-card {
            min-height: auto;
        }

        .editor-container {
            min-height: 320px;
        }
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/tasks" class="text-decoration-none text-slate-500 hover-text-primary">Công việc</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title"><?= htmlspecialchars($pageTitle) ?></span>
    </div>
</div>

<div class="container-fluid p-0">
    <form action="<?= $action_url ?>" method="POST" id="taskForm" class="form-main-container m-0 pb-5">
        <?php \App\helpers\SecurityHelper::csrfInput(); ?>
        <input type="hidden" name="project_id" id="task_project_id" value="<?= htmlspecialchars($selectedProjectId, ENT_QUOTES, 'UTF-8') ?>">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 px-1 task-create-context-row">
            <div>
                <div class="project-context d-flex align-items-center gap-3 min-vw-0" id="taskProjectContext">
                    <div class="dropdown tasks-project-dropdown project-switcher" data-project-switcher>
                        <button class="btn btn-link project-switcher-trigger text-decoration-none shadow-none border-0" type="button" <?= $isEditingTask ? 'disabled aria-disabled="true" title="Không thể đổi dự án khi chỉnh sửa công việc"' : 'data-bs-toggle="dropdown" data-bs-offset="0,8" aria-expanded="false"' ?>>
                            <span class="project-switcher-icon">
                                <i data-lucide="folder-kanban"></i>
                            </span>
                            <span class="project-switcher-text">
                                <span class="project-switcher-eyebrow">Dự án</span>
                                <span class="project-switcher-title" data-task-project-title><?= htmlspecialchars($projectSwitcherTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                            <i data-lucide="<?= $isEditingTask ? 'lock' : 'chevron-down' ?>" class="project-switcher-chevron"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-start shadow-xl border-0">
                            <li class="project-switcher-search px-3 py-2">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-slate-400">
                                        <i data-lucide="search" size="16"></i>
                                    </span>
                                    <input type="search" class="form-control border-start-0" placeholder="Tìm dự án..." data-project-switcher-search>
                                </div>
                            </li>

                            <li class="px-0 py-0">
                                <div class="project-dropdown-scroll">
                                    <?php foreach ($projects as $project): ?>
                                        <?php
                                        $projectId = (string) ($project['id'] ?? '');
                                        $isCurrentProject = $selectedProjectId !== '' && $selectedProjectId === $projectId;
                                        $projectName = (string) ($project['name'] ?? 'Dự án');
                                        $projectCode = (string) ($project['project_code'] ?? '');
                                        $statusName = (string) ($project['status_name'] ?? '');
                                        $statusColor = (string) ($project['status_color'] ?? '#64748b');
                                        $searchText = $normalizeSearchText(trim($projectName . ' ' . $projectCode . ' ' . $statusName));
                                        ?>
                                        <button type="button"
                                            class="dropdown-item project-switcher-item <?= $isCurrentProject ? 'active' : '' ?>"
                                            data-project-switcher-item
                                            data-task-project-option
                                            data-project-id="<?= htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8') ?>"
                                            data-project-name="<?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-project-code="<?= htmlspecialchars($projectCode, ENT_QUOTES, 'UTF-8') ?>"
                                            data-project-status-name="<?= htmlspecialchars($statusName, ENT_QUOTES, 'UTF-8') ?>"
                                            data-project-status-color="<?= htmlspecialchars($statusColor, ENT_QUOTES, 'UTF-8') ?>"
                                            data-project-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="project-switcher-item-icon">
                                                <i data-lucide="folder"></i>
                                            </span>
                                            <span class="project-switcher-item-main">
                                                <span class="project-switcher-item-title"><?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="project-switcher-item-meta">
                                                    <?= htmlspecialchars($projectCode !== '' ? $projectCode : 'Chưa có mã', ENT_QUOTES, 'UTF-8') ?>
                                                    <?php if ($statusName !== ''): ?>
                                                        <span class="project-switcher-dot">&middot;</span>
                                                        <?= htmlspecialchars($statusName, ENT_QUOTES, 'UTF-8') ?>
                                                    <?php endif; ?>
                                                </span>
                                            </span>
                                            <i data-lucide="check" class="project-switcher-check <?= $isCurrentProject ? '' : 'd-none' ?>"></i>
                                        </button>
                                    <?php endforeach; ?>

                                    <div class="project-switcher-empty d-none" data-project-switcher-empty>
                                        Không tìm thấy dự án phù hợp.
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div class="project-context-meta d-flex align-items-center gap-2 border-start border-slate-200 <?= $selectedProject ? '' : 'd-none' ?>" data-task-project-meta>
                        <span class="text-slate-500 small fw-medium <?= !empty($selectedProject['project_code']) ? '' : 'd-none' ?>" data-task-project-code><?= htmlspecialchars((string) ($selectedProject['project_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="status-pill py-0 px-2 <?= !empty($selectedProject['status_name']) ? '' : 'd-none' ?>" style="font-size: 11px; background-color: <?= htmlspecialchars((string) ($selectedProject['status_color'] ?? '#64748b'), ENT_QUOTES, 'UTF-8') ?>20; color: <?= htmlspecialchars((string) ($selectedProject['status_color'] ?? '#64748b'), ENT_QUOTES, 'UTF-8') ?>;" data-task-project-status>
                            <?= htmlspecialchars((string) ($selectedProject['status_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

                <div class="text-danger small mt-1 task-create-context-error <?= isset($errors['project_id']) ? '' : 'd-none' ?>" data-task-project-error>
                    <?= htmlspecialchars((string) ($errors['project_id'] ?? 'Vui lòng chọn dự án.'), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?= URLROOT ?>/tasks" class="btn btn-outline-secondary">
                    <i data-lucide="arrow-left" size="18"></i>
                    <span>Trở về</span>
                </a>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('taskForm').reset();">
                    <i data-lucide="refresh-ccw" size="18"></i>
                    <span>Làm mới</span>
                </button>
                <?php if ($showTaskDeleteAction): ?>
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    onclick="showDeleteModal('<?= htmlspecialchars($taskDeleteUrl, ENT_QUOTES, 'UTF-8') ?>', <?= htmlspecialchars((string) json_encode($taskDeleteMessage, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                    <i data-lucide="trash-2" size="18"></i>
                    <span>Xóa</span>
                </button>
                <?php endif; ?>
                <button type="submit" form="taskForm" class="btn btn-primary shadow-sm">
                    <i data-lucide="check-circle" size="18"></i>
                    <span>Lưu lại</span>
                </button>
            </div>
        </div>

        <div class="row g-4 task-create-layout">
            <!-- Cột trái: Nội dung chính -->
            <div class="col-lg-8">
                <div class="card task-create-card task-create-main-card">
                    <div class="form-body-stack h-100">
                        <div class="form-group-stack mb-0">
                            <label class="form-label fw-bold">Tiêu đề công việc <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg" placeholder="Nhập tên công việc..." value="<?= $old['title'] ?? '' ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['title'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group-stack editor-wrapper">
                            <label class="form-label fw-bold">Mô tả chi tiết</label>
                            <div id="description-editor" class="editor-container">
                                <?= htmlspecialchars_decode($old['description'] ?? '') ?>
                            </div>
                            <input type="hidden" name="description" id="description-input">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Thông tin bổ sung & Hành động -->
            <div class="col-lg-4">
                <div class="card task-create-card task-create-side-card">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <i data-lucide="sliders-horizontal" size="18" class="text-slate-500"></i>
                        <h6 class="fw-bold text-slate-800 mb-0">Thuộc tính công việc</h6>
                    </div>

                    <div class="form-group-stack mb-4">
                        <label class="form-label">Người thực hiện</label>
                        <select name="assigned_to" class="form-select select2">
                            <option value="">-- Chưa giao --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= (isset($old['assigned_to']) && (string) $old['assigned_to'] === (string) $user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['employee_code'] ?? 'N/A') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="form-group-stack">
                                <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select name="status_id" id="task_status_id" class="form-select" required>
                                    <?php if (empty($statuses)): ?>
                                        <option value="">-- Chọn dự án trước --</option>
                                    <?php else: ?>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status['id'] ?>" <?= ($selectedStatusId !== '' && $selectedStatusId === (string) $status['id']) ? 'selected' : '' ?>><?= htmlspecialchars($status['name']) ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (isset($errors['status_id'])): ?>
                                    <div class="text-danger small mt-1"><?= $errors['status_id'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group-stack">
                                <label class="form-label">Ưu tiên</label>
                                <select name="priority" class="form-select">
                                    <option value="low" <?= (($old['priority'] ?? 'medium') === 'low') ? 'selected' : '' ?>>Thấp</option>
                                    <option value="medium" <?= (($old['priority'] ?? 'medium') === 'medium') ? 'selected' : '' ?>>Trung bình</option>
                                    <option value="high" <?= (($old['priority'] ?? 'medium') === 'high') ? 'selected' : '' ?>>Cao</option>
                                    <option value="urgent" <?= (($old['priority'] ?? 'medium') === 'urgent') ? 'selected' : '' ?>>Khẩn cấp</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-stack mb-4">
                        <label class="form-label">Thời gian bắt đầu</label>
                        <div class="input-group">
                            <span class="input-group-text"><i data-lucide="calendar" size="16"></i></span>
                            <input type="date" name="start_date" class="form-control" value="<?= $old['start_date'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="form-group-stack mb-4">
                        <label class="form-label">Thời gian kết thúc</label>
                        <div class="input-group">
                            <span class="input-group-text"><i data-lucide="calendar" size="16"></i></span>
                            <input type="date" name="due_date" class="form-control" value="<?= $old['due_date'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="form-group-stack">
                        <label class="form-label">Ước tính (giờ)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i data-lucide="clock" size="16"></i></span>
                            <input type="number" name="estimated_hours" class="form-control" placeholder="0" min="0" step="0.5" value="<?= $old['estimated_hours'] ?? '' ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php if ($showTaskDeleteAction): ?>
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
<?php endif; ?>

<!-- Scripts cho Editor -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusesByProject = <?= json_encode($statusesByProject, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const initialProjectId = <?= json_encode($selectedProjectId) ?>;
        const initialStatusId = <?= json_encode($selectedStatusId) ?>;
        const isEditingTask = <?= json_encode($isEditingTask) ?>;
        const projectSelect = document.getElementById('task_project_id');
        const statusSelect = document.getElementById('task_status_id');
        const projectTitle = document.querySelector('[data-task-project-title]');
        const projectMeta = document.querySelector('[data-task-project-meta]');
        const projectCode = document.querySelector('[data-task-project-code]');
        const projectStatus = document.querySelector('[data-task-project-status]');
        const projectError = document.querySelector('[data-task-project-error]');
        const projectOptions = Array.from(document.querySelectorAll('[data-task-project-option]'));

        function renderStatuses(projectId, preferredStatusId = '') {
            if (!statusSelect) return;

            const statuses = statusesByProject[String(projectId)] || [];
            statusSelect.innerHTML = '';

            if (!projectId) {
                statusSelect.appendChild(new Option('-- Chọn dự án trước --', ''));
                statusSelect.disabled = true;
                return;
            }

            if (statuses.length === 0) {
                statusSelect.appendChild(new Option('-- Dự án chưa có trạng thái --', ''));
                statusSelect.disabled = true;
                return;
            }

            statuses.forEach(function(status, index) {
                const option = new Option(status.name || 'Chưa đặt tên', status.id);
                if ((preferredStatusId && String(status.id) === String(preferredStatusId)) || (!preferredStatusId && index === 0)) {
                    option.selected = true;
                }
                statusSelect.appendChild(option);
            });
            statusSelect.disabled = false;
        }

        function updateProjectSwitcher(option) {
            projectOptions.forEach(function(item) {
                const isActive = option && item === option;
                item.classList.toggle('active', isActive);

                const checkIcon = item.querySelector('.project-switcher-check');
                if (checkIcon) {
                    checkIcon.classList.toggle('d-none', !isActive);
                }
            });

            if (!projectTitle || !projectMeta) return;

            if (!option) {
                projectTitle.textContent = 'Chọn dự án';
                projectMeta.classList.add('d-none');

                if (projectCode) {
                    projectCode.textContent = '';
                    projectCode.classList.add('d-none');
                }

                if (projectStatus) {
                    projectStatus.textContent = '';
                    projectStatus.classList.add('d-none');
                }
                return;
            }

            projectTitle.textContent = option.dataset.projectName || 'Dự án';
            projectMeta.classList.remove('d-none');

            if (projectCode) {
                projectCode.textContent = option.dataset.projectCode || '';
                projectCode.classList.toggle('d-none', !option.dataset.projectCode);
            }

            if (projectStatus) {
                projectStatus.textContent = option.dataset.projectStatusName || '';
                projectStatus.classList.toggle('d-none', !option.dataset.projectStatusName);
                const statusColor = option.dataset.projectStatusColor || '#64748b';
                projectStatus.style.backgroundColor = statusColor + '20';
                projectStatus.style.color = statusColor;
            }
        }

        if (projectSelect && statusSelect) {
            renderStatuses(projectSelect.value || initialProjectId, initialStatusId);
        }

        if (!isEditingTask) {
            projectOptions.forEach(function(option) {
                option.addEventListener('click', function() {
                    const projectId = this.dataset.projectId || '';
                    if (projectSelect) {
                        projectSelect.value = projectId;
                    }

                    if (projectError) {
                        projectError.classList.add('d-none');
                    }

                    updateProjectSwitcher(this);
                    renderStatuses(projectId);

                    const dropdown = this.closest('.dropdown');
                    if (dropdown && window.bootstrap && window.bootstrap.Dropdown) {
                        const trigger = dropdown.querySelector('[data-bs-toggle="dropdown"]');
                        window.bootstrap.Dropdown.getOrCreateInstance(trigger).hide();
                    }
                });
            });
        }

        // Cấu hình Toolbar cho Quill
        var toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'], // in đậm, nghiêng...
            ['blockquote', 'code-block'],
            [{
                'header': 1
            }, {
                'header': 2
            }], // tiêu đề
            [{
                'list': 'ordered'
            }, {
                'list': 'bullet'
            }],
            [{
                'color': []
            }, {
                'background': []
            }], // màu chữ
            ['link', 'image'], // chèn link và ảnh
            ['clean'] // xóa định dạng
        ];

        var quill = new Quill('#description-editor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow',
            placeholder: 'Viết mô tả công việc tại đây...'
        });

        // Xử lý đồng bộ dữ liệu Quill trước khi submit form
        const form = document.getElementById('taskForm');
        if (form) {
            form.addEventListener('reset', function() {
                window.setTimeout(function() {
                    const initialOption = projectOptions.find(function(option) {
                        return String(option.dataset.projectId || '') === String(initialProjectId || '');
                    });

                    if (projectSelect) {
                        projectSelect.value = initialProjectId || '';
                    }

                    updateProjectSwitcher(initialOption || null);
                    renderStatuses(initialProjectId || '', initialStatusId);
                }, 0);
            });

            form.addEventListener('submit', function(e) {
                if (projectSelect && !projectSelect.value) {
                    e.preventDefault();

                    if (projectError) {
                        projectError.textContent = 'Vui lòng chọn dự án.';
                        projectError.classList.remove('d-none');
                    }

                    const projectTrigger = document.querySelector('#taskProjectContext [data-bs-toggle="dropdown"]');
                    if (projectTrigger) {
                        projectTrigger.focus();
                    }
                    return;
                }

                // Lấy nội dung HTML từ Quill và gán vào hidden input
                const descriptionInput = document.getElementById('description-input');
                
                if (descriptionInput && typeof quill !== 'undefined') {
                    descriptionInput.value = quill.root.innerHTML;

                    // Nếu editor trống (chỉ chứa các thẻ HTML mặc định) thì trả về rỗng để validator xử lý
                    if (quill.getText().trim().length === 0 && quill.root.innerHTML.indexOf('<img') === -1) {
                        descriptionInput.value = '';
                    }
                }
            });
        }
    });
</script>

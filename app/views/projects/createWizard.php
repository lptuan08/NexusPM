<?php
/**
 * Wizard tạo dự án — dữ liệu select/mẫu task/member lấy qua API; form POST giữ old/errors.
 *
 * @var array|null $old
 * @var array|null $errors
 * @var string $action_url
 */

$old = $old ?? [];
$errors = $errors ?? [];
$action_url = $action_url ?? '';

$currentName = $old['name'] ?? '';
$currentDescription = $old['description'] ?? '';
$currentStartDate = $old['start_date'] ?? '';
$currentDueDate = $old['due_date'] ?? '';

$oldWizardStatusesJson = $old['wizard_task_statuses'] ?? '';
$oldWizardMembersJson = $old['wizard_members'] ?? '';
$serverInitialStep = 1;
if (!empty($errors['wizard_members'])) {
    $serverInitialStep = 3;
} elseif (!empty($errors['wizard_task_statuses'])) {
    $serverInitialStep = 2;
}

$serverOldBasicJson = json_encode(
    [
        'owner_id' => (string) ($old['owner_id'] ?? ''),
        'status_id' => (string) ($old['status_id'] ?? ''),
    ],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>

<style>
    /* Toàn chiều ngang vùng nội dung (không giới hạn 800px của .form-main-container) */
    .form-main-container.project-create-fullwidth {
        max-width: 1400px;
        width: 100%;
        margin: 0 auto; /* Đã loại bỏ margin-top/bottom để nhất quán với bố cục chung */
        
    }

    .project-create-card {
        height: calc(100vh - 164px); /* Điều chỉnh chiều cao sau khi loại bỏ margin-top của container */
        display: flex;
        flex-direction: column;
        border-radius: 1.25rem; /* Bo góc thẻ */
        overflow: hidden; /* Đảm bảo nội dung không tràn khỏi góc bo */
        background: var(--md-content-surface);
        border: 0;
        box-shadow: none;
    }

    .project-create-card .ui-card-header {
        flex-shrink: 0;
        background: var(--md-content-surface);
        padding: 1.25rem 2rem 0; /* Thêm padding đồng bộ với nội dung */
    }

    .wizard-stepper {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.25rem;
        margin-bottom: 0.75rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
        -webkit-overflow-scrolling: touch;
    }

    .wizard-stepper--in-card-header {
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .wizard-stepper-item {
        flex: 1 1 0;
        min-width: 120px;
        text-align: center;
        position: relative;
    }

    .wizard-stepper-item:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 1.1rem;
        left: calc(50% + 1.25rem);
        right: calc(-50% + 1.25rem);
        height: 2px;
        background: var(--slate-200);
        z-index: 0;
    }

    .wizard-stepper-item.is-done:not(:last-child)::after {
        background: var(--primary-600);
    }

    .wizard-step-circle {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
        background: var(--slate-50);
        color: var(--slate-500);
        border: 2px solid var(--slate-200);
        box-sizing: border-box;
        position: relative;
        z-index: 1;
        transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, color 0.18s ease;
    }

    .wizard-stepper-item.is-active .wizard-step-circle {
        background: var(--primary-50);
        color: var(--primary-700);
        border-color: var(--primary-600);
        box-shadow: inset 0 0 0 2px rgba(59, 130, 246, 0.18);
        z-index: 2;
    }

    .wizard-stepper-item.is-done .wizard-step-circle {
        background: var(--primary-600);
        color: #fff;
        border-color: var(--primary-600);
    }

    .wizard-step-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--slate-600);
        margin-top: 0.35rem;
        line-height: 1.25;
    }

    .wizard-stepper-item.is-active .wizard-step-label {
        color: var(--primary-700);
        font-weight: 700;
    }

    @media (min-width: 576px) {
        .wizard-step-label {
            font-size: 0.8125rem;
        }
    }

    .project-create-card .ui-card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 1.25rem 0; /* Xóa padding ngang để thanh cuộn sát mép */
    }

    #projectWizardForm {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .wizard-panel {
        display: none;
        flex-direction: column;
        height: 100%;
    }

    .wizard-panel.is-active {
        display: flex;
    }

    .wizard-panel-content {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden; /* Chống cuộn ngang */
        padding: 0 2rem; /* Bù lại padding ngang ở đây */
        scrollbar-gutter: stable;
    }

    .project-form-textarea {
        min-height: auto; 
        margin-bottom: 15px;
    }

    .border-dashed {
        border-style: dashed !important;
    }

    .color-box {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-flex;
        flex-shrink: 0;
        border: 1px solid rgba(32, 33, 36, 0.1);
        cursor: pointer; /* Để gợi ý rằng có thể tương tác */
        margin-right: 0.5rem; /* Khoảng cách với text */
    }

    /* —— Bước 2: thẻ trạng thái (card + kéo thả) —— */
    .task-status-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: var(--radius-md);
        background: var(--md-content-surface-strong);
    }

    .task-status-board {
        padding: 0.75rem;
        border-radius: var(--radius-md);
        background: var(--md-content-surface-strong);
    }

    .task-status-board-header,
    .task-status-row {
        display: grid;
        grid-template-columns: 2.25rem 3rem minmax(220px, 1fr) 10rem 7rem 10rem 2.5rem;
        align-items: center;
        gap: 0.75rem;
    }

    .task-status-board-header {
        padding: 0 0.75rem 0.5rem;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--slate-400);
    }

    .task-status-cards {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .task-status-card {
        border: 0;
        border-radius: var(--radius-md, 0.5rem);
        background: #fff;
        padding: 0.65rem 0.75rem;
        box-shadow: none;
        transition:
            background-color 0.2s ease,
            opacity 0.2s ease;
    }

    .task-status-card:hover {
        background: var(--slate-50);
    }

    .task-status-card .form-label {
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--slate-700);
        margin-bottom: 0.35rem;
    }

    .task-status-card__index {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        height: 1.75rem;
        padding: 0 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: var(--slate-600);
        background: var(--md-content-surface-strong);
        border: 0;
        border-radius: 999px;
    }

    .task-status-card .btn-remove-ts {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.375rem;
        color: var(--slate-400) !important;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    .task-status-card .btn-remove-ts:hover {
        color: #dc2626 !important;
        background: rgba(220, 38, 38, 0.06);
    }

    .task-status-card .ts-color-trigger {
        min-height: calc(1.5em + 0.75rem + 2px);
        border: 1px solid var(--slate-200);
        border-color: var(--slate-200) !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .task-status-card .ts-color-trigger:hover {
        border-color: var(--primary-300) !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }

    .task-status-card .ts-color-trigger .color-box {
        margin-right: 0;
    }

    .task-status-name-field,
    .task-status-slug-field {
        min-width: 0;
    }

    .task-status-toggle-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        justify-items: center;
        gap: 0.5rem;
    }

    .task-status-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin: 0;
    }

    .task-status-toggle .form-check-input {
        margin: 0;
    }

    .task-status-remove {
        justify-self: center;
    }

    .task-status-add-btn {
        border: 0;
        border-radius: var(--radius-md);
        background: #fff;
        color: var(--primary-600);
    }

    .task-status-add-btn:hover {
        background: var(--slate-50);
        color: var(--primary-700);
    }

    .task-status-card.is-dragging {
        opacity: 1;
        transform: scale(1.02);
        background: #fff;
        box-shadow: none;
        z-index: 6;
    }

    .task-status-card.is-drag-over {
        background: var(--md-primary-container);
        box-shadow: none;
        transform: translateY(-3px);
    }

    .status-drag-handle {
        cursor: grab;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        color: var(--slate-400);
        user-select: none;
        -webkit-user-select: none;
        touch-action: none;
        transition: background-color 0.15s ease, color 0.15s ease;
    }

    .status-drag-handle:active {
        cursor: grabbing;
    }

    .status-drag-handle:hover {
        background: var(--slate-200);
        color: var(--slate-700);
    }

    .status-drag-handle:focus-visible {
        outline: 2px solid var(--primary-500);
        outline-offset: 2px;
    }

    .status-drag-handle:active {
        cursor: grabbing;
    }

    .member-transfer-layout {
        display: flex;
        flex: 1;
        min-height: 0;
    }

    .member-transfer-pane {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        padding: 1rem;
        border-radius: var(--radius-md);
        background: var(--md-content-surface-strong);
    }

    .member-transfer-header {
        flex-shrink: 0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.75rem;
    }

    .member-transfer-list {
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .member-transfer-search {
        flex-shrink: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        margin-bottom: 0.75rem;
        border-radius: var(--radius-md);
        color: var(--slate-400);
        background: #fff;
    }

    .member-transfer-search .form-control {
        border: 0;
        box-shadow: none;
        padding-left: 0;
    }

    .member-transfer-list {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding-right: 0.125rem;
    }

    .member-transfer-row {
        border: 0;
        border-radius: var(--radius-md);
        background: #fff;
        padding: 0.75rem;
        display: grid;
        grid-template-columns: 2.25rem minmax(0, 1fr) 9rem;
        align-items: center;
        gap: 0.75rem;
        transition: background-color 0.16s ease;
    }

    .member-transfer-row.is-selected {
        background: var(--primary-50);
    }

    .member-transfer-check {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .member-transfer-info {
        min-width: 0;
    }

    .member-transfer-name {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
        font-weight: 700;
        color: var(--slate-800);
    }

    .member-transfer-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 0.75rem;
        margin-top: 0.15rem;
        font-size: 0.75rem;
        color: var(--slate-500);
    }

    .member-selected-count {
        min-width: 2rem;
        height: 2rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--primary-700);
        background: var(--primary-50);
    }

    .selected-empty {
        padding: 0.75rem;
        border-radius: var(--radius-md);
        color: var(--slate-500);
        background: rgba(255, 255, 255, 0.64);
        font-size: 0.8125rem;
    }

    @media (max-width: 991.98px) {
        .task-status-toolbar {
            align-items: stretch;
            flex-direction: column;
        }

        .task-status-board {
            padding: 0.625rem;
        }

        .task-status-row {
            grid-template-columns: 2.25rem 2.75rem minmax(0, 1fr) 2.5rem;
            grid-template-areas:
                "drag index name remove"
                ". . color color"
                ". . toggles toggles";
            gap: 0.625rem;
        }

        .task-status-row__drag {
            grid-area: drag;
        }

        .task-status-card__index {
            grid-area: index;
        }

        .task-status-name-field {
            grid-area: name;
            min-width: 0;
        }

        .task-status-color-field {
            grid-area: color;
            width: 100% !important;
        }

        .task-status-toggle-group {
            grid-area: toggles;
            justify-items: start;
        }

        .task-status-remove {
            grid-area: remove;
        }

        .member-transfer-row {
            grid-template-columns: 2.25rem minmax(0, 1fr);
        }

        .member-transfer-check {
            justify-content: flex-start;
        }

        .member-source-role {
            grid-column: 1 / -1;
            width: 100%;
        }
    }

    .wizard-panel-footer {
        flex-shrink: 0;
        margin-top: auto;
        padding: 1rem 2rem 0; /* Padding ngang khớp với nội dung */
        border-top: 0;
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Thêm mới</span>
    </div>

    <div class="page-actions">
        <a href="<?= URLROOT ?>/projects" class="btn btn-outline-secondary px-3">
            <i data-lucide="arrow-left"></i>
            <span>Trở về</span>
        </a>
    </div>
</div>

<div class="form-main-container project-create-fullwidth">
    <div class="ui-card project-create-card">
        <div class="ui-card-header">
            <nav class="wizard-stepper wizard-stepper--in-card-header" aria-label="Tiến trình tạo dự án">
                <div class="wizard-stepper-item is-active" data-step-indicator="1">
                    <div class="wizard-step-circle">1</div>
                    <div class="wizard-step-label">Thông tin cơ bản</div>
                </div>
                <div class="wizard-stepper-item" data-step-indicator="2">
                    <div class="wizard-step-circle">2</div>
                    <div class="wizard-step-label">Trạng thái công việc</div>
                </div>
                <div class="wizard-stepper-item" data-step-indicator="3">
                    <div class="wizard-step-circle">3</div>
                    <div class="wizard-step-label">Thành viên</div>
                </div>
            </nav>
        </div>

        <div class="ui-card-body">
            <?php if (!empty($errors['wizard_task_statuses'])): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errors['wizard_task_statuses'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($errors['wizard_members'])): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($errors['wizard_members'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form id="projectWizardForm" action="<?= htmlspecialchars($action_url, ENT_QUOTES, 'UTF-8') ?>" method="POST" autocomplete="off">
                <?php App\helpers\SecurityHelper::csrfInput(); ?>

                <input type="hidden" name="wizard_task_statuses" id="wizard_task_statuses" value="<?= htmlspecialchars((string) $oldWizardStatusesJson, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="wizard_members" id="wizard_members" value="<?= htmlspecialchars((string) $oldWizardMembersJson, ENT_QUOTES, 'UTF-8') ?>">

                <!-- Bước 1 -->
                <div class="wizard-panel is-active" data-step="1">
                    <div class="wizard-panel-content">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label class="form-label">Tên dự án <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="fld_name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" placeholder="Nhập tên dự án" value="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Project Sponsor <span class="text-danger">*</span></label>
                                    <select name="owner_id" id="fld_owner_id" class="form-select <?= isset($errors['owner_id']) ? 'is-invalid' : '' ?>">
                                        <option value="">Chọn người bảo trợ dự án</option>
                                    </select>
                                    <?php if (isset($errors['owner_id'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['owner_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Trạng thái dự án <span class="text-danger">*</span></label>
                                    <select name="status_id" id="fld_status_id" class="form-select <?= isset($errors['status_id']) ? 'is-invalid' : '' ?>">
                                        <option value="">Chọn trạng thái</option>
                                    </select>
                                    <?php if (isset($errors['status_id'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['status_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Thời gian bắt đầu</label>
                                        <input type="date" name="start_date" id="fld_start_date" class="form-control" value="<?= htmlspecialchars($currentStartDate, ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Thời gian kết thúc</label>
                                        <input type="date" name="due_date" id="fld_due_date" class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($currentDueDate, ENT_QUOTES, 'UTF-8') ?>">
                                        <?php if (isset($errors['due_date'])): ?>
                                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['due_date'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6 d-flex flex-column">
                                <label class="form-label">Mô tả dự án</label>
                                <textarea name="description" id="fld_description" class="form-control project-form-textarea flex-grow-1" placeholder="Mô tả ngắn gọn mục tiêu và phạm vi"><?= htmlspecialchars($currentDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 wizard-panel-footer">
                        <button type="button" class="btn btn-primary px-4" data-wizard-next>
                            Tiếp theo
                            <i data-lucide="chevron-right" class="ms-1" size="18"></i>
                        </button>
                    </div>
                </div>

                <!-- Bước 2 -->
                <div class="wizard-panel" data-step="2">
                    <div class="wizard-panel-content">
                        <div class="task-status-toolbar mb-3">
                            <p class="text-slate-600 small mb-0">Để trống khi lưu sẽ dùng mẫu hệ thống. Kéo thanh bên trái mỗi thẻ để sắp xếp thứ tự.</p>
                            <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" id="btnLoadGlobalStatuses">
                                <i data-lucide="copy" size="16"></i>
                                <span class="ms-1">Tải mẫu hệ thống</span>
                            </button>
                        </div>

                        <div class="task-status-board mb-4">
                            <div class="task-status-board-header d-none d-lg-grid" aria-hidden="true">
                                <div></div>
                                <div class="text-center">Thứ tự</div>
                                <div>Tên hiển thị</div>
                                <div>Mã định danh</div>
                                <div class="text-center">Màu sắc</div>
                                <div class="text-center">Luồng</div>
                                <div></div>
                            </div>

                            <div id="taskStatusList" class="task-status-cards mb-3" role="list" aria-label="Trạng thái công việc">
                            </div>

                            <button type="button" class="btn task-status-add-btn w-100 py-2" id="btnAddTaskStatusRow">
                                <i data-lucide="plus" size="18"></i>
                                <span class="fw-bold">Thêm trạng thái</span>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 wizard-panel-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-wizard-prev>
                            <i data-lucide="chevron-left" class="me-1" size="18"></i>
                            Quay lại
                        </button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary px-4" data-wizard-next>
                                Tiếp theo
                                <i data-lucide="chevron-right" class="ms-1" size="18"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Bước 3 -->
                <div class="wizard-panel" data-step="3">
                    <div class="wizard-panel-content d-flex flex-column">
                        <div class="member-transfer-layout">
                            <section class="member-transfer-pane">
                                <div class="member-transfer-header">
                                    <div>
                                        <div class="fw-semibold text-slate-800">Danh sách nhân viên</div>
                                        <div class="text-slate-500 small">Tick nhân viên tham gia dự án và chọn role tương ứng.</div>
                                    </div>
                                    <span class="member-selected-count" id="selectedMemberCount">0</span>
                                </div>
                                <div class="member-transfer-search">
                                    <i data-lucide="search" size="16"></i>
                                    <input type="search" id="memberSearchInput" class="form-control form-control-sm" placeholder="Tìm theo tên, mã, email hoặc chức danh">
                                </div>
                                <div id="memberSourceList" class="member-transfer-list" aria-label="Danh sách tất cả nhân viên"></div>
                            </section>
                        </div>

                        <div id="wizard_members_error" class="text-danger small d-none">
                            Vui lòng chọn ít nhất một Project Manager.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 wizard-panel-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-wizard-prev>
                            <i data-lucide="chevron-left" class="me-1" size="18"></i>
                            Quay lại
                        </button>
                        <button type="submit" class="btn btn-primary px-4" id="wizardSubmitBtn">
                            <i data-lucide="save"></i>
                            Hoàn tất &amp; lưu dự án
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Logic wizard tạo dự án (phía trình duyệt):
 * - Gọi API `/api/projects/wizard-data` để nạp select + mẫu task + danh sách user.
 * - Trước submit: ghi JSON vào hidden để PHP xử lý.
 * IIFE: tránh biến toàn cục trùng tên với các script khác.
 */
(function () {
    /** Giá trị owner/status từ server khi form POST lỗi (PHP render lại). */
    var SERVER_OLD_BASIC = <?= $serverOldBasicJson !== false ? $serverOldBasicJson : '{}' ?>;
    var SERVER_INITIAL_STEP = <?= (int) $serverInitialStep ?>;
    /** Bản sao mẫu task status toàn hệ thống (API) — dùng khi seed bảng hoặc bấm "Tải mẫu hệ thống". */
    var globalTemplate = [];
    /** Danh sách vai trò thành viên dự án (slug + label) — API trả về, dùng ở bước 3. */
    var memberRoles = [];

    /** Tạo slug an toàn từ tên (bỏ dấu, chữ thường, gạch ngang). */
    function slugify(str) {
        if (!str) return '';
        var s = str.normalize ? str.normalize('NFD') : str;
        s = s.replace(/[\u0300-\u036f]/g, '');
        s = s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        return s || 'status';
    }

    /** Mảng trạng thái công việc (bước 2) — nguồn sự thật trước khi sync vào hidden. */
    var taskRows = [];

    /** Di chuyển phần tử trong mảng (from / to là chỉ số trước khi kéo, theo thứ tự hiển thị). */
    function moveTaskRowInArray(arr, from, to) {
        if (from === to || from < 0 || to < 0 || from >= arr.length || to >= arr.length) return;
        var item = arr.splice(from, 1)[0];
        arr.splice(to, 0, item);
    }

    /** Vẽ lại danh sách thẻ trạng thái từ taskRows; gắn listener và giữ focus khi đổi radio. */
    function renderTaskRows() {
        var container = document.getElementById('taskStatusList');
        container.innerHTML = '';
        taskRows.forEach(function (row, idx) {
            var rowDiv = document.createElement('div');
            rowDiv.className = 'task-status-card status-picker-row';
            rowDiv.setAttribute('role', 'listitem');
            rowDiv.innerHTML =
                '<div class="task-status-row">' +
                    '<span class="status-drag-handle task-status-row__drag" role="button" tabindex="0">' +
                        '<i data-lucide="more-vertical" size="18"></i>' +
                    '</span>' +
                    '<span class="task-status-card__index ts-position">#' + (idx + 1) + '</span>' +
                    
                    '<div class="task-status-name-field">' +
                        '<input type="text" class="form-control form-control-sm ts-name' + (row.name === '' && taskRows.length > 0 ? '' : '') + '" placeholder="Tên trạng thái" value="' + escapeAttr(row.name) + '">' +
                    '</div>' +
                    
                    '<div class="task-status-slug-field d-none d-lg-block">' +
                        '<input type="text" class="form-control form-control-sm ts-slug font-monospace" placeholder="slug" value="' + escapeAttr(row.slug) + '">' +
                    '</div>' +
                    
                    '<div class="ts-color-trigger task-status-color-field d-flex align-items-center gap-2 px-2 rounded bg-white cursor-pointer" style="height: 33px;">' +
                        '<span class="color-box ts-color-display mb-0 flex-shrink-0" style="background-color: ' + escapeAttr(row.color) + '; width: 14px; height: 14px; margin-right: 0;"></span>' +
                        '<code class="small text-slate-500 fw-mono ts-color-hex flex-grow-1 text-truncate" style="font-size: 0.7rem;">' + escapeAttr(row.color).toUpperCase() + '</code>' +
                        '<input type="color" class="ts-color" style="visibility: hidden; width: 0; height: 0; position: absolute;" value="' + escapeAttr(row.color) + '">' +
                    '</div>' +
                    
                    '<div class="task-status-toggle-group">' +
                        '<label class="task-status-toggle">' +
                            '<input type="radio" name="ts_default" class="form-check-input ts-def" id="ts_def_' + idx + '"' + (row.is_default ? ' checked' : '') + '>' +
                            '<span class="d-lg-none small text-slate-500">Mặc định</span>' +
                        '</label>' +
                        '<label class="task-status-toggle">' +
                            '<input type="radio" name="ts_done" class="form-check-input ts-done" id="ts_done_' + idx + '"' + (row.is_done ? ' checked' : '') + '>' +
                            '<span class="d-lg-none small text-slate-500">Hoàn tất</span>' +
                        '</label>' +
                    '</div>' +
                    
                    '<button type="button" class="btn btn-link p-0 border-0 btn-remove-ts task-status-remove">' +
                        '<i data-lucide="trash-2" size="18"></i>' +
                    '</button>' +
                '</div>';
            container.appendChild(rowDiv);

            var nameInp = rowDiv.querySelector('.ts-name');
            var slugInp = rowDiv.querySelector('.ts-slug');
            // Tên đổi → slug tự sinh nếu user chưa sửa tay (dataset.touched).
            nameInp.addEventListener('input', function (e) {
                row.name = nameInp.value.trim();
                nameInp.classList.remove('is-invalid');
                if (!row.isSlugTouched) {
                    slugInp.value = slugify(nameInp.value);
                    row.slug = slugInp.value;
                }
            });
            // User sửa slug trực tiếp → đánh dấu touched, không ghi đè từ tên nữa.
            slugInp.addEventListener('input', function (e) {
                row.isSlugTouched = true;
                slugInp.classList.remove('is-invalid');
                row.slug = slugInp.value;
            });
            
            var colorInp = rowDiv.querySelector('.ts-color');
            var colorTrigger = rowDiv.querySelector('.ts-color-trigger');
            var hexText = rowDiv.querySelector('.ts-color-hex');
            var colorDisplay = rowDiv.querySelector('.ts-color-display'); // Lấy thẻ span hiển thị màu

            // Khi nhấp vào vùng hiển thị, kích hoạt input color ẩn
            colorTrigger.addEventListener('click', function(e) {
                if (e.target !== colorInp) colorInp.click();
            });

            colorInp.addEventListener('input', function (e) {
                row.color = e.target.value;
                hexText.textContent = e.target.value.toUpperCase();
                colorDisplay.style.backgroundColor = e.target.value; // Cập nhật màu cho thẻ span
            });
            rowDiv.querySelector('.ts-def').addEventListener('change', function () {
                taskRows.forEach(function (r, i) { r.is_default = i === idx; });
            });
            rowDiv.querySelector('.ts-done').addEventListener('change', function () {
                taskRows.forEach(function (r, i) { r.is_done = i === idx; });
            });
            rowDiv.querySelector('.btn-remove-ts').addEventListener('click', function () {
                taskRows.splice(idx, 1);
                renderTaskRows();
            });
        });
        refreshIcons();
    }

    /** Escape chuỗi gán vào HTML attribute (tránh XSS khi tên/slug có ký tự đặc biệt). */
    function escapeAttr(s) {
        if (s === undefined || s === null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    /** Đổ <select> từ mảng { id, name, email? } (owner / trạng thái dự án). */
    function populateSelect(id, data, defaultText) {
        var el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = '<option value="">' + escapeAttr(defaultText) + '</option>';
        (data || []).forEach(function(item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name + (item.email ? ' - ' + item.email : '');
            el.appendChild(opt);
        });
    }

    var allMemberUsers = [];
    var selectedMembers = {};
    var sourceRoleDrafts = {};
    var memberSearchQuery = '';
    var defaultMemberRoles = [
        { slug: 'manager', name: 'Manager' },
        { slug: 'member', name: 'Member' },
        { slug: 'viewer', name: 'Viewer' }
    ];

    function projectRoleOrder() {
        var known = {};
        var roles = [];
        (memberRoles && memberRoles.length ? memberRoles : defaultMemberRoles).forEach(function (role) {
            var slug = normalizeProjectRole(role.slug);
            if (!known[slug]) {
                roles.push({ slug: slug, name: role.name || role.slug });
                known[slug] = true;
            }
        });
        defaultMemberRoles.forEach(function (role) {
            if (!known[role.slug]) {
                roles.push(role);
            }
        });
        return roles;
    }

    function normalizeProjectRole(role) {
        return ['manager', 'member', 'viewer'].indexOf(role) >= 0 ? role : 'member';
    }

    function getUserById(userId) {
        var id = Number(userId);
        for (var i = 0; i < allMemberUsers.length; i++) {
            if (Number(allMemberUsers[i].id) === id) return allMemberUsers[i];
        }
        return null;
    }

    function roleOptionsHtml(selectedRole) {
        var activeRole = normalizeProjectRole(selectedRole);
        return projectRoleOrder().map(function (role) {
            return '<option value="' + escapeAttr(role.slug) + '"' + (role.slug === activeRole ? ' selected' : '') + '>' +
                escapeAttr(role.name) +
            '</option>';
        }).join('');
    }

    function memberMetaHtml(user) {
        var parts = [
            '<span>#' + escapeAttr(user.employee_code || '---') + '</span>',
            '<span>' + escapeAttr(user.email || 'Chưa có email') + '</span>',
            '<span>' + escapeAttr(user.job_title || 'Chưa cập nhật chức danh') + '</span>'
        ];
        return parts.join('');
    }

    function renderMemberSourceList() {
        var container = document.getElementById('memberSourceList');
        var countEl = document.getElementById('selectedMemberCount');
        if (!container) return;

        container.innerHTML = '';
        if (countEl) countEl.textContent = String(collectMembersFromDom().length);
        if (!allMemberUsers.length) {
            container.innerHTML = '<div class="selected-empty">Không có nhân viên để chọn.</div>';
            return;
        }

        var query = memberSearchQuery.trim().toLowerCase();
        var filteredUsers = allMemberUsers.filter(function (user) {
            if (!query) return true;
            return [
                user.name,
                user.employee_code,
                user.email,
                user.job_title
            ].join(' ').toLowerCase().indexOf(query) >= 0;
        });

        if (!filteredUsers.length) {
            container.innerHTML = '<div class="selected-empty">Không tìm thấy nhân viên phù hợp.</div>';
            return;
        }

        filteredUsers.forEach(function (user) {
            var userId = Number(user.id);
            var selected = selectedMembers[userId];
            var currentRole = selected ? selected.role : (sourceRoleDrafts[userId] || 'member');
            var row = document.createElement('div');
            row.className = 'member-transfer-row' + (selected ? ' is-selected' : '');
            row.setAttribute('data-user-id', String(userId));
            row.innerHTML =
                '<label class="member-transfer-check" title="' + (selected ? 'Bỏ chọn nhân viên' : 'Chọn nhân viên') + '">' +
                    '<input class="form-check-input member-source-cb" type="checkbox" data-user-id="' + userId + '"' + (selected ? ' checked' : '') + '>' +
                '</label>' +
                '<div class="member-transfer-info">' +
                    '<div class="member-transfer-name">' +
                        '<span class="text-truncate" title="' + escapeAttr(user.name) + '">' + escapeAttr(user.name || 'Không tên') + '</span>' +
                        (selected ? '<span class="badge bg-primary-subtle text-primary-emphasis flex-shrink-0">Đã chọn</span>' : '') +
                    '</div>' +
                    '<div class="member-transfer-meta">' + memberMetaHtml(user) + '</div>' +
                '</div>' +
                '<select class="form-select form-select-sm member-source-role" data-user-id="' + userId + '">' + roleOptionsHtml(currentRole) + '</select>';
            container.appendChild(row);
        });

        bindMemberSourceEvents();
        refreshIcons();
    }

    function renderMemberTransfer() {
        renderMemberSourceList();
    }

    /** Bước 3: render danh sách nhân viên kèm checkbox và role dự án. */
    function populateMemberPicker(users, roles) {
        memberRoles = roles && roles.length ? roles : defaultMemberRoles;
        allMemberUsers = (users || []).map(function (user) {
            return {
                id: Number(user.id || 0),
                name: user.name || '',
                employee_code: user.employee_code || '',
                email: user.email || '',
                job_title: user.job_title || ''
            };
        }).filter(function (user) {
            return user.id > 0;
        });
        bindMemberSearch();
        renderMemberTransfer();
    }

    /** Đọc từng dòng bảng bước 2 → mảng object gửi server (hidden JSON). */
    function collectTaskStatusesFromTable() {
        var out = [];
        document.querySelectorAll('#taskStatusList .status-picker-row').forEach(function (tr) {
            var name = tr.querySelector('.ts-name').value.trim();
            // Slug gửi server luôn chữ thường (đồng nhất với validate backend).
            var slug = tr.querySelector('.ts-slug').value.trim().toLowerCase();
            var color = tr.querySelector('.ts-color').value;
            out.push({
                name: name,
                slug: slug,
                color: color,
                isSlugTouched: tr.querySelector('.ts-slug').dataset.touched === '1',
                position: out.length + 1,
                is_default: tr.querySelector('.ts-def').checked,
                is_done: tr.querySelector('.ts-done').checked
            });
        });
        return out;
    }

    /** Sao chép globalTemplate vào taskRows rồi đảm bảo đúng 1 default + 1 done. */
    function seedFromGlobal() {
        taskRows = (globalTemplate || []).map(function (g) {
            return {
                name: g.name || '',
                slug: g.slug || slugify(g.name || ''),
                color: (g.color && /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(g.color)) ? g.color : '#64748b',
                isSlugTouched: false,
                // API có thể trả 0/1 hoặc chuỗi — dùng == 1 để ép boolean.
                is_default: g.is_default == 1,
                is_done: g.is_done == 1
            };
        });
        ensureOneDefaultOneDone();
        renderTaskRows();
    }

    /** Ràng buộc nghiệp vụ: luôn có đúng một dòng default và một dòng done (bổ sung nếu thiếu). */
    function ensureOneDefaultOneDone() {
        if (!taskRows.length) return;
        var di = taskRows.findIndex(function (r) { return r.is_default; });
        var dj = taskRows.findIndex(function (r) { return r.is_done; });

        if (di < 0 && taskRows.length > 0) taskRows[0].is_default = true;
        if (dj < 0 && taskRows.length > 0) taskRows[taskRows.length - 1].is_done = true;

        taskRows.forEach(function (r, i) {
            if (di >= 0 && i !== di) r.is_default = false;
            if (dj >= 0 && i !== dj) r.is_done = false;
        });
    }

    /** Thêm một dòng trạng thái trống; dòng đầu tiên mặc định là default nếu bảng đang rỗng. */
    function addEmptyRow() {
        taskRows.push({
            name: '',
            slug: '',
            color: '#64748b',
            isSlugTouched: false,
            is_default: taskRows.length === 0,
            is_done: false
        });
        ensureOneDefaultOneDone();
        renderTaskRows();
    }

    /** Bước wizard hiện tại (1–3), đồng bộ panel + stepper header. */
    var currentStep = 1;

    /** Chuyển bước có clamp; cập nhật class is-active / is-done. */
    function setStep(step) {
        currentStep = Math.min(3, Math.max(1, step));
        document.querySelectorAll('.wizard-panel').forEach(function (el) {
            el.classList.toggle('is-active', Number(el.getAttribute('data-step')) === currentStep);
        });
        document.querySelectorAll('.wizard-stepper-item').forEach(function (el) {
            var n = Number(el.getAttribute('data-step-indicator'));
            el.classList.toggle('is-active', n === currentStep);
            el.classList.toggle('is-done', n < currentStep);
        });
        refreshIcons();
    }

    function hasSelectedManager() {
        return collectMembersFromDom().some(function (member) {
            return member.role === 'manager';
        });
    }

    function setMemberErrorVisible(visible) {
        var err = document.getElementById('wizard_members_error');
        if (err) {
            err.classList.toggle('d-none', !visible);
        }
    }

    /** Chỉ user đã được tick chọn trong danh sách nhân viên. */
    function collectMembersFromDom() {
        return Object.keys(selectedMembers).map(function (key) {
            var id = Number(key);
            return {
                user_id: id,
                role: normalizeProjectRole(selectedMembers[id].role)
            };
        }).filter(function (member) {
            return member.user_id > 0;
        });
    }

    /** Khôi phục checkbox + role từ mảng { user_id, role } khi server render lại form lỗi validate. */
    function applyMembersToDom(members) {
        selectedMembers = {};
        (members || []).forEach(function (m) {
            var id = Number(m.user_id);
            if (id > 0 && getUserById(id)) {
                selectedMembers[id] = {
                    user_id: id,
                    role: normalizeProjectRole(m.role || 'member')
                };
                sourceRoleDrafts[id] = selectedMembers[id].role;
            }
        });
        renderMemberTransfer();
    }

    function bindMemberSourceEvents() {
        document.querySelectorAll('.member-source-role').forEach(function (select) {
            select.addEventListener('change', function () {
                var id = Number(select.getAttribute('data-user-id'));
                var role = normalizeProjectRole(select.value);
                sourceRoleDrafts[id] = role;
                if (selectedMembers[id]) {
                    selectedMembers[id].role = role;
                    setMemberErrorVisible(!hasSelectedManager());
                    renderMemberSourceList();
                }
            });
        });

        document.querySelectorAll('.member-source-cb').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var id = Number(cb.getAttribute('data-user-id'));
                if (!id) return;
                if (cb.checked) {
                    selectedMembers[id] = {
                        user_id: id,
                        role: normalizeProjectRole(sourceRoleDrafts[id] || 'member')
                    };
                } else {
                    delete selectedMembers[id];
                }
                setMemberErrorVisible(!hasSelectedManager());
                renderMemberTransfer();
            });
        });
    }

    function bindMemberSearch() {
        var input = document.getElementById('memberSearchInput');
        if (!input) return;
        input.addEventListener('input', function () {
            memberSearchQuery = input.value || '';
            renderMemberSourceList();
            var nextInput = document.getElementById('memberSearchInput');
            if (nextInput) {
                nextInput.value = memberSearchQuery;
            }
        });
    }

    /** Validate dữ liệu từng bước */
    function validateCurrentStep() {
        var isValid = true;
        if (currentStep === 1) {
            var fields = [
                { id: 'fld_name', label: 'Tên dự án' },
                { id: 'fld_owner_id', label: 'Project Sponsor' },
                { id: 'fld_status_id', label: 'Trạng thái dự án' }
            ];
            fields.forEach(function(f) {
                var el = document.getElementById(f.id);
                if (!el.value || !el.value.trim() || el.value === '0') {
                    el.classList.add('is-invalid');
                    isValid = false;
                } else {
                    el.classList.remove('is-invalid');
                }
            });
        } else if (currentStep === 2) {
            // Nếu có dòng nào thì các dòng đó phải đầy đủ tên và slug
            var rows = document.querySelectorAll('#taskStatusList .status-picker-row');
            rows.forEach(function(row) {
                var nameInp = row.querySelector('.ts-name');
                var slugInp = row.querySelector('.ts-slug');
                if (!nameInp.value.trim()) {
                    nameInp.classList.add('is-invalid');
                    isValid = false;
                }
                if (!slugInp.value.trim()) {
                    slugInp.classList.add('is-invalid');
                    isValid = false;
                }
            });
        } else if (currentStep === 3) {
            var hasManager = hasSelectedManager();
            setMemberErrorVisible(!hasManager);
            isValid = hasManager;
        }
        return isValid;
    }

    // --- Điều hướng bước wizard ---
    document.querySelectorAll('[data-wizard-next]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (validateCurrentStep()) {
                setStep(currentStep + 1);
            }
        });
    });

    document.querySelectorAll('[data-wizard-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setStep(currentStep - 1);
        });
    });

    // --- Bước 2: nút tải mẫu / thêm dòng ---
    document.getElementById('btnLoadGlobalStatuses').addEventListener('click', function () {
        seedFromGlobal();
    });
    document.getElementById('btnAddTaskStatusRow').addEventListener('click', function () {
        addEmptyRow();
    });

    /** Submit: đổ dữ liệu bước 2–3 vào hidden input để PHP đọc. */
    document.getElementById('projectWizardForm').addEventListener('submit', function (e) {
        if (currentStep !== 3) {
            e.preventDefault();
            if (validateCurrentStep()) {
                setStep(currentStep + 1);
            }
            return;
        }
        if (!validateCurrentStep()) {
            e.preventDefault();
            return;
        }
        if (!hasSelectedManager()) {
            e.preventDefault();
            setStep(3);
            setMemberErrorVisible(true);
            return;
        }
        var ts = collectTaskStatusesFromTable();
        document.getElementById('wizard_task_statuses').value = JSON.stringify(ts);
        document.getElementById('wizard_members').value = JSON.stringify(collectMembersFromDom());
    });

    /**
     * Sau khi DOM sẵn sàng: gọi API wizard (kèm cookie phiên — cần AuthMiddleware).
     * Thành công → initWizardData để đổ UI; lỗi chỉ log console.
     */
    document.addEventListener('DOMContentLoaded', function () {
        fetch('<?= URLROOT; ?>/api/projects/wizard-data', { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (res) {
                if (res.status === 'success' && res.data) {
                    initWizardData(res.data);
                }
            })
            .catch(function (err) { console.error('Wizard API Error:', err); });
    });

    /**
     * Ghép dữ liệu API + hidden (POST lỗi):
     * 1) Task rows: hidden wizard_task_statuses nếu có → else seed từ globalTemplate.
     * 2) Members: hidden wizard_members nếu có.
     * 3) Owner/status lấy lại từ SERVER_OLD_BASIC nếu server trả lỗi validate.
     */
    function initWizardData(data) {
        // Hidden input do PHP render khi POST lỗi validate.
        var srvStatuses = document.getElementById('wizard_task_statuses').value;
        var srvMembers = document.getElementById('wizard_members').value;

        if (srvStatuses) {
            try {
                var parsed = JSON.parse(srvStatuses);
                if (Array.isArray(parsed) && parsed.length) {
                    var rows = parsed.slice();
                    if (rows.every(function (r) { return typeof r.position === 'number' && !isNaN(r.position); })) {
                        rows.sort(function (a, b) { return a.position - b.position; });
                    }
                    taskRows = rows.map(function (r) {
                        return {
                            name: r.name || '',
                            slug: r.slug || slugify(r.name || ''),
                            color: r.color || '#64748b',
                            isSlugTouched: !!r.isSlugTouched,
                            is_default: !!r.is_default,
                            is_done: !!r.is_done
                        };
                    });
                }
            } catch (e) {}
        }

        // Dữ liệu danh mục từ API (đồng bộ key với ProjectApiController::getWizardData).
        globalTemplate = data.globalTaskStatuses || [];
        memberRoles = data.memberRoles || [];
        populateSelect('fld_owner_id', data.ownerOptions, 'Chọn người bảo trợ dự án');
        populateSelect('fld_status_id', data.statusOptions, 'Chọn trạng thái');
        populateMemberPicker(data.memberUserOptions, memberRoles);

        if (!taskRows.length) {
            seedFromGlobal();
        } else {
            ensureOneDefaultOneDone();
            renderTaskRows();
        }

        // Thành viên: ưu tiên hidden (lỗi validate).
        if (srvMembers) {
            try {
                var pm = JSON.parse(srvMembers);
                if (Array.isArray(pm)) applyMembersToDom(pm);
            } catch (e) {}
        }

        // POST lỗi: ép lại owner/status đúng với dữ liệu PHP.
        if (SERVER_OLD_BASIC && SERVER_OLD_BASIC.owner_id) {
            document.getElementById('fld_owner_id').value = SERVER_OLD_BASIC.owner_id;
        }
        if (SERVER_OLD_BASIC && SERVER_OLD_BASIC.status_id) {
            document.getElementById('fld_status_id').value = SERVER_OLD_BASIC.status_id;
        }

        setStep(SERVER_INITIAL_STEP || 1);
        refreshIcons();

        // Khởi tạo SortableJS cho danh sách trạng thái
        var el = document.getElementById('taskStatusList');
        if (el && window.Sortable) {
            new Sortable(el, {
                animation: 200,
                handle: '.status-drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function() {
                    taskRows = collectTaskStatusesFromTable();
                    renderTaskRows();
                }
            });
        }
    }
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

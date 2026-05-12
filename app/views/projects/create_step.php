<?php
/**
 * Tạo dự án (wizard) / Chỉnh sửa dự án (form một trang — create_old)
 *
 * @var array|null $project
 * @var array|null $old
 * @var array|null $errors
 * @var string $action_url
 * @var array $ownerOptions
 * @var array $statusOptions
 * @var array $globalTaskStatuses
 * @var array $memberUserOptions
 */

$project = $project ?? [];
$old = $old ?? [];
$errors = $errors ?? [];
$action_url = $action_url ?? '';
$globalTaskStatuses = $globalTaskStatuses ?? [];
$memberUserOptions = $memberUserOptions ?? [];

// $isEdit = !empty($project['id']);
// if ($isEdit) {
//     require __DIR__ . '/create.php';
//     return;
// }

$currentStatusId = (int) ($old['status_id'] ?? 0);
$currentOwnerId = (int) ($old['owner_id'] ?? 0);
$currentName = $old['name'] ?? '';
$currentDescription = $old['description'] ?? '';
$currentStartDate = $old['start_date'] ?? '';
$currentDueDate = $old['due_date'] ?? '';

$oldWizardStatusesJson = $old['wizard_task_statuses'] ?? '';
$oldWizardMembersJson = $old['wizard_members'] ?? '';

$globalStatusesJson = htmlspecialchars(
    json_encode($globalTaskStatuses, JSON_UNESCAPED_UNICODE),
    ENT_QUOTES,
    'UTF-8'
);
?>

<style>
    /* Toàn chiều ngang vùng nội dung (không giới hạn 800px của .form-main-container) */
    .form-main-container.project-create-fullwidth {
        max-width: none;
        width: 100%;
        margin: 2rem 0;
    }

    /* Cho phép sticky neo theo <main>; overflow-hidden trên .ui-card sẽ làm sticky không hoạt động đúng */
    .project-create-card {
        overflow: visible;
    }

    .project-create-card .ui-card-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #fff;
    }

    .wizard-stepper {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.25rem;
        margin-bottom: 1.75rem;
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
        position: relative;
        z-index: 1;
    }

    .wizard-stepper-item.is-active .wizard-step-circle {
        background: var(--primary-50);
        color: var(--primary-600);
        border-color: var(--primary-200);
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

    @media (min-width: 576px) {
        .wizard-step-label {
            font-size: 0.8125rem;
        }
    }

    .wizard-panel {
        display: none;
    }

    .wizard-panel.is-active {
        display: block;
    }

    .project-form-textarea {
        min-height: 160px;
    }

    .task-status-row .form-control,
    .task-status-row .form-select {
        font-size: 0.875rem;
    }

    .wizard-task-status-table {
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        overflow: hidden;
    }

    .wizard-task-status-table thead th {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--slate-700);
    }

    .wizard-task-status-table.table-custom tbody tr {
        cursor: default;
    }

    .member-picker-scroll {
        max-height: 340px;
        overflow-y: auto;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        background: var(--slate-50);
        padding: 0.5rem;
    }

    .member-picker-row {
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        padding: 0.65rem 0.85rem;
        margin-bottom: 0.5rem;
        background: #fff;
    }

    .member-picker-row:last-child {
        margin-bottom: 0;
    }

    .member-picker-row:hover {
        background: var(--slate-50);
    }

    .wizard-panel-footer {
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--slate-100);
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
            <span>Quay lại</span>
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
                    <div class="d-flex flex-column gap-4">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="form-section h-100">
                                    <div class="d-flex flex-column gap-3">
                                        <div>
                                            <label class="form-label">Tên dự án <span class="text-danger">*</span></label>
                                            <input type="text" name="name" id="fld_name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" placeholder="Nhập tên dự án" value="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') ?>">
                                            <?php if (isset($errors['name'])): ?>
                                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <label class="form-label">Trưởng dự án <span class="text-danger">*</span></label>
                                            <select name="owner_id" id="fld_owner_id" class="form-select <?= isset($errors['owner_id']) ? 'is-invalid' : '' ?>">
                                                <option value="">Chọn người phụ trách chính</option>
                                                <?php foreach (($ownerOptions ?? []) as $owner): ?>
                                                    <option value="<?= (int) $owner['id'] ?>" <?= $currentOwnerId === (int) $owner['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($owner['email']) ? ' - ' . htmlspecialchars($owner['email'], ENT_QUOTES, 'UTF-8') : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (isset($errors['owner_id'])): ?>
                                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['owner_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <label class="form-label">Trạng thái dự án <span class="text-danger">*</span></label>
                                            <select name="status_id" id="fld_status_id" class="form-select <?= isset($errors['status_id']) ? 'is-invalid' : '' ?>">
                                                <option value="">Chọn trạng thái</option>
                                                <?php foreach (($statusOptions ?? []) as $status): ?>
                                                    <option value="<?= (int) $status['id'] ?>" <?= $currentStatusId === (int) $status['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($status['name'], ENT_QUOTES, 'UTF-8') ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (isset($errors['status_id'])): ?>
                                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['status_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div>
                                            <label class="form-label">Thời gian bắt đầu</label>
                                            <input type="date" name="start_date" id="fld_start_date" class="form-control" value="<?= htmlspecialchars($currentStartDate, ENT_QUOTES, 'UTF-8') ?>">
                                        </div>

                                        <div class="mb-0">
                                            <label class="form-label">Thời gian kết thúc</label>
                                            <input type="date" name="due_date" id="fld_due_date" class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($currentDueDate, ENT_QUOTES, 'UTF-8') ?>">
                                            <?php if (isset($errors['due_date'])): ?>
                                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['due_date'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-section h-100">
                                    <label class="form-label">Mô tả dự án</label>
                                    <textarea name="description" id="fld_description" class="form-control project-form-textarea" placeholder="Mô tả ngắn gọn mục tiêu và phạm vi"><?= htmlspecialchars($currentDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>
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
                    <p class="text-slate-600 small mb-3">Thiết lập các cột trạng thái cho công việc trong dự án. Nếu bỏ trống khi gửi, hệ thống sẽ sao chép mẫu mặc định toàn hệ thống.</p>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLoadGlobalStatuses">
                            <i data-lucide="copy" size="16"></i>
                            <span class="ms-1">Lấy từ mẫu hệ thống</span>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddTaskStatusRow">
                            <i data-lucide="plus" size="16"></i>
                            <span class="ms-1">Thêm dòng</span>
                        </button>
                    </div>

                    <div class="table-responsive mb-2 wizard-task-status-table">
                        <table class="table table-sm table-custom align-middle mb-0">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th>Tên</th>
                                    <th style="min-width:120px">Slug</th>
                                    <th style="width:56px">Màu</th>
                                    <th class="text-center" style="width:90px">Mặc định</th>
                                    <th class="text-center" style="width:90px">Hoàn thành</th>
                                    <th style="width:48px"></th>
                                </tr>
                            </thead>
                            <tbody id="taskStatusTableBody"></tbody>
                        </table>
                    </div>
                    <p class="text-slate-500 small mb-0">Phải có đúng một trạng thái “Mặc định” và một trạng thái “Hoàn thành”.</p>

                    <div class="d-flex flex-wrap justify-content-between gap-2 wizard-panel-footer">
                        <button type="button" class="btn btn-outline-secondary px-4" data-wizard-prev>
                            <i data-lucide="chevron-left" class="me-1" size="18"></i>
                            Quay lại
                        </button>
                        <button type="button" class="btn btn-primary px-4" data-wizard-next>
                            Tiếp theo
                            <i data-lucide="chevron-right" class="ms-1" size="18"></i>
                        </button>
                    </div>
                </div>

                <!-- Bước 3 -->
                <div class="wizard-panel" data-step="3">
                    <p class="text-slate-600 small mb-3">Chọn thêm nhân sự tham gia dự án (ngoài trưởng dự án đã chọn ở bước 1).</p>

                    <div id="memberPicker" class="mb-4 member-picker-scroll">
                        <?php foreach ($memberUserOptions as $u): ?>
                            <div class="member-picker-row d-flex flex-wrap align-items-center gap-2" data-user-id="<?= (int) $u['id'] ?>">
                                <div class="form-check mb-0">
                                    <input class="form-check-input member-cb" type="checkbox" id="member_cb_<?= (int) $u['id'] ?>">
                                    <label class="form-check-label small fw-medium text-slate-800" for="member_cb_<?= (int) $u['id'] ?>">
                                        <?= htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($u['email'])): ?>
                                            <span class="text-slate-500 fw-normal"> — <?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                <select class="form-select form-select-sm ms-auto member-role" style="max-width: 160px;" disabled>
                                    <option value="member">Thành viên</option>
                                    <option value="lead">Trưởng nhóm</option>
                                    <option value="viewer">Chỉ xem</option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between gap-2 wizard-panel-footer">
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
(function () {
    var STORAGE_KEY = 'nexuspm_project_create_wizard_v1';
    var globalTemplate = <?= json_encode($globalTaskStatuses, JSON_UNESCAPED_UNICODE); ?> || [];

    function slugify(str) {
        if (!str) return '';
        var s = str.normalize ? str.normalize('NFD') : str;
        s = s.replace(/[\u0300-\u036f]/g, '');
        s = s.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        return s || 'status';
    }

    function refreshIcons() {
        if (window.lucide) lucide.createIcons();
    }

    function loadState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function saveState(state) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {}
    }

    function gatherBasicFromDom() {
        return {
            name: document.getElementById('fld_name').value,
            description: document.getElementById('fld_description').value,
            owner_id: document.getElementById('fld_owner_id').value,
            status_id: document.getElementById('fld_status_id').value,
            start_date: document.getElementById('fld_start_date').value,
            due_date: document.getElementById('fld_due_date').value
        };
    }

    function applyBasicToDom(b) {
        if (!b) return;
        var map = {
            fld_name: 'name',
            fld_description: 'description',
            fld_owner_id: 'owner_id',
            fld_status_id: 'status_id',
            fld_start_date: 'start_date',
            fld_due_date: 'due_date'
        };
        Object.keys(map).forEach(function (id) {
            var el = document.getElementById(id);
            if (el && b[map[id]] !== undefined) el.value = b[map[id]];
        });
    }

    var taskRows = [];

    function renderTaskRows() {
        var tbody = document.getElementById('taskStatusTableBody');
        tbody.innerHTML = '';
        taskRows.forEach(function (row, idx) {
            var tr = document.createElement('tr');
            tr.className = 'task-status-row';
            tr.innerHTML =
                '<td><input type="text" class="form-control ts-name" placeholder="Ví dụ: Đang làm" value="' + escapeAttr(row.name) + '"></td>' +
                '<td><input type="text" class="form-control ts-slug" placeholder="doing" value="' + escapeAttr(row.slug) + '"></td>' +
                '<td><input type="color" class="form-control form-control-color ts-color p-1" value="' + escapeAttr(row.color) + '"></td>' +
                '<td class="text-center"><input type="radio" name="ts_default" class="form-check-input ts-def" ' + (row.is_default ? 'checked' : '') + '></td>' +
                '<td class="text-center"><input type="radio" name="ts_done" class="form-check-input ts-done" ' + (row.is_done ? 'checked' : '') + '></td>' +
                '<td><button type="button" class="btn btn-link text-danger p-0 btn-remove-ts" title="Xóa"><i data-lucide="trash-2" size="18"></i></button></td>';
            tbody.appendChild(tr);

            var nameInp = tr.querySelector('.ts-name');
            var slugInp = tr.querySelector('.ts-slug');
            nameInp.addEventListener('input', function () {
                row.name = nameInp.value;
                if (!slugInp.dataset.touched) slugInp.value = slugify(nameInp.value);
                row.slug = slugInp.value;
                persistWizard();
            });
            slugInp.addEventListener('input', function () {
                slugInp.dataset.touched = '1';
                row.slug = slugInp.value;
                persistWizard();
            });
            tr.querySelector('.ts-color').addEventListener('input', function (e) {
                row.color = e.target.value;
                persistWizard();
            });
            tr.querySelector('.ts-def').addEventListener('change', function () {
                taskRows.forEach(function (r, i) { r.is_default = i === idx; });
                renderTaskRows();
                persistWizard();
            });
            tr.querySelector('.ts-done').addEventListener('change', function () {
                taskRows.forEach(function (r, i) { r.is_done = i === idx; });
                renderTaskRows();
                persistWizard();
            });
            tr.querySelector('.btn-remove-ts').addEventListener('click', function () {
                taskRows.splice(idx, 1);
                renderTaskRows();
                persistWizard();
            });
        });
        refreshIcons();
    }

    function escapeAttr(s) {
        if (s === undefined || s === null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function collectTaskStatusesFromTable() {
        var out = [];
        document.querySelectorAll('#taskStatusTableBody tr').forEach(function (tr) {
            var name = tr.querySelector('.ts-name').value.trim();
            var slug = tr.querySelector('.ts-slug').value.trim();
            var color = tr.querySelector('.ts-color').value;
            out.push({
                name: name,
                slug: slug,
                color: color,
                is_default: tr.querySelector('.ts-def').checked,
                is_done: tr.querySelector('.ts-done').checked
            });
        });
        return out;
    }

    function seedFromGlobal() {
        taskRows = (globalTemplate || []).map(function (g) {
            return {
                name: g.name || '',
                slug: g.slug || slugify(g.name || ''),
                color: g.color && /^#/.test(g.color) ? g.color : '#64748b',
                is_default: !!Number(g.is_default),
                is_done: !!Number(g.is_done)
            };
        });
        ensureOneDefaultOneDone();
        renderTaskRows();
    }

    function ensureOneDefaultOneDone() {
        if (!taskRows.length) return;
        var di = taskRows.findIndex(function (r) { return r.is_default; });
        var dj = taskRows.findIndex(function (r) { return r.is_done; });
        if (di < 0) taskRows[0].is_default = true;
        if (dj < 0) taskRows[taskRows.length - 1].is_done = true;
        taskRows.forEach(function (r, i) {
            if (di >= 0 && i !== di) r.is_default = false;
            if (dj >= 0 && i !== dj) r.is_done = false;
        });
    }

    function addEmptyRow() {
        taskRows.push({
            name: '',
            slug: '',
            color: '#64748b',
            is_default: taskRows.length === 0,
            is_done: false
        });
        ensureOneDefaultOneDone();
        renderTaskRows();
    }

    var currentStep = 1;

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
        persistWizard();
        refreshIcons();
    }

    function persistWizard() {
        var members = collectMembersFromDom();
        saveState({
            step: currentStep,
            basic: gatherBasicFromDom(),
            taskStatuses: collectTaskStatusesFromTable(),
            members: members
        });
    }

    function collectMembersFromDom() {
        var list = [];
        document.querySelectorAll('.member-picker-row').forEach(function (row) {
            var cb = row.querySelector('.member-cb');
            var roleSel = row.querySelector('.member-role');
            if (cb && cb.checked) {
                list.push({
                    user_id: Number(row.getAttribute('data-user-id')),
                    role: roleSel ? roleSel.value : 'member'
                });
            }
        });
        return list;
    }

    function applyMembersToDom(members) {
        var map = {};
        (members || []).forEach(function (m) {
            map[Number(m.user_id)] = m.role || 'member';
        });
        document.querySelectorAll('.member-picker-row').forEach(function (row) {
            var id = Number(row.getAttribute('data-user-id'));
            var cb = row.querySelector('.member-cb');
            var roleSel = row.querySelector('.member-role');
            if (map[id] !== undefined) {
                cb.checked = true;
                roleSel.disabled = false;
                roleSel.value = map[id];
            } else {
                cb.checked = false;
                roleSel.disabled = true;
            }
        });
    }

    function bindMemberPickers() {
        document.querySelectorAll('.member-picker-row').forEach(function (row) {
            var cb = row.querySelector('.member-cb');
            var roleSel = row.querySelector('.member-role');
            cb.addEventListener('change', function () {
                roleSel.disabled = !cb.checked;
                persistWizard();
            });
            roleSel.addEventListener('change', persistWizard);
        });
    }

    document.querySelectorAll('[data-wizard-next]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            gatherBasicFromDom();
            setStep(currentStep + 1);
        });
    });
    document.querySelectorAll('[data-wizard-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setStep(currentStep - 1);
        });
    });

    document.getElementById('btnLoadGlobalStatuses').addEventListener('click', function () {
        seedFromGlobal();
        persistWizard();
    });
    document.getElementById('btnAddTaskStatusRow').addEventListener('click', function () {
        addEmptyRow();
        persistWizard();
    });

    ['fld_name', 'fld_description', 'fld_owner_id', 'fld_status_id', 'fld_start_date', 'fld_due_date'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', persistWizard);
        if (el) el.addEventListener('input', persistWizard);
    });

    document.getElementById('projectWizardForm').addEventListener('submit', function () {
        var ts = collectTaskStatusesFromTable();
        document.getElementById('wizard_task_statuses').value = JSON.stringify(ts);
        document.getElementById('wizard_members').value = JSON.stringify(collectMembersFromDom());
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (e) {}
    });

    function syncOwnerMemberLock() {
        var ownerId = document.getElementById('fld_owner_id').value;
        document.querySelectorAll('.member-picker-row').forEach(function (row) {
            var uid = String(row.getAttribute('data-user-id'));
            var isOwner = ownerId && uid === String(ownerId);
            row.style.opacity = isOwner ? '0.55' : '';
            row.querySelector('.member-cb').disabled = !!isOwner;
            row.querySelector('.member-role').disabled = !!isOwner || !row.querySelector('.member-cb').checked;
            if (isOwner) {
                row.querySelector('.member-cb').checked = false;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindMemberPickers();

        var saved = loadState();
        var srvStatuses = document.getElementById('wizard_task_statuses').value;
        var srvMembers = document.getElementById('wizard_members').value;

        if (srvStatuses) {
            try {
                var parsed = JSON.parse(srvStatuses);
                if (Array.isArray(parsed) && parsed.length) {
                    taskRows = parsed.map(function (r) {
                        return {
                            name: r.name || '',
                            slug: r.slug || '',
                            color: r.color || '#64748b',
                            is_default: !!r.is_default,
                            is_done: !!r.is_done
                        };
                    });
                }
            } catch (e) {}
        }

        if (!taskRows.length && saved && saved.taskStatuses && saved.taskStatuses.length) {
            taskRows = saved.taskStatuses;
        }

        if (!taskRows.length) {
            seedFromGlobal();
        } else {
            ensureOneDefaultOneDone();
            renderTaskRows();
        }

        if (srvMembers) {
            try {
                var pm = JSON.parse(srvMembers);
                if (Array.isArray(pm)) applyMembersToDom(pm);
            } catch (e) {}
        } else if (saved && saved.members) {
            applyMembersToDom(saved.members);
        }

        if (saved && saved.basic) applyBasicToDom(saved.basic);

        var ownSel = document.getElementById('fld_owner_id');
        syncOwnerMemberLock();
        ownSel.addEventListener('change', function () {
            syncOwnerMemberLock();
            persistWizard();
        });

        var initialStep = (saved && saved.step) ? saved.step : 1;
        setStep(initialStep);
        refreshIcons();
    });
})();
</script>

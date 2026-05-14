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
        border: 1px solid var(--slate-200);
    }

    .project-create-card .ui-card-header {
        flex-shrink: 0;
        background: #fff;
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
        min-height: 400px; /* Tăng chiều cao mô tả dự án ở bước 1 */
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
        max-height: 450px;
        overflow-y: auto;
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        background: var(--slate-50);
        padding: 1rem;
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 0.75rem;
        scrollbar-gutter: stable;
    }

    @media (min-width: 768px) {
        .member-picker-scroll {
            grid-template-columns: repeat(2, 1fr); /* 2 nhân sự mỗi hàng trên tablet */
        }
    }

    @media (min-width: 1200px) {
        .member-picker-scroll {
            grid-template-columns: repeat(3, 1fr); /* 3 nhân sự mỗi hàng trên desktop */
        }
    }

    .member-picker-row {
        border: 1px solid var(--slate-200);
        border-radius: var(--radius-md);
        padding: 0.65rem 0.85rem;
        margin-bottom: 0;
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .member-picker-row:hover {
        background: var(--slate-50);
        border-color: var(--primary-200);
    }

    .member-picker-row .member-role {
        width: 100%;
        max-width: none !important;
    }

    .wizard-panel-footer {
        flex-shrink: 0;
        margin-top: auto;
        padding: 1rem 2rem 0; /* Padding ngang khớp với nội dung */
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
                                    <label class="form-label">Trưởng dự án <span class="text-danger">*</span></label>
                                    <select name="owner_id" id="fld_owner_id" class="form-select <?= isset($errors['owner_id']) ? 'is-invalid' : '' ?>">
                                        <option value="">Chọn người phụ trách chính</option>
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

                                <div class="mb-3">
                                    <label class="form-label">Thời gian bắt đầu</label>
                                    <input type="date" name="start_date" id="fld_start_date" class="form-control" value="<?= htmlspecialchars($currentStartDate, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Thời gian kết thúc</label>
                                    <input type="date" name="due_date" id="fld_due_date" class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($currentDueDate, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($errors['due_date'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['due_date'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
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
                        <p class="text-slate-600 small mb-3">Thiết lập các cột trạng thái cho công việc trong dự án. Nếu bỏ trống khi gửi, hệ thống sẽ sao chép mẫu mặc định toàn hệ thống.</p>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btnLoadGlobalStatuses">
                                <i data-lucide="copy" size="16"></i>
                                <span class="ms-1">Tải mẫu hệ thống</span>
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
                                        <th style="width:60px">Màu</th>
                                        <th class="text-center" style="width:90px">Mặc định</th>
                                        <th class="text-center" style="width:90px">Hoàn thành</th>
                                        <th style="width:48px"></th>
                                    </tr>
                                </thead>
                                <tbody id="taskStatusTableBody"></tbody>
                            </table>
                        </div>
                        <p class="text-slate-500 small mb-0">Phải có đúng một trạng thái “Mặc định” và một trạng thái “Hoàn thành”.</p>
                    </div>

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
                    <div class="wizard-panel-content">
                        <div id="memberPicker" class="mb-4 member-picker-grid"></div>
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
/**
 * Logic wizard tạo dự án (phía trình duyệt):
 * - Gọi API `/api/projects/wizard-data` để nạp select + mẫu task + danh sách user.
 * - Lưu nháp (bước, form, bảng trạng thái, thành viên) vào localStorage khi thao tác.
 * - Trước submit: ghi JSON vào hidden để PHP xử lý; sau submit xóa nháp.
 * IIFE: tránh biến toàn cục trùng tên với các script khác.
 */
(function () {
    /** Khóa localStorage — dùng chung một phiên bản để có thể đổi key khi đổi cấu trúc dữ liệu. */
    var STORAGE_KEY = 'nexuspm_project_create_wizard_v1';
    /** Giá trị owner/status từ server khi form POST lỗi (PHP render lại) — ưu tiên hơn draft. */
    var SERVER_OLD_BASIC = <?= $serverOldBasicJson !== false ? $serverOldBasicJson : '{}' ?>;
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

    /** Lucide: render lại icon sau khi chèn HTML động (nút xóa, chevron, v.v.). */
    function refreshIcons() {
        if (window.lucide) lucide.createIcons();
    }

    /** Đọc object trạng thái wizard đã lưu; lỗi parse → null. */
    function loadState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    /** Ghi nháp wizard vào localStorage (hết quota hoặc lỗi ghi thì bỏ qua, không throw). */
    function saveState(state) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) {}
    }

    /** Thu thập bước 1 (thông tin cơ bản) từ DOM — dùng khi persist vào localStorage. */
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

    /** Ghi object basic vào các input tương ứng (khôi phục từ localStorage). */
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

    /** Mảng trạng thái công việc (bước 2) — nguồn sự thật trước khi sync vào hidden / localStorage. */
    var taskRows = [];

    /** Vẽ lại toàn bộ tbody từ taskRows; gắn listener cập nhật row + persist (không re-render khi đổi radio để giữ focus). */
    function renderTaskRows() {
        var tbody = document.getElementById('taskStatusTableBody');
        tbody.innerHTML = '';
        taskRows.forEach(function (row, idx) {
            var tr = document.createElement('tr');
            tr.className = 'task-status-row';
            tr.innerHTML =
                '<td><input type="text" class="form-control ts-name" placeholder="Ví dụ: Đang làm" value="' + escapeAttr(row.name) + '"></td>' +
                '<td>' +
                    '<input type="text" class="form-control ts-slug" placeholder="doing" value="' + escapeAttr(row.slug) + '">' +
                '</td>' +
                '<td><input type="color" class="form-control form-control-color ts-color p-1" value="' + escapeAttr(row.color) + '"></td>' +
                '<td class="text-center"><input type="radio" name="ts_default" class="form-check-input ts-def" ' + (row.is_default ? 'checked' : '') + '></td>' +
                '<td class="text-center"><input type="radio" name="ts_done" class="form-check-input ts-done" ' + (row.is_done ? 'checked' : '') + '></td>' +
                '<td><button type="button" class="btn btn-link text-danger p-0 btn-remove-ts" title="Xóa"><i data-lucide="trash-2" size="18"></i></button></td>';
            tbody.appendChild(tr);

            var nameInp = tr.querySelector('.ts-name');
            var slugInp = tr.querySelector('.ts-slug');
            // Tên đổi → slug tự sinh nếu user chưa sửa tay (dataset.touched).
            nameInp.addEventListener('input', function () {
                row.name = nameInp.value.trim();
                if (!slugInp.dataset.touched) {
                    slugInp.value = slugify(nameInp.value);
                    row.slug = slugInp.value;
                }
                persistWizard();
            });
            // User sửa slug trực tiếp → đánh dấu touched, không ghi đè từ tên nữa.
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
                persistWizard();
            });
            tr.querySelector('.ts-done').addEventListener('change', function () {
                taskRows.forEach(function (r, i) { r.is_done = i === idx; });
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

    /** Bước 3: render danh sách user + checkbox + select vai trò (role disable khi chưa tick). */
    function populateMemberPicker(users, roles) {
        var container = document.getElementById('memberPicker');
        if (!container) return;
        container.innerHTML = '';
        
        var rolesHtml = (roles || []).map(function(r) {
            return '<option value="' + escapeAttr(r.slug) + '">' + escapeAttr(r.name) + '</option>';
        }).join('');

        (users || []).forEach(function(u) {
            var row = document.createElement('div');
            row.className = 'member-picker-row';
            row.dataset.userId = u.id;
            
            row.innerHTML = 
                '<div class="form-check mb-0">' +
                    '<input class="form-check-input member-cb" type="checkbox" id="member_cb_' + u.id + '">' +
                    '<label class="form-check-label small fw-bold text-slate-800 d-block text-truncate" for="member_cb_' + u.id + '" title="' + escapeAttr(u.name) + '">' +
                        escapeAttr(u.name) +
                    '</label>' +
                    (u.email ? '<div class="text-slate-500 small text-truncate" style="font-size: 0.7rem;">' + escapeAttr(u.email) + '</div>' : '') +
                '</div>' +
                '<select class="form-select form-select-sm member-role" disabled>' +
                    rolesHtml +
                '</select>';
            container.appendChild(row);
        });
        
        bindMemberPickers();
    }

    /** Đọc từng dòng bảng bước 2 → mảng object gửi server (hidden JSON). */
    function collectTaskStatusesFromTable() {
        var out = [];
        document.querySelectorAll('#taskStatusTableBody tr').forEach(function (tr) {
            var name = tr.querySelector('.ts-name').value.trim();
            // Slug gửi server luôn chữ thường (đồng nhất với validate backend).
            var slug = tr.querySelector('.ts-slug').value.trim().toLowerCase();
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

    /** Sao chép globalTemplate vào taskRows rồi đảm bảo đúng 1 default + 1 done. */
    function seedFromGlobal() {
        taskRows = (globalTemplate || []).map(function (g) {
            return {
                name: g.name || '',
                slug: g.slug || slugify(g.name || ''),
                color: (g.color && /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(g.color)) ? g.color : '#64748b',
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
            is_default: taskRows.length === 0,
            is_done: false
        });
        ensureOneDefaultOneDone();
        renderTaskRows();
    }

    /** Bước wizard hiện tại (1–3), đồng bộ panel + stepper header. */
    var currentStep = 1;

    /** Chuyển bước có clamp; cập nhật class is-active / is-done và lưu nháp. */
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

    /** Gom toàn bộ trạng thái UI vào localStorage (bước, basic, task rows, members). */
    function persistWizard() {
        var members = collectMembersFromDom();
        saveState({
            step: currentStep,
            basic: gatherBasicFromDom(),
            taskStatuses: collectTaskStatusesFromTable(),
            members: members
        });
    }

    /** Chỉ user được tick + role đã chọn — không gồm trưởng dự án (backend tự thêm owner). */
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

    /** Khôi phục checkbox + role từ mảng { user_id, role } (server lỗi validate hoặc localStorage). */
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

    /** Gắn sự kiện: tick mới bật chọn role; mọi thay đổi đều persist. */
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

    // --- Điều hướng bước wizard ---
    document.querySelectorAll('[data-wizard-next]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setStep(currentStep + 1);
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
        persistWizard();
    });
    document.getElementById('btnAddTaskStatusRow').addEventListener('click', function () {
        addEmptyRow();
        persistWizard();
    });

    // Mỗi thay đổi form bước 1 → cập nhật nháp localStorage.
    ['fld_name', 'fld_description', 'fld_owner_id', 'fld_status_id', 'fld_start_date', 'fld_due_date'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', persistWizard);
        if (el) el.addEventListener('input', persistWizard);
    });

    /** Submit: đổ dữ liệu bước 2–3 vào hidden input để PHP đọc; xóa nháp vì coi như đã gửi. */
    document.getElementById('projectWizardForm').addEventListener('submit', function (e) {
        var ts = collectTaskStatusesFromTable();
        document.getElementById('wizard_task_statuses').value = JSON.stringify(ts);
        document.getElementById('wizard_members').value = JSON.stringify(collectMembersFromDom());
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (e) {}
    });

    /**
     * Trưởng dự án không được tick thêm ở bước 3 (tránh trùng vai trò).
     * Đổi owner → bỏ tick user đó nếu trước đó được chọn làm member.
     */
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
     * Ghép dữ liệu API + hidden (POST lỗi) + localStorage theo thứ ưu tiên hợp lý:
     * 1) Task rows: hidden wizard_task_statuses nếu có → else draft → else seed từ globalTemplate.
     * 2) Members: hidden wizard_members nếu có → else draft.
     * 3) Basic: draft rồi ghi đè owner/status bằng SERVER_OLD_BASIC nếu server trả lỗi validate.
     */
    function initWizardData(data) {
        var saved = loadState();
        // Hidden input do PHP render khi POST lỗi validate — ưu tiên khôi phục trước draft.
        var srvStatuses = document.getElementById('wizard_task_statuses').value;
        var srvMembers = document.getElementById('wizard_members').value;

        if (srvStatuses) {
            try {
                var parsed = JSON.parse(srvStatuses);
                if (Array.isArray(parsed) && parsed.length) {
                    taskRows = parsed.map(function (r) {
                        return {
                            name: r.name || '',
                            slug: r.slug || slugify(r.name || ''),
                            color: r.color || '#64748b',
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
        populateSelect('fld_owner_id', data.ownerOptions, 'Chọn người phụ trách chính');
        populateSelect('fld_status_id', data.statusOptions, 'Chọn trạng thái');
        populateMemberPicker(data.memberUserOptions, memberRoles);

        // Nếu chưa có từ hidden và bảng vẫn trống → thử draft localStorage.
        if (!taskRows.length && saved && saved.taskStatuses && saved.taskStatuses.length) {
            taskRows = saved.taskStatuses;
        }

        if (!taskRows.length) {
            seedFromGlobal();
        } else {
            ensureOneDefaultOneDone();
            renderTaskRows();
        }

        // Thành viên: ưu tiên hidden (lỗi validate), không thì draft.
        if (srvMembers) {
            try {
                var pm = JSON.parse(srvMembers);
                if (Array.isArray(pm)) applyMembersToDom(pm);
            } catch (e) {}
        } else if (saved && saved.members) {
            applyMembersToDom(saved.members);
        }

        // Form bước 1: khôi phục từ draft (sau đó có thể bị ghi đè bởi giá trị server).
        if (saved && saved.basic) {
            applyBasicToDom(saved.basic);
        }

        // POST lỗi: ép lại owner/status đúng với dữ liệu PHP (ưu tiên hơn draft).
        if (SERVER_OLD_BASIC && SERVER_OLD_BASIC.owner_id) {
            document.getElementById('fld_owner_id').value = SERVER_OLD_BASIC.owner_id;
        }
        if (SERVER_OLD_BASIC && SERVER_OLD_BASIC.status_id) {
            document.getElementById('fld_status_id').value = SERVER_OLD_BASIC.status_id;
        }

        // Đổi trưởng dự án → khóa dòng member tương ứng ở bước 3; mỗi lần đổi cũng persist.
        var ownSel = document.getElementById('fld_owner_id');
        syncOwnerMemberLock();
        ownSel.addEventListener('change', function () {
            syncOwnerMemberLock();
            persistWizard();
        });

        // Bước hiện tại: từ draft nếu có (setStep sẽ cập nhật UI + lưu lại).
        var initialStep = (saved && saved.step) ? saved.step : 1;
        setStep(initialStep);
        refreshIcons();
    }
})();
</script>
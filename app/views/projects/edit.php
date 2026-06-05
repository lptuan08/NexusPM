<?php
/**
 * Giao diện chỉnh sửa dự án
 * 
 * @var array $project Dữ liệu dự án hiện tại
 * @var array $ownerOptions Danh sách nhân viên làm người bảo trợ dự án
 * @var array $statusOptions Danh sách trạng thái dự án
 * @var array $errors Mảng lỗi validation
 * @var array $old Dữ liệu cũ khi submit lỗi
 * @var string $action_url URL xử lý form
 */

$project = $project ?? [];
$old = $old ?? [];
$errors = $errors ?? [];
$ownerOptions = $ownerOptions ?? [];
$statusOptions = $statusOptions ?? [];

// Ưu tiên lấy dữ liệu từ old (nếu vừa submit lỗi) sau đó mới đến dữ liệu gốc của dự án
$getValue = function($field) use ($project, $old) {
    return $old[$field] ?? $project[$field] ?? '';
};

$formatDateTime = static function ($date, string $fallback = '-'): string {
    $timestamp = !empty($date) ? strtotime((string) $date) : false;

    return $timestamp !== false ? date('H:i d/m/Y', $timestamp) : $fallback;
};

$projectEditUrl = URLROOT . '/projects/' . (int) ($project['id'] ?? 0) . '/edit';
$projectDetailUrl = URLROOT . '/projects/' . (int) ($project['id'] ?? 0);
$createdAtText = $formatDateTime($project['created_at'] ?? null);
$createdByText = !empty($project['created_by_name']) ? (string) $project['created_by_name'] : 'Chưa ghi nhận';
$createdByEmail = !empty($project['created_by_email']) ? (string) $project['created_by_email'] : '';
$updatedAtText = $formatDateTime($project['updated_at'] ?? null);
$updatedByText = !empty($project['updated_by_name']) ? (string) $project['updated_by_name'] : 'Chưa ghi nhận';
$updatedByEmail = !empty($project['updated_by_email']) ? (string) $project['updated_by_email'] : '';

$buildInitials = static function (string $name): string {
    $name = trim($name);
    if ($name === '') {
        return 'PM';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $first = $parts[0] ?? '';
    $last = $parts[count($parts) - 1] ?? $first;
    $initials = (function_exists('mb_substr') ? mb_substr($first, 0, 1, 'UTF-8') : substr($first, 0, 1))
        . (function_exists('mb_substr') ? mb_substr($last, 0, 1, 'UTF-8') : substr($last, 0, 1));

    return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
};

$selectedOwnerId = (string) $getValue('owner_id');
$selectedOwner = null;
foreach ($ownerOptions as $option) {
    if ($selectedOwnerId !== '' && (string) ($option['id'] ?? '') === $selectedOwnerId) {
        $selectedOwner = $option;
        break;
    }
}

$selectedOwnerName = !empty($selectedOwner['name']) ? (string) $selectedOwner['name'] : 'Chọn người bảo trợ dự án';
$selectedOwnerEmail = $selectedOwner
    ? (!empty($selectedOwner['email']) ? (string) $selectedOwner['email'] : 'Chưa có email')
    : 'Tên và email nhân viên';
$selectedOwnerInitials = $selectedOwner ? $buildInitials((string) ($selectedOwner['name'] ?? '')) : 'PM';
?>

<style>
    .project-edit-shell {
        max-width: 1280px;
        margin: 0 auto;
    }

    .project-edit-card {
        border-radius: var(--radius-lg);
        overflow: hidden;
        background: var(--md-content-surface);
        border: 0;
        box-shadow: none;
    }

    .project-edit-card-body {
        padding: 1.5rem;
    }

    .project-edit-field {
        margin-bottom: 1rem;
    }

    .project-edit-field:last-child {
        margin-bottom: 0;
    }

    .project-edit-card .form-control,
    .project-edit-card .form-select {
        border-radius: var(--radius-md);
    }

    .project-edit-card .form-select {
        min-height: 48px;
        padding: 0.7rem 2.75rem;
        background-color: var(--md-surface);
        border-color: var(--md-outline-variant);
        color: var(--md-on-surface);
        font-weight: 500;
        transition: border-color 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
    }

    .project-edit-card .form-select:hover {
        background-color: var(--md-surface-container-low);
        border-color: var(--md-outline);
    }

    .project-edit-card .form-select:focus {
        background-color: var(--md-surface);
        border-color: var(--md-primary);
        box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.12);
    }

    .project-edit-select-shell {
        position: relative;
    }

    .project-edit-select-icon {
        position: absolute;
        left: 0.9rem;
        top: 50%;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.25rem;
        height: 1.25rem;
        color: var(--md-on-surface-variant);
        pointer-events: none;
        transform: translateY(-50%);
        transition: color 0.16s ease;
    }

    .project-edit-select-icon svg,
    .project-edit-select-icon i {
        width: 18px;
        height: 18px;
    }

    .project-edit-select-shell:focus-within .project-edit-select-icon {
        color: var(--md-primary);
    }

    .project-edit-select-shell .form-select.is-invalid {
        padding-right: 4.5rem;
    }

    .project-employee-picker {
        position: relative;
    }

    .project-employee-picker-trigger {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        min-height: 58px;
        padding: 0.65rem 0.9rem;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--radius-md);
        background: var(--md-surface);
        color: var(--md-on-surface);
        text-align: left;
        transition: background-color 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .project-employee-picker-trigger:hover,
    .project-employee-picker-trigger[aria-expanded="true"] {
        background: var(--md-surface-container-low);
        border-color: var(--md-outline);
    }

    .project-employee-picker-trigger:focus-visible,
    .project-employee-picker:focus-within .project-employee-picker-trigger {
        border-color: var(--md-primary);
        box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.12);
        outline: 0;
    }

    .project-employee-picker.is-invalid .project-employee-picker-trigger {
        border-color: var(--bs-form-invalid-border-color, #dc3545);
    }

    .project-employee-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 36px;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--md-surface-container-low);
        color: var(--md-primary);
        font-size: 0.78rem;
        font-weight: 700;
    }

    .project-employee-main {
        min-width: 0;
        flex: 1 1 auto;
    }

    .project-employee-name {
        display: block;
        overflow: hidden;
        color: var(--md-on-surface);
        font-size: 0.92rem;
        font-weight: 600;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .project-employee-email {
        display: block;
        overflow: hidden;
        color: var(--md-on-surface-variant);
        font-size: 0.78rem;
        line-height: 1.3;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .project-employee-chevron {
        flex: 0 0 auto;
        width: 18px;
        height: 18px;
        color: var(--md-on-surface-variant);
    }

    .project-employee-menu {
        width: 100%;
        max-height: 280px;
        padding: 0.35rem;
        overflow-y: auto;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--radius-md);
        box-shadow: var(--md-shadow-2);
    }

    .project-employee-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-height: 54px;
        padding: 0.55rem 0.65rem;
        border-radius: var(--radius-sm);
    }

    .project-employee-option.active,
    .project-employee-option:active {
        background: var(--md-primary-container);
        color: var(--md-on-primary-container);
    }

    .project-employee-option.active .project-employee-avatar {
        background: var(--md-surface);
    }

    .project-employee-check {
        flex: 0 0 auto;
        width: 17px;
        height: 17px;
        color: var(--md-primary);
    }

    .project-edit-description {
        min-height: 235px !important;
    }

    .project-edit-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .project-edit-actions .btn {
        min-width: 132px;
        padding-inline: 1.25rem;
    }

    .project-edit-meta-card {
        margin-top: 1rem;
        padding: 1rem 1.25rem;
        border-radius: var(--radius-lg);
        background: var(--md-content-surface);
        border: 0;
        box-shadow: none;
    }

    .project-edit-meta-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 220px;
    }

    .project-edit-meta-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-md);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: var(--md-surface-container-low);
        color: var(--md-on-surface-variant);
    }

    .project-edit-meta-icon svg,
    .project-edit-meta-icon i {
        width: 18px;
        height: 18px;
    }

    .project-edit-meta-label {
        font-size: 0.75rem;
        color: var(--md-on-surface-variant);
        line-height: 1.2;
    }

    .project-edit-meta-value {
        font-size: 0.9375rem;
        color: var(--md-on-surface);
        font-weight: 600;
        line-height: 1.3;
    }

    .project-edit-meta-subtext {
        font-size: 0.8125rem;
        color: var(--md-on-surface-variant);
        line-height: 1.25;
    }

    .project-edit-meta-item--subtle {
        opacity: 0.72;
    }

    .project-edit-meta-item--subtle .project-edit-meta-icon {
        width: 34px;
        height: 34px;
        background: transparent;
        border: 1px solid var(--md-outline-variant);
    }

    .project-edit-meta-item--subtle .project-edit-meta-icon svg,
    .project-edit-meta-item--subtle .project-edit-meta-icon i {
        width: 16px;
        height: 16px;
    }

    .project-edit-meta-item--subtle .project-edit-meta-value {
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--md-on-surface-variant);
    }

    .project-edit-meta-item--subtle .project-edit-meta-label,
    .project-edit-meta-item--subtle .project-edit-meta-subtext {
        color: var(--slate-400);
    }

    @media (max-width: 575.98px) {
        .project-edit-card-body {
            padding: 1rem;
        }

        .project-edit-actions {
            width: 100%;
        }

        .project-edit-actions .btn {
            flex: 1 1 100%;
            min-width: 100%;
        }
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <a href="<?= URLROOT; ?>/projects/<?= (int) ($project['id'] ?? 0) ?>" class="text-decoration-none text-slate-500 hover-text-primary"><?= htmlspecialchars((string) ($project['name'] ?? 'Chi tiết'), ENT_QUOTES, 'UTF-8') ?></a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Chỉnh sửa</span>
    </div>

    <div class="page-actions project-edit-actions">
        <a href="<?= htmlspecialchars($projectDetailUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
            <i data-lucide="x"></i>
            <span>Hủy bỏ</span>
        </a>
        <a href="<?= htmlspecialchars($projectEditUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" title="Nạp lại dữ liệu từ database">
            <i data-lucide="refresh-ccw"></i>
            <span>Làm lại</span>
        </a>
        <button type="submit" form="projectEditForm" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Lưu thay đổi</span>
        </button>
    </div>
</div>

<div class="form-main-container project-edit-shell">
    <div class="ui-card project-edit-card">
        <div class="ui-card-body project-edit-card-body">
            <form action="<?= htmlspecialchars((string) $action_url, ENT_QUOTES, 'UTF-8') ?>" method="POST" id="projectEditForm">
                <?php App\helpers\SecurityHelper::csrfInput(); ?>
                
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="project-edit-field">
                            <label class="form-label fw-semibold">Tên dự án <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars((string) $getValue('name'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập tên dự án">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars((string) $errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="project-edit-field">
                            <label class="form-label fw-semibold">Mô tả dự án</label>
                            <textarea name="description" class="form-control project-edit-description" rows="6"
                                      placeholder="Mô tả chi tiết mục tiêu dự án..."><?= htmlspecialchars((string) $getValue('description'), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="project-edit-field">
                            <label class="form-label fw-semibold">Người bảo trợ dự án <span class="text-danger">*</span></label>
                            <div class="dropdown project-employee-picker <?= isset($errors['owner_id']) ? 'is-invalid' : '' ?>" data-project-owner-picker>
                                <input type="hidden" name="owner_id" value="<?= htmlspecialchars($selectedOwnerId, ENT_QUOTES, 'UTF-8') ?>" data-project-owner-input>
                                <button type="button" class="project-employee-picker-trigger" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="project-employee-avatar" data-project-owner-initials><?= htmlspecialchars($selectedOwnerInitials, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="project-employee-main">
                                        <span class="project-employee-name" data-project-owner-name><?= htmlspecialchars($selectedOwnerName, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="project-employee-email" data-project-owner-email><?= htmlspecialchars($selectedOwnerEmail, ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                    <i data-lucide="chevron-down" class="project-employee-chevron"></i>
                                </button>
                                <ul class="dropdown-menu project-employee-menu">
                                    <li>
                                        <button
                                            type="button"
                                            class="dropdown-item project-employee-option <?= $selectedOwnerId === '' ? 'active' : '' ?>"
                                            data-project-owner-option
                                            data-owner-id=""
                                            data-owner-name="Chọn người bảo trợ dự án"
                                            data-owner-email="Tên và email nhân viên"
                                            data-owner-initials="PM">
                                            <span class="project-employee-avatar">PM</span>
                                            <span class="project-employee-main">
                                                <span class="project-employee-name">Chọn người bảo trợ dự án</span>
                                                <span class="project-employee-email">Tên và email nhân viên</span>
                                            </span>
                                            <i data-lucide="check" class="project-employee-check <?= $selectedOwnerId === '' ? '' : 'd-none' ?>"></i>
                                        </button>
                                    </li>
                                    <?php foreach ($ownerOptions as $opt): ?>
                                        <?php
                                        $ownerId = (string) ($opt['id'] ?? '');
                                        $ownerName = (string) ($opt['name'] ?? '');
                                        $ownerEmail = (string) ($opt['email'] ?? '');
                                        $isSelectedOwner = $selectedOwnerId !== '' && $selectedOwnerId === $ownerId;
                                        $ownerInitials = $buildInitials($ownerName);
                                        ?>
                                        <li>
                                            <button
                                                type="button"
                                                class="dropdown-item project-employee-option <?= $isSelectedOwner ? 'active' : '' ?>"
                                                data-project-owner-option
                                                data-owner-id="<?= htmlspecialchars($ownerId, ENT_QUOTES, 'UTF-8') ?>"
                                                data-owner-name="<?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?>"
                                                data-owner-email="<?= htmlspecialchars($ownerEmail !== '' ? $ownerEmail : 'Chưa có email', ENT_QUOTES, 'UTF-8') ?>"
                                                data-owner-initials="<?= htmlspecialchars($ownerInitials, ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="project-employee-avatar"><?= htmlspecialchars($ownerInitials, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="project-employee-main">
                                                    <span class="project-employee-name"><?= htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="project-employee-email"><?= htmlspecialchars($ownerEmail !== '' ? $ownerEmail : 'Chưa có email', ENT_QUOTES, 'UTF-8') ?></span>
                                                </span>
                                                <i data-lucide="check" class="project-employee-check <?= $isSelectedOwner ? '' : 'd-none' ?>"></i>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php if (isset($errors['owner_id'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars((string) $errors['owner_id'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="project-edit-field">
                            <label class="form-label fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                            <div class="project-edit-select-shell">
                                <span class="project-edit-select-icon">
                                    <i data-lucide="flag"></i>
                                </span>
                                <select name="status_id" class="form-select <?= isset($errors['status_id']) ? 'is-invalid' : '' ?>">
                                    <?php foreach ($statusOptions as $status): ?>
                                        <option value="<?= (int) ($status['id'] ?? 0) ?>" <?= (int) $getValue('status_id') === (int) ($status['id'] ?? 0) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) ($status['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (isset($errors['status_id'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars((string) $errors['status_id'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="project-edit-field">
                            <label class="form-label fw-semibold">Ngày bắt đầu</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars((string) $getValue('start_date'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="project-edit-field">
                            <label class="form-label fw-semibold">Hạn xử lý (Due Date)</label>
                            <input type="date" name="due_date" class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $getValue('due_date'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (isset($errors['due_date'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars((string) $errors['due_date'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="ui-card project-edit-meta-card">
        <div class="d-flex flex-wrap align-items-center gap-4">
            <div class="project-edit-meta-item project-edit-meta-item--subtle">
                <span class="project-edit-meta-icon">
                    <i data-lucide="clock"></i>
                </span>
                <span>
                    <span class="project-edit-meta-label d-block">Thời gian cập nhật</span>
                    <span class="project-edit-meta-value d-block"><?= htmlspecialchars($updatedAtText, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </div>
            <div class="project-edit-meta-item project-edit-meta-item--subtle">
                <span class="project-edit-meta-icon">
                    <i data-lucide="user-round-check"></i>
                </span>
                <span>
                    <span class="project-edit-meta-label d-block">Người cập nhật</span>
                    <span class="project-edit-meta-value d-block"><?= htmlspecialchars($updatedByText, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($updatedByEmail !== ''): ?>
                        <span class="project-edit-meta-subtext d-block"><?= htmlspecialchars($updatedByEmail, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="project-edit-meta-item project-edit-meta-item--subtle">
                <span class="project-edit-meta-icon">
                    <i data-lucide="calendar-plus"></i>
                </span>
                <span>
                    <span class="project-edit-meta-label d-block">Thời gian tạo</span>
                    <span class="project-edit-meta-value d-block"><?= htmlspecialchars($createdAtText, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </div>
            <div class="project-edit-meta-item project-edit-meta-item--subtle">
                <span class="project-edit-meta-icon">
                    <i data-lucide="user-plus"></i>
                </span>
                <span>
                    <span class="project-edit-meta-label d-block">Người tạo</span>
                    <span class="project-edit-meta-value d-block"><?= htmlspecialchars($createdByText, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($createdByEmail !== ''): ?>
                        <span class="project-edit-meta-subtext d-block"><?= htmlspecialchars($createdByEmail, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var picker = document.querySelector('[data-project-owner-picker]');
        if (!picker) {
            return;
        }

        var input = picker.querySelector('[data-project-owner-input]');
        var nameTarget = picker.querySelector('[data-project-owner-name]');
        var emailTarget = picker.querySelector('[data-project-owner-email]');
        var initialsTarget = picker.querySelector('[data-project-owner-initials]');
        var options = Array.prototype.slice.call(picker.querySelectorAll('[data-project-owner-option]'));

        options.forEach(function (option) {
            option.addEventListener('click', function () {
                var ownerId = option.getAttribute('data-owner-id') || '';
                var ownerName = option.getAttribute('data-owner-name') || 'Chọn người bảo trợ dự án';
                var ownerEmail = option.getAttribute('data-owner-email') || 'Tên và email nhân viên';
                var ownerInitials = option.getAttribute('data-owner-initials') || 'PM';

                if (input) {
                    input.value = ownerId;
                }

                if (nameTarget) {
                    nameTarget.textContent = ownerName;
                }

                if (emailTarget) {
                    emailTarget.textContent = ownerEmail;
                }

                if (initialsTarget) {
                    initialsTarget.textContent = ownerInitials;
                }

                options.forEach(function (item) {
                    var isActive = item === option;
                    item.classList.toggle('active', isActive);

                    var check = item.querySelector('.project-employee-check');
                    if (check) {
                        check.classList.toggle('d-none', !isActive);
                    }
                });
            });
        });
    });
</script>

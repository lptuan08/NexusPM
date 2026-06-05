<?php
/**
 * Form thêm/chỉnh sửa thành viên dự án.
 *
 * @var array $project
 * @var array|null $member
 * @var array $employeeOptions
 * @var array $memberRoles
 * @var array $memberStatuses
 * @var array $errors
 * @var array $old
 * @var string $action_url
 * @var string $detail_url
 * @var string $reload_url
 * @var string $pageTitle
 */

$project = $project ?? [];
$member = $member ?? null;
$employeeOptions = $employeeOptions ?? [];
$memberRoles = $memberRoles ?? [];
$memberStatuses = $memberStatuses ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$action_url = $action_url ?? '';
$detail_url = $detail_url ?? (URLROOT . '/projects/' . (int) ($project['id'] ?? 0));
$reload_url = $reload_url ?? '';
$isEditing = is_array($member) && !empty($member['user_id']);
$projectId = (int) ($project['id'] ?? 0);

$dateValue = static function ($value, string $fallback = ''): string {
    $timestamp = !empty($value) ? strtotime((string) $value) : false;

    return $timestamp !== false ? date('Y-m-d', $timestamp) : $fallback;
};

$buildInitials = static function (string $name): string {
    $name = trim($name);
    if ($name === '') {
        return 'NV';
    }

    $parts = preg_split('/\s+/', $name) ?: [];
    $first = $parts[0] ?? '';
    $last = $parts[count($parts) - 1] ?? $first;
    $initials = (function_exists('mb_substr') ? mb_substr($first, 0, 1, 'UTF-8') : substr($first, 0, 1))
        . (function_exists('mb_substr') ? mb_substr($last, 0, 1, 'UTF-8') : substr($last, 0, 1));

    return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
};

$selectedUserId = (string) ($old['user_id'] ?? ($member['user_id'] ?? $member['id'] ?? ''));
$selectedEmployee = $isEditing ? $member : null;
if (!$selectedEmployee && $selectedUserId !== '') {
    foreach ($employeeOptions as $employee) {
        if ((string) ($employee['id'] ?? '') === $selectedUserId) {
            $selectedEmployee = $employee;
            break;
        }
    }
}

$selectedEmployeeName = !empty($selectedEmployee['name']) ? (string) $selectedEmployee['name'] : 'Chọn nhân viên dự án';
$selectedEmployeeEmail = $selectedEmployee
    ? (!empty($selectedEmployee['email']) ? (string) $selectedEmployee['email'] : 'Chưa có email')
    : 'Tên và email nhân viên';
$selectedEmployeeMeta = trim((string) ($selectedEmployee['job_title'] ?? '') . ' ' . (string) ($selectedEmployee['employee_code'] ?? ''));
$selectedEmployeeMeta = $selectedEmployeeMeta !== '' ? $selectedEmployeeMeta : 'Chưa có chức danh';
$selectedEmployeeInitials = $selectedEmployee ? $buildInitials($selectedEmployeeName) : 'NV';

$selectedRole = (string) ($old['role'] ?? ($member['role'] ?? 'member'));
$selectedRole = array_key_exists($selectedRole, $memberRoles) ? $selectedRole : 'member';

if (isset($old['participation_status'])) {
    $selectedParticipationStatus = (string) $old['participation_status'];
} elseif ($isEditing && !empty($member['left_at'])) {
    $selectedParticipationStatus = 'left';
} elseif ($isEditing && (int) ($member['is_active'] ?? 1) === 0) {
    $selectedParticipationStatus = 'paused';
} else {
    $selectedParticipationStatus = 'active';
}
$selectedParticipationStatus = array_key_exists($selectedParticipationStatus, $memberStatuses) ? $selectedParticipationStatus : 'active';

$joinedAtValue = (string) ($old['joined_at'] ?? $dateValue($member['joined_at'] ?? null, date('Y-m-d')));
$leftAtValue = (string) ($old['left_at'] ?? $dateValue($member['left_at'] ?? null));
$projectStatusColor = !empty($project['status_color']) ? (string) $project['status_color'] : '#64748b';
if (!preg_match('/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $projectStatusColor)) {
    $projectStatusColor = '#64748b';
}
?>

<style>
    .project-member-form-shell {
        max-width: 1280px;
        margin: 0 auto;
    }

    .project-member-form-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .project-member-form-actions .btn {
        min-width: 132px;
        padding-inline: 1.25rem;
    }

    .project-member-form-card {
        display: flex;
        flex-direction: column;
        height: 100%;
        border: 0;
        border-radius: var(--radius-lg);
        background: var(--md-content-surface);
        box-shadow: none;
    }

    .project-member-form-card-body {
        padding: 1.5rem;
    }

    .project-member-form-section {
        margin-bottom: 1.25rem;
    }

    .project-member-form-section:last-child {
        margin-bottom: 0;
    }

    .project-member-form-label {
        margin-bottom: 0.55rem;
        color: var(--md-on-surface);
        font-weight: 650;
    }

    .member-employee-picker-trigger,
    .member-employee-locked {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        width: 100%;
        min-height: 64px;
        padding: 0.75rem 0.9rem;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--radius-md);
        background: var(--md-surface);
        color: var(--md-on-surface);
        text-align: left;
    }

    .member-employee-picker-trigger {
        transition: background-color 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .member-employee-picker-trigger:hover,
    .member-employee-picker-trigger[aria-expanded="true"] {
        background: var(--md-surface-container-low);
        border-color: var(--md-outline);
    }

    .member-employee-picker-trigger:focus-visible,
    .member-employee-picker:focus-within .member-employee-picker-trigger {
        border-color: var(--md-primary);
        box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.12);
        outline: 0;
    }

    .member-employee-picker.is-invalid .member-employee-picker-trigger {
        border-color: var(--bs-form-invalid-border-color, #dc3545);
    }

    .member-employee-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--md-surface-container-low);
        color: var(--md-primary);
        font-size: 0.8rem;
        font-weight: 750;
    }

    .member-employee-main {
        min-width: 0;
        flex: 1 1 auto;
    }

    .member-employee-name,
    .member-option-name {
        display: block;
        overflow: hidden;
        color: var(--md-on-surface);
        font-size: 0.94rem;
        font-weight: 650;
        line-height: 1.25;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .member-employee-email,
    .member-option-meta {
        display: block;
        overflow: hidden;
        color: var(--md-on-surface-variant);
        font-size: 0.8rem;
        line-height: 1.35;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .member-employee-chevron {
        flex: 0 0 auto;
        width: 18px;
        height: 18px;
        color: var(--md-on-surface-variant);
    }

    .member-employee-menu {
        width: 100%;
        padding: 0.35rem;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--radius-md);
        box-shadow: var(--md-shadow-2);
    }

    .member-employee-search {
        padding: 0.4rem;
    }

    .member-employee-scroll {
        max-height: 320px;
        overflow-y: auto;
    }

    .member-employee-option {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-height: 58px;
        padding: 0.55rem 0.65rem;
        border-radius: var(--radius-sm);
    }

    .member-employee-option.active,
    .member-employee-option:active {
        background: var(--md-primary-container);
        color: var(--md-on-primary-container);
    }

    .member-employee-option.active .member-employee-avatar {
        background: var(--md-surface);
    }

    .member-option-check {
        flex: 0 0 auto;
        width: 17px;
        height: 17px;
        color: var(--md-primary);
    }

    .member-picker-empty {
        padding: 1rem;
        color: var(--md-on-surface-variant);
        font-size: 0.875rem;
        text-align: center;
    }

    .member-segment-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .member-segment-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .member-segment-card {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        height: 100%;
        min-height: 104px;
        padding: 0.9rem;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--radius-md);
        background: var(--md-surface);
        color: var(--md-on-surface);
        cursor: pointer;
        transition: background-color 0.16s ease, border-color 0.16s ease, box-shadow 0.16s ease, transform 0.16s ease;
    }

    .member-segment-card:hover {
        background: var(--md-surface-container-low);
        border-color: var(--md-outline);
    }

    .member-segment-input:focus-visible + .member-segment-card,
    .member-segment-input:checked + .member-segment-card {
        border-color: var(--md-primary);
        background: var(--md-primary-container);
        box-shadow: 0 0 0 3px rgba(11, 87, 208, 0.1);
    }

    .member-segment-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 34px;
        width: 34px;
        height: 34px;
        border-radius: var(--radius-sm);
        background: var(--md-surface-container-low);
        color: var(--md-primary);
    }

    .member-segment-icon svg,
    .member-segment-icon i {
        width: 17px;
        height: 17px;
    }

    .member-segment-title {
        display: block;
        font-size: 0.92rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .member-segment-hint {
        display: block;
        margin-top: 0.25rem;
        color: var(--md-on-surface-variant);
        font-size: 0.78rem;
        line-height: 1.35;
    }

    .member-date-shell .form-control {
        min-height: 48px;
        border-radius: var(--radius-md);
    }

    .member-project-summary {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        padding: 1rem;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--radius-md);
        background:
            radial-gradient(circle at top right, color-mix(in srgb, var(--project-status-color) 12%, transparent), transparent 48%),
            var(--md-surface);
    }

    .member-project-summary-title {
        color: var(--md-on-surface);
        font-size: 1rem;
        font-weight: 750;
        line-height: 1.3;
    }

    .member-project-summary-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .member-soft-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        min-height: 28px;
        padding: 0.25rem 0.6rem;
        border: 1px solid var(--md-outline-variant);
        border-radius: 999px;
        color: var(--md-on-surface-variant);
        font-size: 0.78rem;
        font-weight: 600;
    }

    .member-soft-chip svg,
    .member-soft-chip i {
        width: 14px;
        height: 14px;
    }

    @media (max-width: 991.98px) {
        .member-segment-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .project-member-form-card-body {
            padding: 1rem;
        }

        .project-member-form-actions {
            width: 100%;
        }

        .project-member-form-actions .btn {
            flex: 1 1 100%;
            min-width: 100%;
        }
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <a href="<?= htmlspecialchars($detail_url, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none text-slate-500 hover-text-primary">
            <?= htmlspecialchars((string) ($project['name'] ?? 'Chi tiết'), ENT_QUOTES, 'UTF-8') ?>
        </a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title"><?= htmlspecialchars((string) ($pageTitle ?? ($isEditing ? 'Chỉnh sửa thành viên' : 'Thêm thành viên')), ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="page-actions project-member-form-actions">
        <a href="<?= htmlspecialchars($detail_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
            <i data-lucide="x"></i>
            <span>Hủy bỏ</span>
        </a>
        <a href="<?= htmlspecialchars($reload_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary" title="Nạp lại dữ liệu từ database">
            <i data-lucide="refresh-ccw"></i>
            <span>Làm lại</span>
        </a>
        <button type="submit" form="projectMemberForm" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Lưu thay đổi</span>
        </button>
    </div>
</div>

<form action="<?= htmlspecialchars((string) $action_url, ENT_QUOTES, 'UTF-8') ?>" method="POST" id="projectMemberForm" class="form-main-container project-member-form-shell">
    <?php App\helpers\SecurityHelper::csrfInput(); ?>

    <div class="row g-4 align-items-stretch">
        <div class="col-lg-8">
            <div class="ui-card project-member-form-card">
                <div class="project-member-form-card-body">
                    <div class="project-member-form-section">
                        <label class="form-label project-member-form-label">Nhân viên <span class="text-danger">*</span></label>
                        <?php if ($isEditing): ?>
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($selectedUserId, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="member-employee-locked">
                                <span class="member-employee-avatar"><?= htmlspecialchars($selectedEmployeeInitials, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="member-employee-main">
                                    <span class="member-employee-name"><?= htmlspecialchars($selectedEmployeeName, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="member-employee-email"><?= htmlspecialchars($selectedEmployeeEmail, ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                                <span class="member-soft-chip">
                                    <i data-lucide="lock"></i>
                                    <span>Đã khóa</span>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="dropdown member-employee-picker <?= isset($errors['user_id']) ? 'is-invalid' : '' ?>" data-member-employee-picker>
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($selectedUserId, ENT_QUOTES, 'UTF-8') ?>" data-member-employee-input>
                                <button type="button" class="member-employee-picker-trigger" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                    <span class="member-employee-avatar" data-member-employee-initials><?= htmlspecialchars($selectedEmployeeInitials, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="member-employee-main">
                                        <span class="member-employee-name" data-member-employee-name><?= htmlspecialchars($selectedEmployeeName, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="member-employee-email" data-member-employee-email><?= htmlspecialchars($selectedEmployeeEmail, ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                    <i data-lucide="chevron-down" class="member-employee-chevron"></i>
                                </button>
                                <div class="dropdown-menu member-employee-menu">
                                    <div class="member-employee-search">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white text-slate-400">
                                                <i data-lucide="search" size="16"></i>
                                            </span>
                                            <input type="search" class="form-control border-start-0" placeholder="Tìm theo tên hoặc email..." data-member-employee-search>
                                        </div>
                                    </div>
                                    <div class="member-employee-scroll">
                                        <?php foreach ($employeeOptions as $employee): ?>
                                            <?php
                                            $employeeId = (string) ($employee['id'] ?? '');
                                            $employeeName = (string) ($employee['name'] ?? '');
                                            $employeeEmail = (string) ($employee['email'] ?? '');
                                            $employeeCode = (string) ($employee['employee_code'] ?? '');
                                            $employeeJobTitle = (string) ($employee['job_title'] ?? '');
                                            $employeeMeta = trim(($employeeJobTitle !== '' ? $employeeJobTitle : 'Chưa có chức danh') . ($employeeCode !== '' ? ' · ' . $employeeCode : ''));
                                            $employeeInitials = $buildInitials($employeeName);
                                            $isSelectedEmployee = $selectedUserId !== '' && $selectedUserId === $employeeId;
                                            $searchText = function_exists('mb_strtolower')
                                                ? mb_strtolower(trim($employeeName . ' ' . $employeeEmail . ' ' . $employeeCode . ' ' . $employeeJobTitle), 'UTF-8')
                                                : strtolower(trim($employeeName . ' ' . $employeeEmail . ' ' . $employeeCode . ' ' . $employeeJobTitle));
                                            ?>
                                            <button
                                                type="button"
                                                class="dropdown-item member-employee-option <?= $isSelectedEmployee ? 'active' : '' ?>"
                                                data-member-employee-option
                                                data-employee-id="<?= htmlspecialchars($employeeId, ENT_QUOTES, 'UTF-8') ?>"
                                                data-employee-name="<?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?>"
                                                data-employee-email="<?= htmlspecialchars($employeeEmail !== '' ? $employeeEmail : 'Chưa có email', ENT_QUOTES, 'UTF-8') ?>"
                                                data-employee-meta="<?= htmlspecialchars($employeeMeta, ENT_QUOTES, 'UTF-8') ?>"
                                                data-employee-initials="<?= htmlspecialchars($employeeInitials, ENT_QUOTES, 'UTF-8') ?>"
                                                data-employee-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="member-employee-avatar"><?= htmlspecialchars($employeeInitials, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="member-employee-main">
                                                    <span class="member-option-name"><?= htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="member-option-meta"><?= htmlspecialchars(($employeeEmail !== '' ? $employeeEmail : 'Chưa có email') . ' · ' . $employeeMeta, ENT_QUOTES, 'UTF-8') ?></span>
                                                </span>
                                                <i data-lucide="check" class="member-option-check <?= $isSelectedEmployee ? '' : 'd-none' ?>"></i>
                                            </button>
                                        <?php endforeach; ?>
                                        <div class="member-picker-empty <?= empty($employeeOptions) ? '' : 'd-none' ?>" data-member-employee-empty>
                                            Không còn nhân viên khả dụng để thêm vào dự án.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (isset($errors['user_id'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars((string) $errors['user_id'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="project-member-form-section">
                        <div class="project-member-form-label">Vai trò trong dự án</div>
                        <div class="member-segment-grid">
                            <?php foreach ($memberRoles as $roleValue => $role): ?>
                                <?php $roleInputId = 'member_role_' . preg_replace('/[^a-z0-9_-]/i', '', (string) $roleValue); ?>
                                <label>
                                    <input class="member-segment-input" type="radio" name="role" id="<?= htmlspecialchars($roleInputId, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $roleValue, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedRole === (string) $roleValue ? 'checked' : '' ?>>
                                    <span class="member-segment-card">
                                        <span class="member-segment-icon">
                                            <i data-lucide="<?= htmlspecialchars((string) ($role['icon'] ?? 'user'), ENT_QUOTES, 'UTF-8') ?>"></i>
                                        </span>
                                        <span>
                                            <span class="member-segment-title"><?= htmlspecialchars((string) ($role['label'] ?? $roleValue), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="member-segment-hint"><?= htmlspecialchars((string) ($role['hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($errors['role'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars((string) $errors['role'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="project-member-form-section">
                        <div class="project-member-form-label">Trạng thái tham gia</div>
                        <div class="member-segment-grid">
                            <?php foreach ($memberStatuses as $statusValue => $status): ?>
                                <?php $statusInputId = 'member_status_' . preg_replace('/[^a-z0-9_-]/i', '', (string) $statusValue); ?>
                                <label>
                                    <input class="member-segment-input" type="radio" name="participation_status" id="<?= htmlspecialchars($statusInputId, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $statusValue, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedParticipationStatus === (string) $statusValue ? 'checked' : '' ?> data-member-status-input>
                                    <span class="member-segment-card">
                                        <span class="member-segment-icon">
                                            <i data-lucide="<?= htmlspecialchars((string) ($status['icon'] ?? 'circle'), ENT_QUOTES, 'UTF-8') ?>"></i>
                                        </span>
                                        <span>
                                            <span class="member-segment-title"><?= htmlspecialchars((string) ($status['label'] ?? $statusValue), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="member-segment-hint"><?= htmlspecialchars((string) ($status['hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="ui-card project-member-form-card">
                <div class="project-member-form-card-body">
                    <div class="project-member-form-section">
                        <div class="project-member-form-label">Dự án</div>
                        <div class="member-project-summary" style="--project-status-color: <?= htmlspecialchars($projectStatusColor, ENT_QUOTES, 'UTF-8') ?>;">
                            <div>
                                <div class="member-project-summary-title"><?= htmlspecialchars((string) ($project['name'] ?? 'Dự án'), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="member-project-summary-meta mt-2">
                                    <?php if (!empty($project['project_code'])): ?>
                                        <span class="member-soft-chip">
                                            <i data-lucide="hash"></i>
                                            <span><?= htmlspecialchars((string) $project['project_code'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($project['status_name'])): ?>
                                        <span class="member-soft-chip" style="border-color: <?= htmlspecialchars($projectStatusColor, ENT_QUOTES, 'UTF-8') ?>33; color: <?= htmlspecialchars($projectStatusColor, ENT_QUOTES, 'UTF-8') ?>;">
                                            <i data-lucide="flag"></i>
                                            <span><?= htmlspecialchars((string) $project['status_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($project['owner_name'])): ?>
                                <div class="member-soft-chip align-self-start">
                                    <i data-lucide="user-round-check"></i>
                                    <span><?= htmlspecialchars((string) $project['owner_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="project-member-form-section member-date-shell">
                        <label class="form-label project-member-form-label" for="joined_at">Ngày tham gia <span class="text-danger">*</span></label>
                        <input type="date" name="joined_at" id="joined_at" class="form-control <?= isset($errors['joined_at']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($joinedAtValue, ENT_QUOTES, 'UTF-8') ?>">
                        <?php if (isset($errors['joined_at'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars((string) $errors['joined_at'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="project-member-form-section member-date-shell <?= $selectedParticipationStatus === 'left' ? '' : 'd-none' ?>" data-member-left-wrap>
                        <label class="form-label project-member-form-label" for="left_at">Ngày rời dự án <span class="text-danger">*</span></label>
                        <input type="date" name="left_at" id="left_at" class="form-control <?= isset($errors['left_at']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($leftAtValue, ENT_QUOTES, 'UTF-8') ?>" data-member-left-input>
                        <?php if (isset($errors['left_at'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars((string) $errors['left_at'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if ($isEditing): ?>
                        <div class="project-member-form-section mb-0">
                            <div class="d-flex flex-column gap-2">
                                <span class="member-soft-chip align-self-start">
                                    <i data-lucide="calendar"></i>
                                    <span>Tham gia <?= htmlspecialchars($dateValue($member['joined_at'] ?? null, '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                </span>
                                <?php if (!empty($member['left_at'])): ?>
                                    <span class="member-soft-chip align-self-start">
                                        <i data-lucide="log-out"></i>
                                        <span>Rời dự án <?= htmlspecialchars($dateValue($member['left_at'] ?? null, '-'), ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var picker = document.querySelector('[data-member-employee-picker]');
        if (picker) {
            var input = picker.querySelector('[data-member-employee-input]');
            var nameTarget = picker.querySelector('[data-member-employee-name]');
            var emailTarget = picker.querySelector('[data-member-employee-email]');
            var initialsTarget = picker.querySelector('[data-member-employee-initials]');
            var trigger = picker.querySelector('.member-employee-picker-trigger');
            var searchInput = picker.querySelector('[data-member-employee-search]');
            var emptyState = picker.querySelector('[data-member-employee-empty]');
            var options = Array.prototype.slice.call(picker.querySelectorAll('[data-member-employee-option]'));

            options.forEach(function (option) {
                option.addEventListener('click', function () {
                    var employeeId = option.getAttribute('data-employee-id') || '';
                    var employeeName = option.getAttribute('data-employee-name') || 'Chọn nhân viên dự án';
                    var employeeEmail = option.getAttribute('data-employee-email') || 'Tên và email nhân viên';
                    var employeeInitials = option.getAttribute('data-employee-initials') || 'NV';

                    if (input) input.value = employeeId;
                    if (nameTarget) nameTarget.textContent = employeeName;
                    if (emailTarget) emailTarget.textContent = employeeEmail;
                    if (initialsTarget) initialsTarget.textContent = employeeInitials;

                    options.forEach(function (item) {
                        var isActive = item === option;
                        item.classList.toggle('active', isActive);
                        var check = item.querySelector('.member-option-check');
                        if (check) {
                            check.classList.toggle('d-none', !isActive);
                        }
                    });

                    if (window.bootstrap && window.bootstrap.Dropdown && trigger) {
                        window.bootstrap.Dropdown.getOrCreateInstance(trigger).hide();
                    }
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    var query = searchInput.value.trim().toLowerCase();
                    var visibleCount = 0;

                    options.forEach(function (option) {
                        var searchText = (option.getAttribute('data-employee-search') || '').toLowerCase();
                        var visible = query === '' || searchText.indexOf(query) !== -1;
                        option.classList.toggle('d-none', !visible);
                        if (visible) visibleCount++;
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('d-none', visibleCount > 0);
                    }
                });
            }
        }

        var leftWrap = document.querySelector('[data-member-left-wrap]');
        var leftInput = document.querySelector('[data-member-left-input]');
        var statusInputs = Array.prototype.slice.call(document.querySelectorAll('[data-member-status-input]'));

        function syncLeftDateVisibility() {
            var selected = statusInputs.find(function (input) {
                return input.checked;
            });
            var shouldShow = selected && selected.value === 'left';

            if (leftWrap) {
                leftWrap.classList.toggle('d-none', !shouldShow);
            }

            if (leftInput && !shouldShow) {
                leftInput.value = '';
            }
        }

        statusInputs.forEach(function (input) {
            input.addEventListener('change', syncLeftDateVisibility);
        });
        syncLeftDateVisibility();
    });
</script>

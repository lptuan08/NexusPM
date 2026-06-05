<?php
/**
 * Giao diện chi tiết dự án
 * 
 * @var array $project Thông tin dự án
 * @var array $members Danh sách thành viên tham gia
 * @var array $tasks Danh sách công việc thuộc dự án
 * @var array $allUsers Danh sách toàn bộ người dùng (cho modal thêm thành viên)
 * @var string $pageTitle Tiêu đề trang
 */

$project = (!empty($project) && is_array($project)) ? $project : [];
$members = $members ?? [];
$tasks = $tasks ?? [];
$allUsers = $allUsers ?? [];
$stats = $stats ?? ['total' => 0, 'completed' => 0, 'overdue' => 0, 'percent' => 0];
$canUpdateProject = $canUpdateProject ?? false;
$canDeleteProject = $canDeleteProject ?? false;
$canCreateTask = $canCreateTask ?? false;
$canViewTaskStatuses = $canViewTaskStatuses ?? false;
$projectId = (int) ($project['id'] ?? 0);
$hasProjectSettings = $canUpdateProject || $canViewTaskStatuses;
$hasProjectHeaderActions = $hasProjectSettings || $canDeleteProject;

$priorityMap = [
    'urgent' => ['text' => 'Khẩn cấp', 'class' => 'priority-high'],
    'high' => ['text' => 'Cao', 'class' => 'priority-high'],
    'medium' => ['text' => 'Trung bình', 'class' => 'priority-medium'],
    'low' => ['text' => 'Thấp', 'class' => 'priority-low'],
];

$safeHexColor = static function (?string $color, string $fallback = '#64748b'): string {
    $color = trim((string) $color);
    if (!preg_match('/^#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color)) {
        return $fallback;
    }

    if (strlen($color) === 4) {
        return '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
    }

    return $color;
};

$hexToRgba = static function (string $color, float $alpha): string {
    $hex = ltrim($color, '#');
    $alpha = max(0, min(1, $alpha));

    return sprintf(
        'rgba(%d, %d, %d, %.2f)',
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
        $alpha
    );
};

$projectStatusColor = $safeHexColor($project['status_color'] ?? null);
$projectStatusSurfaceSoft = $hexToRgba($projectStatusColor, 0.05);
$projectStatusSurface = $hexToRgba($projectStatusColor, 0.10);
$projectStatusSurfaceStrong = $hexToRgba($projectStatusColor, 0.18);
$projectStatusSurfaceMesh = $hexToRgba($projectStatusColor, 0.26);

$formatDate = static function ($date, string $format = 'd/m/Y', string $fallback = '-'): string {
    $timestamp = !empty($date) ? strtotime((string) $date) : false;

    return $timestamp !== false ? date($format, $timestamp) : $fallback;
};

/**
 * Ánh xạ màu sắc và tên hiển thị cho vai trò thành viên
 */
$roleMap = [
    'manager' => ['text' => 'Manager', 'class' => 'role-pill-manager'],
    'member'  => ['text' => 'Member',  'class' => 'role-pill-member'],
    'viewer'  => ['text' => 'Viewer',  'class' => 'role-pill-viewer'],
];

$remainingDays = null;
$isOverdueProject = false;
$todayTs = strtotime(date('Y-m-d'));

if (!empty($project['due_date'])) {
    $dueTs = strtotime($project['due_date']);
    if ($dueTs !== false) {
        $remainingDays = (int) floor(($dueTs - $todayTs) / 86400);
        $isOverdueProject = $remainingDays < 0 && ($project['status_slug'] ?? '') !== 'completed';
    }
}
/**
 * Hàm closure để tạo URL ảnh đại diện.
 * Nếu người dùng có ảnh thực tế thì dùng ảnh đó, nếu không thì dùng UI Avatars.
 * 
 * @param array $person Dữ liệu người dùng/thành viên
 * @param string $nameKey Key chứa tên
 * @param string $avatarKey Key chứa tên file avatar
 * @return string URL ảnh
 */
$buildAvatar = static function (array $person, string $nameKey = 'name', string $avatarKey = 'avatar', int $size = 80): string {
    $avatar = $person[$avatarKey] ?? null;
    if (!empty($avatar) && file_exists(APPROOT . '/public/uploads/avatars/' . $avatar)) {
        return URLROOT . '/uploads/avatars/' . rawurlencode($avatar);
    }

    $name = $person[$nameKey] ?? 'User';
    return 'https://ui-avatars.com/api/?name=' . urlencode((string) $name) . '&background=E2E8F0&color=0F172A&rounded=true&size=' . $size;
};
?>

<style>
    /* Container chính cho trang chi tiết dự án */
    .project-detail-shell {
        min-height: 100%;
    }

    /* Card Header chứa thông tin tiêu đề và banner */
    .project-detail-header {
        background: #fff;
        border-radius: 1.5rem;
        box-shadow: none;
        overflow: hidden;
    }

    /* Banner trang trí phía trên cùng */
    .project-detail-banner {
        background:
            radial-gradient(circle at 98% 0%, var(--project-status-bg-mesh) 0, var(--project-status-bg-strong) 17%, transparent 36%),
            radial-gradient(circle at 88% 30%, var(--project-status-bg) 0, transparent 32%),
            radial-gradient(circle at 68% 82%, var(--project-status-bg-soft) 0, transparent 34%),
            linear-gradient(270deg, var(--project-status-bg-strong) 0%, var(--project-status-bg) 32%, #fbfdff 68%, #ffffff 100%);
        color: #fff;
        position: relative;
    }

    .project-detail-banner::after {
        content: "";
        position: absolute;
        inset: auto -8% -45% auto;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    /* Các thành phần panel và card thống kê */
    .project-stat-card,
    .project-panel {
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: none;
    }

    .project-stat-card {
        padding: 0.85rem 1.15rem;
        height: 100%;
    }

    .project-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .project-stat-icon svg {
        width: 18px;
        height: 18px;
    }

    .project-soft-blue { background: #dbeafe; color: #1d4ed8; }
    .project-soft-green { background: #dcfce7; color: #15803d; }
    .project-soft-rose { background: #ffe4e6; color: #e11d48; }
    .project-soft-violet { background: #ede9fe; color: #7c3aed; }

    /* Kiểu dáng cho tiêu đề và nhãn */
    .project-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
    }

    /* Thanh tiến độ dự án */
    .project-meta-label {
        color: #64748b;
        font-size: 0.75rem;
        line-height: 1.2;
    }

    .project-progress {
        height: 12px;
        background: #e2e8f0;
        border-radius: 999px;
        overflow: hidden;
    }

    .project-progress-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--project-status-color) 0%, var(--project-status-bg-mesh) 100%);
    }

    /* Tabs điều hướng (Tổng quan, Công việc) */
    .project-tabset {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.125rem;
        max-width: 100%;
        width: auto;
        padding: 0.25rem;
        overflow-x: visible;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #f8fafc;
    }

    .project-tabset .nav-item {
        flex: 0 0 auto;
    }

    .project-tabset .nav-link {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        min-height: 36px;
        border: 0;
        border-radius: 0.55rem;
        background: transparent;
        color: #64748b;
        padding: 0.45rem 0.75rem;
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1.2;
        white-space: nowrap;
    }

    .project-tabset .nav-link.active,
    .project-tabset .nav-link:hover {
        background: #ffffff;
        color: #0f172a;
    }

    .project-tabset .nav-link i,
    .project-tabset .nav-link svg {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
    }

    .project-tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.45rem;
        padding: 0.08rem 0.4rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.08);
        color: inherit;
        flex-shrink: 0;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .project-member-row:last-child,
    .project-timeline-item:last-child {
        border-bottom: 0;
    }

    /* Thẻ thành viên (tab Thành viên) */
    .project-member-card {
        position: relative;
        background: #f8fafc;
        border: 0 !important;
        border-radius: 0.875rem;
        transition: background-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    }

    .project-member-card:hover {
        background: #f1f5f9;
        box-shadow: none;
        transform: translateY(-1px);
    }

    .project-member-card-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px -4px rgba(15, 23, 42, 0.25);
    }

    .project-member-card-name {
        font-size: 0.9375rem;
        font-weight: 600;
        line-height: 1.25;
    }

    .project-member-card-email {
        font-size: 0.8125rem;
        color: #64748b;
        line-height: 1.3;
    }

    .project-member-role-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        max-width: 100%;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .project-member-card-meta {
        font-size: 0.75rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        flex-wrap: wrap;
        margin-top: auto;
    }

    .project-member-card-meta svg,
    .project-member-card-meta i {
        width: 13px;
        height: 13px;
        flex-shrink: 0;
    }

    .project-member-card-head {
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
        min-width: 0;
        padding-right: 1.75rem;
    }

    .project-member-card-media {
        display: flex;
        align-items: center;
        flex: 0 0 56px;
        flex-direction: column;
        gap: 0.45rem;
        min-width: 0;
    }

    .project-member-card-media .project-member-role-pill {
        max-width: 56px;
        min-height: 20px;
        padding-inline: 0.42rem;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .project-member-card-identity {
        min-width: 0;
        flex: 1 1 auto;
        padding-top: 0.25rem;
    }

    .project-member-card-edit {
        display: inline-grid;
        place-items: center;
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        color: var(--md-on-surface-variant);
        opacity: 0;
        pointer-events: none;
        text-decoration: none;
        transform: translateY(-2px);
        transition: background-color 0.15s ease, color 0.15s ease, opacity 0.15s ease, transform 0.15s ease;
    }

    .project-member-card:hover .project-member-card-edit,
    .project-member-card:focus-within .project-member-card-edit {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }

    .project-member-card-edit:hover,
    .project-member-card-edit:focus-visible {
        background: var(--md-surface);
        color: var(--md-primary);
        opacity: 1;
    }

    .project-member-card-edit svg,
    .project-member-card-edit i {
        width: 15px;
        height: 15px;
    }

    .project-member-card-date {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        min-width: 0;
    }

    .project-member-card-date span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .project-member-state-pill {
        display: inline-flex;
        align-items: center;
        max-width: 100%;
        padding: 0.18rem 0.5rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 650;
        line-height: 1.2;
        white-space: nowrap;
    }

    .project-member-state-pill.is-active {
        background: #dcfce7;
        color: #166534;
    }

    .project-member-state-pill.is-paused {
        background: #fef3c7;
        color: #92400e;
    }

    .project-member-state-pill.is-left {
        background: #e2e8f0;
        color: #475569;
    }

    .project-members-split {
        height: 0;
        margin: 1.25rem 0;
        border: 0;
        border-top: 1px dashed #cbd5e1;
    }

    .project-member-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
    }

    .project-description {
        line-height: 1.8;
        font-size: 0.95rem;
    }

    .project-banner-content {
        z-index: 1;
    }

    .project-banner-pill-outline {
        background: rgba(255,255,255,0.12);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.16);
    }

    .project-banner-pill-soft {
        background: rgba(255,255,255,0.16);
        color: #fff;
    }

    .project-date-compact {
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .project-task-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Thu nhỏ kích thước nhãn */
    .project-pill-sm {
        padding: 0.25rem 0.6rem;
        font-size: 0.725rem;
        line-height: 1;
    }

    .project-member-role-pill.role-pill-manager,
    .project-member-role-pill.role-pill-member,
    .project-member-role-pill.role-pill-viewer {
        border-width: 1px;
    }

    /* Định nghĩa màu sắc cho các Role Pills */
    .role-pill-manager {
        background: #fee2e2 !important;
        color: #b91c1c !important;
        border: 1px solid #fecaca !important;
    }
    .role-pill-member {
        background: #dcfce7 !important;
        color: #15803d !important;
        border: 1px solid #bbf7d0 !important;
    }
    .role-pill-viewer {
        background: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #e2e8f0 !important;
    }

    /* Khu vực nội dung chính của Tab */
    .project-main-tab-content {
        height: auto;
        overflow: visible;
        padding-right: 0;
        scrollbar-gutter: auto;
    }

    .project-member-picker {
        max-height: none;
    }

    .project-lead-avatar {
        width: 80px;
        height: 80px;
        border-radius: 1.25rem;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.65);
        box-shadow: 0 16px 28px -24px rgba(15, 23, 42, 0.45);
    }

    /* Bảng danh sách công việc */
    .project-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }

    .project-table td {
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Các thành phần Badge (Pill) */
    .project-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .project-pill svg {
        width: 16px;
        height: 16px;
    }

    .project-mini-note {
        font-size: 0.82rem;
        color: #64748b;
    }

    .btn-outline-delete {
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.4) !important;
    }

    .btn-outline-delete:hover {
        background-color: var(--red-600) !important;
        border-color: var(--red-600) !important;
        color: #ffffff !important;
    }

    /* Material 3 productivity refinement */
    .project-detail-header,
    .project-stat-card,
    .project-panel {
        border: 0;
        border-radius: var(--radius-lg);
        box-shadow: none;
    }

    .project-detail-header,
    .project-panel {
        background: var(--md-surface);
    }

    .project-detail-header {
        background:
            radial-gradient(circle at 96% 6%, var(--project-status-bg-mesh) 0, var(--project-status-bg-strong) 16%, transparent 34%),
            radial-gradient(circle at 86% 34%, var(--project-status-bg) 0, transparent 32%),
            radial-gradient(circle at 70% 84%, var(--project-status-bg-soft) 0, transparent 34%),
            linear-gradient(270deg, var(--project-status-bg-strong) 0%, var(--project-status-bg) 32%, #fbfdff 68%, #ffffff 100%);
    }

    .project-stat-card {
        background: #f8fafc;
        box-shadow: none;
    }

    .project-stat-card:has(.project-soft-blue) {
        background: #eff6ff;
    }

    .project-stat-card:has(.project-soft-violet) {
        background: #f5f3ff;
    }

    .project-stat-card:has(.project-soft-rose) {
        background: #fff1f2;
    }

    .project-stat-card:has(.project-soft-green) {
        background: #f0fdf4;
    }

    .project-detail-banner {
        background:
            radial-gradient(circle at 98% 0%, var(--project-status-bg-mesh) 0, var(--project-status-bg-strong) 17%, transparent 36%),
            radial-gradient(circle at 88% 30%, var(--project-status-bg) 0, transparent 32%),
            radial-gradient(circle at 68% 82%, var(--project-status-bg-soft) 0, transparent 34%),
            linear-gradient(270deg, var(--project-status-bg-strong) 0%, var(--project-status-bg) 32%, #fbfdff 68%, #ffffff 100%);
        color: var(--md-on-surface);
        border-bottom: 0;
        padding: 1.25rem !important;
    }

    .project-detail-banner::after {
        display: none;
    }

    .project-stats-section {
        background: var(--md-surface);
    }

    .project-banner-pill-outline,
    .project-banner-pill-soft {
        background: var(--project-status-bg) !important;
        border-color: var(--project-status-bg-strong);
        color: var(--md-on-surface-variant);
    }

    .project-status-pill {
        background: var(--project-status-bg-strong) !important;
        border: 1px solid var(--project-status-color) !important;
        color: var(--project-status-color) !important;
    }

    .project-edit-button {
        background: var(--md-surface) !important;
        border: 1px solid var(--md-outline) !important;
        color: var(--md-on-surface) !important;
    }

    .project-edit-button:hover {
        background: var(--md-surface-container-low) !important;
        border-color: var(--md-outline) !important;
    }

    .project-action-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        padding: 0;
        border-radius: 50%;
        background: transparent !important;
        border: 1px solid transparent !important;
        color: var(--md-on-surface) !important;
    }

    .project-action-trigger:hover,
    .project-action-trigger.show {
        background: var(--md-surface-container-low) !important;
        border-color: var(--md-outline) !important;
    }

    .project-action-trigger i,
    .project-action-trigger svg {
        width: 20px;
        height: 20px;
    }

    .project-action-menu {
        min-width: 260px;
        padding: 0.45rem;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--radius-md);
        box-shadow: var(--md-shadow-2);
    }

    .project-action-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-height: 40px;
        border-radius: var(--radius-sm);
        color: var(--md-on-surface);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .project-action-item i,
    .project-action-item svg {
        width: 17px;
        height: 17px;
        color: var(--md-on-surface-variant);
    }

    .project-action-item-danger,
    .project-action-item-danger i,
    .project-action-item-danger svg {
        color: var(--md-error);
    }

    .project-detail-banner h1 {
        color: var(--md-on-surface);
        font-size: 1.25rem;
        font-weight: 500 !important;
        line-height: 1.35;
        margin-bottom: 0.75rem !important;
    }

    .project-lead-avatar {
        border-color: var(--md-surface);
        border-radius: 999px;
        box-shadow: var(--md-shadow-1);
    }

    .project-section-title {
        color: var(--md-on-surface);
        font-size: 1rem;
        font-weight: 500;
    }

    .project-meta-label,
    .project-mini-note,
    .project-member-card-email,
    .project-member-card-meta {
        color: var(--md-on-surface-variant);
    }

    .project-progress {
        background: var(--md-surface-container);
        height: 8px;
    }

    .project-progress-bar {
        background: var(--project-status-color);
    }

    .project-tabset {
        background: var(--md-surface-container-low);
        border-color: var(--md-outline-variant);
        gap: 0.125rem;
    }

    .project-tabset .nav-link {
        color: var(--md-on-surface-variant);
    }

    .project-tabset .nav-link:hover {
        background: var(--md-surface);
        color: var(--md-on-surface);
    }

    .project-tabset .nav-link.active {
        background: var(--project-status-bg);
        color: var(--project-status-color);
        box-shadow: inset 0 0 0 1px var(--project-status-bg-strong);
    }

    .project-tabset .nav-link.active .project-tab-count {
        background: var(--project-status-bg-strong);
    }

    .project-tab-count {
        background: var(--md-surface);
    }

    .project-settings-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.875rem;
    }

    .project-settings-card {
        display: flex;
        align-items: center;
        gap: 0.875rem;
        min-height: 104px;
        width: 100%;
        padding: 1rem;
        border: 1px solid var(--md-outline-variant);
        border-radius: var(--radius-md);
        background: var(--md-surface-container-low);
        color: var(--md-on-surface);
        text-decoration: none;
        box-shadow: none;
        transition: background-color 0.16s ease, border-color 0.16s ease, transform 0.16s ease;
    }

    .project-settings-card:hover,
    .project-settings-card:focus-visible {
        background: var(--md-surface);
        border-color: var(--project-status-color, var(--md-primary));
        color: var(--md-on-surface);
        transform: translateY(-1px);
    }

    button.project-settings-card {
        border-style: solid;
        cursor: pointer;
        font: inherit;
        text-align: left;
    }

    .project-settings-card-icon {
        display: grid;
        place-items: center;
        flex: 0 0 42px;
        width: 42px;
        height: 42px;
        border-radius: var(--radius-sm);
        background: var(--project-status-bg, var(--md-primary-container));
        color: var(--project-status-color, var(--md-primary));
    }

    .project-settings-card-icon i,
    .project-settings-card-icon svg,
    .project-settings-card-action i,
    .project-settings-card-action svg {
        width: 18px;
        height: 18px;
    }

    .project-settings-card-content {
        min-width: 0;
        flex: 1 1 auto;
    }

    .project-settings-card-title {
        display: block;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.3;
    }

    .project-settings-card-note {
        display: block;
        margin-top: 0.25rem;
        color: var(--md-on-surface-variant);
        font-size: 0.8125rem;
        line-height: 1.35;
    }

    .project-settings-card-action {
        display: grid;
        place-items: center;
        flex: 0 0 32px;
        width: 32px;
        height: 32px;
        margin-left: auto;
        border-radius: 50%;
        color: var(--md-on-surface-variant);
        background: var(--md-surface);
    }

    .project-settings-empty {
        border: 1px dashed var(--md-outline-variant);
        border-radius: var(--radius-md);
        background: var(--md-surface-container-low);
        color: var(--md-on-surface-variant);
        padding: 1.25rem;
        text-align: center;
    }

    .project-member-card {
        background: #f8fafc;
        border: 0 !important;
        border-radius: var(--radius-md);
        box-shadow: none;
    }

    .project-member-card:hover {
        background: #f1f5f9;
        box-shadow: none;
    }

    .project-table-card {
        background: var(--md-content-surface);
        border: 0 !important;
        border-radius: var(--radius-md);
        box-shadow: none;
    }

    .project-table-card .table {
        margin-bottom: 0;
    }

    .project-table-list {
        overflow: visible;
    }

    .project-table-fixed {
        width: 100%;
        table-layout: fixed;
    }

    .project-table-fixed th,
    .project-table-fixed td {
        overflow: hidden;
        vertical-align: middle;
    }

    .project-table-ellipsis {
        display: block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .project-table-person {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
    }

    .project-table-person-name {
        min-width: 0;
        overflow: hidden;
        color: var(--md-on-surface-variant);
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .project-table-fixed .project-pill {
        display: inline-block;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .project-members-list {
        background: transparent;
        border-radius: 0;
    }

    .project-table th {
        background: var(--md-surface-container-low);
        border-bottom-color: var(--md-outline-variant);
        color: var(--md-on-surface-variant);
        font-weight: 500;
    }

    .project-table td {
        border-bottom-color: var(--md-outline-variant);
    }

    .btn-outline-delete {
        background: var(--md-surface);
        border-color: var(--md-outline) !important;
        color: var(--md-error) !important;
    }

    .btn-outline-delete:hover {
        background: var(--md-error-container) !important;
        border-color: var(--md-error-container) !important;
        color: var(--md-error) !important;
    }

    @media (max-width: 991.98px) {
        .project-detail-shell {
            margin: -1rem;
            padding: 1rem;
        }

        .project-settings-grid {
            grid-template-columns: 1fr;
        }

        .project-settings-card {
            min-height: auto;
        }
    }
</style>

<div class="project-detail-shell" style="--project-status-color: <?= htmlspecialchars($projectStatusColor, ENT_QUOTES, 'UTF-8') ?>; --project-status-bg-soft: <?= htmlspecialchars($projectStatusSurfaceSoft, ENT_QUOTES, 'UTF-8') ?>; --project-status-bg: <?= htmlspecialchars($projectStatusSurface, ENT_QUOTES, 'UTF-8') ?>; --project-status-bg-strong: <?= htmlspecialchars($projectStatusSurfaceStrong, ENT_QUOTES, 'UTF-8') ?>; --project-status-bg-mesh: <?= htmlspecialchars($projectStatusSurfaceMesh, ENT_QUOTES, 'UTF-8') ?>;">
    <!-- Đường dẫn Breadcrumb -->
    <div class="page-toolbar">
        <div class="d-flex align-items-center text-slate-600 fs-6">
            <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
            <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
            <span class="page-title"><?= htmlspecialchars((string)($project['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <!-- Khu vực Header Dự án (Banner & Tóm tắt) -->
    <section class="project-detail-header mb-4" style="--project-status-color: <?= htmlspecialchars($projectStatusColor, ENT_QUOTES, 'UTF-8') ?>; --project-status-bg-soft: <?= htmlspecialchars($projectStatusSurfaceSoft, ENT_QUOTES, 'UTF-8') ?>; --project-status-bg: <?= htmlspecialchars($projectStatusSurface, ENT_QUOTES, 'UTF-8') ?>; --project-status-bg-strong: <?= htmlspecialchars($projectStatusSurfaceStrong, ENT_QUOTES, 'UTF-8') ?>; --project-status-bg-mesh: <?= htmlspecialchars($projectStatusSurfaceMesh, ENT_QUOTES, 'UTF-8') ?>;">
        <div class="project-detail-banner p-4 p-lg-5">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 position-relative project-banner-content">
                <div class="pe-xl-4">
                    <h1 class="h2 fw-bold mb-3"><?= htmlspecialchars((string)($project['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="project-pill project-banner-pill-outline">
                            <?= htmlspecialchars((string)($project['project_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="project-pill project-banner-pill-soft project-status-pill">
                            <?= htmlspecialchars((string)($project['status_name'] ?? 'Không rõ'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

                <!-- Các nút hành động chính -->
                <div class="d-flex flex-wrap align-items-start gap-2 flex-shrink-0">
                    <?php if ($hasProjectHeaderActions): ?>
                    <div class="dropdown">
                        <button
                            type="button"
                            class="btn project-action-trigger"
                            id="projectActionMenu"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            aria-label="Thao tác dự án">
                            <i data-lucide="more-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end project-action-menu" aria-labelledby="projectActionMenu">
                            <?php if ($canUpdateProject): ?>
                            <li>
                                <a href="<?= URLROOT ?>/projects/<?= $projectId ?>/edit" class="dropdown-item project-action-item">
                                    <i data-lucide="pencil"></i>
                                    <span>Chỉnh sửa</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($canViewTaskStatuses): ?>
                            <li>
                                <a href="<?= URLROOT ?>/settings/task?project_id=<?= $projectId ?>" class="dropdown-item project-action-item">
                                    <i data-lucide="list-todo"></i>
                                    <span>Trạng thái công việc</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if ($canUpdateProject): ?>
                            <li>
                                <button type="button" class="dropdown-item project-action-item" data-project-settings-open-tab="#members-tab">
                                    <i data-lucide="users-round"></i>
                                    <span>Quản lý thành viên</span>
                                </button>
                            </li>
                            <?php endif; ?>
                            <?php if ($canDeleteProject): ?>
                                <?php if ($canUpdateProject || $canViewTaskStatuses): ?>
                                <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                            <li>
                                <button
                                    type="button"
                                    class="dropdown-item project-action-item project-action-item-danger"
                                    onclick="showDeleteModal('<?= URLROOT ?>/projects/<?= $projectId ?>/delete', <?= htmlspecialchars((string) json_encode('Bạn có chắc chắn muốn xóa dự án ' . (string) ($project['name'] ?? '') . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i data-lucide="trash-2"></i>
                                    <span>Xóa</span>
                                </button>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="project-stats-section p-4 p-lg-5">
            <!-- Hàng thông số thống kê dự án -->
            <div class="row g-3 g-lg-4">
                <!-- Ô: Thời hạn và Ngày còn lại -->
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-blue">
                                <i data-lucide="calendar-clock"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="project-meta-label">Thời hạn & Còn lại</div>
                                <div class="fw-bold text-slate-900 project-date-compact">
                                    <?= $formatDate($project['start_date'] ?? null, 'd/m', '??') ?> - <?= $formatDate($project['due_date'] ?? null, 'd/m/Y', '??') ?>
                                </div>
                                <div class="small fw-semibold <?= $isOverdueProject ? 'text-danger' : 'text-primary' ?>">
                                    <?php if ($remainingDays === null): ?>Hạn chưa xác định<?php elseif ($remainingDays >= 0): ?>Còn <?= $remainingDays ?> ngày<?php else: ?>Trễ <?= abs($remainingDays) ?> ngày<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ô: Project Sponsor -->
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-violet">
                                <i data-lucide="user-check"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="project-meta-label">Project Sponsor</div>
                                <div class="fw-bold text-slate-900 text-truncate" style="font-size: 1.05rem;" title="<?= htmlspecialchars((string)($project['owner_name'] ?? 'Chưa xác định'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($project['owner_name'] ?? 'Chưa xác định'), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ô: Tổng số công việc -->
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-rose">
                                <i data-lucide="list-checks"></i>
                            </div>
                            <div>
                                <div class="project-meta-label">Tổng công việc</div>
                                <div class="fs-4 fw-bold text-slate-900"><?= $stats['total'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ô: Phần trăm tiến độ -->
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-green">
                                <i data-lucide="activity"></i>
                            </div>
                            <div>
                                <div class="project-meta-label">Tiến độ dự án</div>
                                <div class="fs-4 fw-bold text-slate-900"><?= $stats['percent'] ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <!-- Cột Nội dung chính (Tabs) -->
        <div class="col-12">
            <div class="project-panel p-4 p-lg-5">
                <!-- Danh sách Tab -->
                <ul class="nav project-tabset mb-4" id="projectDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="members-tab" data-bs-toggle="tab" data-bs-target="#members-pane" type="button" role="tab">
                            <i data-lucide="users-round"></i>
                            <span>Thành viên</span>
                            <span class="project-tab-count"><?= count($members) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-pane" type="button" role="tab">
                            <i data-lucide="list-checks"></i>
                            <span>Công việc</span>
                            <span class="project-tab-count"><?= (int) ($stats['total'] ?? count($tasks)) ?></span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-pane" type="button" role="tab">
                            <i data-lucide="file-text"></i>
                            <span>Tổng quan</span>
                        </button>
                    </li>
                    <?php if ($hasProjectSettings): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings-pane" type="button" role="tab">
                            <i data-lucide="settings-2"></i>
                            <span>Thiết lập</span>
                        </button>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Nội dung tương ứng của từng Tab -->
                <div class="tab-content project-main-tab-content">
                    <!-- Tab: Thành viên -->
                    <div class="tab-pane fade show active" id="members-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <div class="project-mini-note">Danh sách nhân sự đang thực hiện dự án này.</div>
                            </div>
                            <?php if ($canUpdateProject): ?>
                            <a href="<?= URLROOT ?>/projects/<?= $projectId ?>/members/create" class="btn btn-sm btn-primary px-3 shadow-sm">
                                <i data-lucide="user-plus"></i>
                                <span>Thêm thành viên</span>
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="project-table-card project-members-list">
                            <?php if (!empty($members)): ?>
                                <?php
                                $managers = array_values(array_filter($members, static function ($m) {
                                    return ($m['role'] ?? '') === 'manager';
                                }));
                                $others = array_values(array_filter($members, static function ($m) {
                                    return ($m['role'] ?? '') !== 'manager';
                                }));

                                $renderMemberCard = static function (array $member) use ($roleMap, $buildAvatar, $formatDate, $canUpdateProject, $projectId): void {
                                    $roleSlug = $member['role'] ?? 'member';
                                    $roleInfo = $roleMap[$roleSlug] ?? [
                                        'text' => is_string($roleSlug) ? ucfirst((string) $roleSlug) : 'Member',
                                        'class' => 'role-pill-member',
                                    ];
                                    $memberUserId = (int) ($member['id'] ?? $member['user_id'] ?? 0);
                                    $isActiveMember = (int) ($member['is_active'] ?? 1) === 1 && empty($member['left_at']);
                                    if (!empty($member['left_at'])) {
                                        $memberState = ['text' => 'Đã rời', 'class' => 'is-left'];
                                    } elseif ($isActiveMember) {
                                        $memberState = ['text' => 'Đang tham gia', 'class' => 'is-active'];
                                    } else {
                                        $memberState = ['text' => 'Tạm dừng', 'class' => 'is-paused'];
                                    }
                                    ?>
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="project-member-card p-3 h-100 d-flex flex-column">
                                            <div class="project-member-card-head">
                                                <div class="project-member-card-media">
                                                    <img src="<?= htmlspecialchars($buildAvatar($member, 'name', 'avatar', 80), ENT_QUOTES, 'UTF-8') ?>" alt="" class="project-member-card-avatar" width="46" height="46">
                                                    <span class="project-member-role-pill <?= htmlspecialchars($roleInfo['class'], ENT_QUOTES, 'UTF-8') ?>">
                                                        <?= htmlspecialchars($roleInfo['text'], ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </div>
                                                <div class="project-member-card-identity">
                                                    <div class="project-member-card-name text-slate-900 text-truncate">
                                                        <a href="<?= URLROOT ?>/users/<?= (int) ($member['id'] ?? 0) ?>" class="text-decoration-none text-slate-900 hover-text-primary">
                                                            <?= htmlspecialchars((string) ($member['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                        </a>
                                                    </div>
                                                    <?php if (!empty($member['email'])): ?>
                                                        <div class="project-member-card-email text-truncate" title="<?= htmlspecialchars((string) $member['email'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <?= htmlspecialchars((string) $member['email'], ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php if ($canUpdateProject && $memberUserId > 0): ?>
                                                <a href="<?= URLROOT ?>/projects/<?= (int) $projectId ?>/members/<?= $memberUserId ?>/edit" class="project-member-card-edit" aria-label="Chỉnh sửa thành viên">
                                                    <i data-lucide="pencil"></i>
                                                </a>
                                            <?php endif; ?>
                                            <div class="project-member-card-meta mt-3 pt-2 border-top border-slate-200">
                                                <span class="project-member-state-pill <?= htmlspecialchars($memberState['class'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars($memberState['text'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <span class="project-member-card-date">
                                                    <i data-lucide="calendar" aria-hidden="true"></i>
                                                    <span><?= !empty($member['joined_at']) ? 'Tham gia ' . $formatDate($member['joined_at']) : 'Chưa có ngày tham gia' ?></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                };
                                ?>

                                <?php if (!empty($managers)): ?>
                                    <div class="row g-3">
                                        <?php foreach ($managers as $member) {
                                            $renderMemberCard($member);
                                        } ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($managers) && !empty($others)): ?>
                                    <hr class="project-members-split" role="presentation">
                                <?php endif; ?>

                                <?php if (!empty($others)): ?>
                                    <div class="row g-3 <?= !empty($managers) ? 'mt-0' : '' ?>">
                                        <?php foreach ($others as $member) {
                                            $renderMemberCard($member);
                                        } ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-slate-500">Dự án này hiện chưa có thành viên nào tham gia.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tab: Danh sách Công việc (Chi tiết bảng) -->
                    <div class="tab-pane fade" id="tasks-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <div class="project-mini-note">Quản lý và theo dõi các đầu việc chi tiết.</div>
                            </div>
                            <?php if ($canCreateTask): ?>
                            <a href="<?= URLROOT ?>/tasks/create?project_id=<?= (int) ($project['id'] ?? 0) ?>" class="btn btn-sm btn-primary px-3 shadow-sm">
                                <i data-lucide="plus"></i>
                                <span>Thêm công việc</span>
                            </a>
                            <?php endif; ?>
                        </div>

                        <div class="project-table-card overflow-hidden">
                            <div class="project-table-list">
                                <table class="table table-custom align-middle project-table-fixed">
                                    <colgroup>
                                        <col style="width: 36%;">
                                        <col style="width: 22%;">
                                        <col style="width: 17%;">
                                        <col style="width: 12%;">
                                        <col style="width: 13%;">
                                    </colgroup>
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-4 py-3">Tên công việc</th>
                                            <th class="px-4 py-3">Người phụ trách</th>
                                            <th class="px-4 py-3">Trạng thái</th>
                                            <th class="px-4 py-3">Ưu tiên</th>
                                            <th class="px-4 py-3">Hạn chót</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($tasks)): ?>
                                            <?php foreach ($tasks as $task): ?>
                                                <?php
                                                $taskStatusColor = $safeHexColor($task['status_color'] ?? null);
                                                $taskStatusText = $task['status_name'] ?? $task['status_slug'] ?? 'Không rõ';
                                                $taskPriority = $priorityMap[$task['priority'] ?? 'low'] ?? ['text' => $task['priority'] ?? 'Thấp', 'class' => 'status-muted'];
                                                $taskDone = !empty($task['status_is_done']) || ($task['status_slug'] ?? '') === 'done';
                                                $taskDueTs = !empty($task['due_date']) ? strtotime((string) $task['due_date']) : false;
                                                $isTaskOverdue = $taskDueTs !== false && $taskDueTs < $todayTs && !$taskDone;
                                                ?>
                                                <tr>
                                                    <td class="px-4 py-3">
                                                        <div class="fw-semibold text-slate-900 project-table-ellipsis" title="<?= htmlspecialchars((string) $task['title'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <?= htmlspecialchars((string) $task['title'], ENT_QUOTES, 'UTF-8') ?>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="project-table-person">
                                                            <img src="<?= htmlspecialchars($buildAvatar(['name' => $task['assigned_name'] ?? 'Chưa giao', 'avatar' => $task['assigned_avatar'] ?? null], 'name', 'avatar', 36), ENT_QUOTES, 'UTF-8') ?>" alt="avatar" class="project-task-avatar">
                                                            <span class="project-table-person-name" title="<?= htmlspecialchars((string) ($task['assigned_name'] ?? 'Chưa giao'), ENT_QUOTES, 'UTF-8') ?>">
                                                                <?= htmlspecialchars((string) ($task['assigned_name'] ?? 'Chưa giao'), ENT_QUOTES, 'UTF-8') ?>
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="project-pill" title="<?= htmlspecialchars((string) $taskStatusText, ENT_QUOTES, 'UTF-8') ?>" style="border-left: 4px solid <?= htmlspecialchars($taskStatusColor, ENT_QUOTES, 'UTF-8') ?>; background-color: <?= htmlspecialchars($taskStatusColor, ENT_QUOTES, 'UTF-8') ?>15; color: <?= htmlspecialchars($taskStatusColor, ENT_QUOTES, 'UTF-8') ?>;">
                                                            <?= htmlspecialchars((string) $taskStatusText, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="project-pill <?= htmlspecialchars($taskPriority['class'], ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars((string) $taskPriority['text'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <?= htmlspecialchars($taskPriority['text'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 <?= $isTaskOverdue ? 'text-danger fw-semibold' : 'text-slate-700' ?>">
                                                        <?= $taskDueTs !== false ? date('d/m/Y', $taskDueTs) : '-' ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="px-4 py-5 text-center text-slate-500">Dự án này chưa có công việc nào.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Tổng quan -->
                    <div class="tab-pane fade" id="overview-pane" role="tabpanel">
                        <div class="text-slate-600 project-description">
                            <?= nl2br(htmlspecialchars((string)($project['description'] ?? 'Dự án này hiện chưa có thông tin mô tả chi tiết.'), ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>

                    <?php if ($hasProjectSettings): ?>
                    <!-- Tab: Thiết lập -->
                    <div class="tab-pane fade" id="settings-pane" role="tabpanel">
                        <div class="d-flex flex-column gap-1 mb-4">
                            <div class="project-section-title">Thiết lập dự án</div>
                            <div class="project-mini-note">Các cấu hình liên quan đến dự án được gom tại đây để dễ thao tác.</div>
                        </div>

                        <div class="project-settings-grid">
                            <?php if ($canUpdateProject): ?>
                            <a href="<?= URLROOT ?>/projects/<?= $projectId ?>/edit" class="project-settings-card">
                                <span class="project-settings-card-icon">
                                    <i data-lucide="pencil"></i>
                                </span>
                                <span class="project-settings-card-content">
                                    <span class="project-settings-card-title">Thông tin dự án</span>
                                    <span class="project-settings-card-note">Cập nhật tên, Project Sponsor, trạng thái và thời hạn.</span>
                                </span>
                                <span class="project-settings-card-action">
                                    <i data-lucide="arrow-up-right"></i>
                                </span>
                            </a>

                            <button type="button" class="project-settings-card" data-project-settings-open-tab="#members-tab">
                                <span class="project-settings-card-icon">
                                    <i data-lucide="users-round"></i>
                                </span>
                                <span class="project-settings-card-content">
                                    <span class="project-settings-card-title">Nhân viên dự án</span>
                                    <span class="project-settings-card-note">Mở danh sách thành viên và thêm nhân sự khi cần.</span>
                                </span>
                                <span class="project-settings-card-action">
                                    <i data-lucide="chevron-right"></i>
                                </span>
                            </button>
                            <?php endif; ?>

                            <?php if ($canViewTaskStatuses): ?>
                            <a href="<?= URLROOT ?>/settings/task?project_id=<?= $projectId ?>" class="project-settings-card">
                                <span class="project-settings-card-icon">
                                    <i data-lucide="list-todo"></i>
                                </span>
                                <span class="project-settings-card-content">
                                    <span class="project-settings-card-title">Trạng thái công việc</span>
                                    <span class="project-settings-card-note">Mở trang trạng thái công việc với dự án hiện tại được chọn sẵn.</span>
                                </span>
                                <span class="project-settings-card-action">
                                    <i data-lucide="arrow-up-right"></i>
                                </span>
                            </a>
                            <?php endif; ?>

                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Xác nhận xóa -->
    <?php if ($canDeleteProject): ?>
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

    <!-- Modal Thêm thành viên -->
    <?php if ($canUpdateProject): ?>
    <div class="modal fade" id="addMembersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-bottom-0 pt-4 px-4">
                    <h5 class="fw-bold text-slate-900 mb-0">Thêm thành viên mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= URLROOT ?>/projects/<?= (int) ($project['id'] ?? 0) ?>/addMembers" method="POST">
                    <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                    <div class="modal-body px-4 py-3">
                        <div class="mb-4">
                            <label class="form-label">Quyền hạn trong dự án</label>
                            <select name="role" class="form-select">
                                <option value="manager">Quản lý (Manager)</option>
                                <option value="member" selected>Thành viên (Member)</option>
                                <option value="viewer">Người quan sát (Viewer)</option>
                            </select>
                        </div>
                        
                        <div class="project-section-title mb-2">Chọn nhân viên (có thể chọn nhiều)</div>
                        <div class="project-table-card border rounded-3 project-member-picker">
                            <table class="table table-custom align-middle project-table-fixed">
                                <colgroup>
                                    <col style="width: 8%;">
                                    <col style="width: 62%;">
                                    <col style="width: 30%;">
                                </colgroup>
                                <thead class="sticky-top bg-slate-50 shadow-sm sticky-layer">
                                    <tr class="border-bottom">
                                        <th class="col-check"></th>
                                        <th>Thông tin nhân viên</th>
                                        <th>Chức danh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allUsers as $u): ?>
                                        <?php 
                                        $isAlreadyMember = false;
                                        foreach($members as $m) { if((int)$m['id'] === (int)$u['id']) { $isAlreadyMember = true; break; } }
                                        if(!$isAlreadyMember):
                                        ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="user_ids[]" value="<?= (int) ($u['id'] ?? 0) ?>" class="form-check-input">
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-slate-900 project-table-ellipsis" title="<?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="project-mini-note project-table-ellipsis"><?= htmlspecialchars((string) ($u['employee_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string) ($u['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                </td>
                                                <td>
                                                    <div class="small text-slate-600 project-table-ellipsis" title="<?= htmlspecialchars((string) ($u['job_title'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($u['job_title'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4 px-4">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">
                            <i data-lucide="check-circle"></i>
                            <span>Xác nhận thêm</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-project-settings-open-tab]').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    var target = document.querySelector(trigger.getAttribute('data-project-settings-open-tab'));
                    if (!target || !window.bootstrap || !window.bootstrap.Tab) {
                        return;
                    }

                    window.bootstrap.Tab.getOrCreateInstance(target).show();
                });
            });
        });
    </script>
</div>

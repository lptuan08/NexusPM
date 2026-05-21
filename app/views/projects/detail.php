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
        box-shadow: 0 20px 45px -32px rgba(15, 23, 42, 0.35);
        overflow: hidden;
    }

    /* Banner trang trí phía trên cùng */
    .project-detail-banner {
        background:
            linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #38bdf8 100%);
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
        box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.4);
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
        background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%);
    }

    /* Tabs điều hướng (Tổng quan, Công việc) */
    .project-tabset {
        border-bottom: 1px solid #e2e8f0;
        gap: 1rem;
    }

    .project-tabset .nav-link {
        border: 0;
        border-bottom: 2px solid transparent;
        border-radius: 0;
        background: transparent;
        color: #64748b;
        padding: 0.9rem 0.25rem;
        font-weight: 600;
    }

    .project-tabset .nav-link.active,
    .project-tabset .nav-link:hover {
        color: #0f172a;
        border-bottom-color: #2563eb;
    }

    .project-member-row:last-child,
    .project-timeline-item:last-child {
        border-bottom: 0;
    }

    /* Thẻ thành viên (tab Thành viên) */
    .project-member-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.875rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .project-member-card:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 10px 28px -22px rgba(15, 23, 42, 0.35);
    }

    .project-member-card-avatar {
        width: 40px;
        height: 40px;
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
    }

    .project-member-card-meta svg,
    .project-member-card-meta i {
        width: 13px;
        height: 13px;
        flex-shrink: 0;
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
        background: #dbeafe !important;
        color: #1e40af !important;
        border: 1px solid #bfdbfe !important;
    }
    .role-pill-member {
        background: #f1f5f9 !important;
        color: #475569 !important;
        border: 1px solid #e2e8f0 !important;
    }
    .role-pill-viewer {
        background: #f0fdf4 !important;
        color: #15803d !important;
        border: 1px solid #bbf7d0 !important;
    }

    /* Khu vực cuộn nội dung chính của Tab */
    .project-main-tab-content {
        height: 600px; /* Độ cao cố định tối ưu cho dashboard */
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 10px;
        scrollbar-gutter: stable;
    }

    .project-main-tab-content::-webkit-scrollbar {
        width: 6px;
    }

    .project-main-tab-content::-webkit-scrollbar-thumb {
        background-color: #e2e8f0;
        border-radius: 10px;
    }

    .project-member-picker {
        max-height: 400px;
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

    @media (max-width: 991.98px) {
        .project-detail-shell {
            margin: -1rem;
            padding: 1rem;
        }
    }
</style>

<div class="project-detail-shell">
    <!-- Đường dẫn Breadcrumb -->
    <div class="page-toolbar">
        <div class="d-flex align-items-center text-slate-600 fs-6">
            <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
            <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
            <span class="page-title"><?= htmlspecialchars((string)($project['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <!-- Khu vực Header Dự án (Banner & Tóm tắt) -->
    <section class="project-detail-header mb-4">
        <div class="project-detail-banner p-4 p-lg-5">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 position-relative project-banner-content">
                <div class="pe-xl-4">
                    <h1 class="h2 fw-bold mb-3"><?= htmlspecialchars((string)($project['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="project-pill project-banner-pill-outline">
                            <?= htmlspecialchars((string)($project['project_code'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="project-pill project-banner-pill-soft" style="border-left: 4px solid <?= htmlspecialchars($safeHexColor($project['status_color'] ?? null), ENT_QUOTES, 'UTF-8') ?>; background: rgba(255,255,255,0.1);">
                            <?= htmlspecialchars((string)($project['status_name'] ?? 'Không rõ'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

                <!-- Các nút hành động chính -->
                <div class="d-flex flex-wrap align-items-start gap-2 flex-shrink-0">
                    <?php if ($canUpdateProject): ?>
                    <a href="<?= URLROOT ?>/projects/<?= (int) ($project['id'] ?? 0) ?>/edit" class="btn btn-light fw-semibold px-3 px-lg-4">
                        <i data-lucide="pencil"></i>
                        <span>Chỉnh sửa</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($canDeleteProject): ?>
                    <button
                        type="button"
                        class="btn btn-outline-delete fw-semibold px-3 px-lg-4"
                        onclick="showDeleteModal('<?= URLROOT ?>/projects/<?= (int) ($project['id'] ?? 0) ?>/delete', <?= htmlspecialchars((string) json_encode('Bạn có chắc chắn muốn xóa dự án ' . (string) ($project['name'] ?? '') . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                        <i data-lucide="trash-2"></i>
                        <span>Xóa</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="p-4 p-lg-5">
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
                <!-- Ô: Trưởng dự án -->
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
                            Thành viên
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-pane" type="button" role="tab">
                            Công việc
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-pane" type="button" role="tab">
                            Tổng quan
                        </button>
                    </li>
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
                            <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMembersModal">
                                <i data-lucide="user-plus"></i>
                                <span>Thêm thành viên</span>
                            </button>
                            <?php endif; ?>
                        </div>

                        <div class="project-table-card">
                            <?php if (!empty($members)): ?>
                                <?php
                                $managers = array_values(array_filter($members, static function ($m) {
                                    return ($m['role'] ?? '') === 'manager';
                                }));
                                $others = array_values(array_filter($members, static function ($m) {
                                    return ($m['role'] ?? '') !== 'manager';
                                }));

                                $renderMemberCard = static function (array $member) use ($roleMap, $buildAvatar, $formatDate): void {
                                    $roleSlug = $member['role'] ?? 'member';
                                    $roleInfo = $roleMap[$roleSlug] ?? [
                                        'text' => is_string($roleSlug) ? ucfirst((string) $roleSlug) : 'Member',
                                        'class' => 'role-pill-member',
                                    ];
                                    ?>
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="project-member-card p-3 h-100 d-flex flex-column">
                                            <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
                                                <img src="<?= htmlspecialchars($buildAvatar($member, 'name', 'avatar', 80), ENT_QUOTES, 'UTF-8') ?>" alt="" class="project-member-card-avatar" width="40" height="40">
                                                <div class="flex-grow-1 min-w-0 d-flex flex-column gap-1">
                                                    <div class="d-flex align-items-start justify-content-between gap-2 min-w-0">
                                                        <div class="min-w-0 flex-grow-1">
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
                                                        <span class="project-member-role-pill <?= htmlspecialchars($roleInfo['class'], ENT_QUOTES, 'UTF-8') ?>">
                                                            <?= htmlspecialchars($roleInfo['text'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="project-member-card-meta mt-2 pt-2 border-top border-slate-200">
                                                <i data-lucide="calendar" aria-hidden="true"></i>
                                                <span><?= !empty($member['joined_at']) ? 'Tham gia ' . $formatDate($member['joined_at']) : 'Chưa có ngày tham gia' ?></span>
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
                                <div class="project-section-title mb-1">Công việc dự án</div>
                                <div class="project-mini-note">Quản lý và theo dõi các đầu việc chi tiết.</div>
                            </div>
                            <a href="<?= URLROOT ?>/tasks/create?project_id=<?= (int) ($project['id'] ?? 0) ?>" class="btn btn-sm btn-primary px-3 shadow-sm">
                                <i data-lucide="plus"></i>
                                <span>Thêm công việc</span>
                            </a>
                        </div>

                        <div class="project-table-card overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-custom align-middle">
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
                                                        <div class="fw-semibold text-slate-900"><?= htmlspecialchars((string) $task['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="<?= htmlspecialchars($buildAvatar(['name' => $task['assigned_name'] ?? 'Chưa giao', 'avatar' => $task['assigned_avatar'] ?? null], 'name', 'avatar', 36), ENT_QUOTES, 'UTF-8') ?>" alt="avatar" class="project-task-avatar">
                                                            <span class="text-slate-700"><?= htmlspecialchars((string) ($task['assigned_name'] ?? 'Chưa giao'), ENT_QUOTES, 'UTF-8') ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="project-pill" style="border-left: 4px solid <?= htmlspecialchars($taskStatusColor, ENT_QUOTES, 'UTF-8') ?>; background-color: <?= htmlspecialchars($taskStatusColor, ENT_QUOTES, 'UTF-8') ?>15; color: <?= htmlspecialchars($taskStatusColor, ENT_QUOTES, 'UTF-8') ?>;">
                                                            <?= htmlspecialchars((string) $taskStatusText, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="project-pill <?= htmlspecialchars($taskPriority['class'], ENT_QUOTES, 'UTF-8') ?>">
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
                </div>
            </div>
        </div>
    </div>

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
                        <div class="project-table-card border rounded-3 overflow-auto project-member-picker">
                            <table class="table table-custom align-middle">
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
                                                    <div class="fw-semibold text-slate-900"><?= htmlspecialchars((string) ($u['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="project-mini-note"><?= htmlspecialchars((string) ($u['employee_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars((string) ($u['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                </td>
                                                <td>
                                                    <div class="small text-slate-600"><?= htmlspecialchars((string) ($u['job_title'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></div>
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
</div>

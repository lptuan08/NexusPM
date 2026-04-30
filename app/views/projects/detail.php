<?php
/**
 * Cấu hình ánh xạ trạng thái dự án sang tên hiển thị và màu sắc tương ứng
 */
$projectStatusMap = [
    'planning' => ['text' => 'Lên kế hoạch', 'tone' => 'info', 'color' => '#2563eb'],
    'active'   => ['text' => 'Đang thực hiện', 'tone' => 'success', 'color' => '#0f766e'],
    'on_hold'  => ['text' => 'Tạm dừng', 'tone' => 'warning', 'color' => '#b45309'],
    'completed' => ['text' => 'Hoàn thành', 'tone' => 'primary', 'color' => '#7c3aed'],
];

/**
 * Ánh xạ trạng thái công việc
 */
$taskStatusMap = [
    'todo' => ['text' => 'Chưa làm', 'color' => '#64748b'],
    'in_progress' => ['text' => 'Đang làm', 'color' => '#d97706'],
    'done' => ['text' => 'Hoàn thành', 'color' => '#059669'],
];

/**
 * Ánh xạ mức độ ưu tiên công việc
 */
$priorityMap = [
    'high' => ['text' => 'Cao', 'color' => '#dc2626', 'bg' => '#fee2e2'],
    'medium' => ['text' => 'Trung bình', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'low' => ['text' => 'Thấp', 'color' => '#475569', 'bg' => '#e2e8f0'],
];

// Lấy thông tin trạng thái hiện tại của dự án
$currentStatus = $projectStatusMap[$project['status']] ?? [
    'text' => ucfirst((string) $project['status']),
    'tone' => 'secondary',
    'color' => '#64748b',
];

// Khởi tạo các biến thống kê công việc
$todayTs = strtotime(date('Y-m-d'));
$totalTasks = count($tasks);
$completedTasks = 0;
$inProgressTasks = 0;
$todoTasks = 0;
$overdueTasks = 0;

// Duyệt qua danh sách công việc để tính toán số liệu thống kê
foreach ($tasks as $task) {
    if (($task['status'] ?? '') === 'done') {
        $completedTasks++;
    } elseif (($task['status'] ?? '') === 'in_progress') {
        $inProgressTasks++;
    } else {
        $todoTasks++;
    }

    // Kiểm tra công việc trễ hạn (hạn chót < hôm nay và chưa hoàn thành)
    if (!empty($task['due_date']) && strtotime($task['due_date']) < $todayTs && ($task['status'] ?? '') !== 'done') {
        $overdueTasks++;
    }
}

// Tính toán phần trăm tiến độ và thời gian còn lại của dự án
$progressPercent = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;
$remainingDays = null;
$isOverdueProject = false;

if (!empty($project['due_date'])) {
    $dueTs = strtotime($project['due_date']);
    $remainingDays = (int) floor(($dueTs - $todayTs) / 86400);
    $isOverdueProject = $remainingDays < 0 && ($project['status'] ?? '') !== 'completed';
}

// Xác định Trưởng dự án (Lead/Manager/Owner) từ danh sách thành viên
$leadMember = null;
foreach ($members as $member) {
    $memberRole = strtolower((string) ($member['role'] ?? ''));
    if (
        stripos($memberRole, 'manager') !== false ||
        stripos($memberRole, 'lead') !== false ||
        stripos($memberRole, 'owner') !== false ||
        stripos($memberRole, 'chủ') !== false
    ) {
        $leadMember = $member;
        break;
    }
}
if ($leadMember === null && !empty($members)) {
    $leadMember = $members[0];
}

// Chuẩn bị các biến hiển thị (xử lý escape HTML để bảo mật)
$ownerName = htmlspecialchars((string) ($project['owner_name'] ?? 'Chưa xác định'), ENT_QUOTES, 'UTF-8');
$ownerEmail = htmlspecialchars((string) ($project['owner_email'] ?? ''), ENT_QUOTES, 'UTF-8');
$memberCount = count($members);
$projectCode = htmlspecialchars((string) ($project['project_code'] ?? '-'), ENT_QUOTES, 'UTF-8');
$projectName = htmlspecialchars((string) ($project['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$projectDescription = trim((string) ($project['description'] ?? ''));
$deleteMessage = htmlspecialchars("Bạn có chắc chắn muốn xóa dự án {$project['name']}?", ENT_QUOTES, 'UTF-8');

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

// Thông tin hiển thị cho Trưởng dự án
$leadMemberName = htmlspecialchars((string) ($leadMember['name'] ?? ($project['owner_name'] ?? 'Chưa xác định')), ENT_QUOTES, 'UTF-8');
$leadMemberEmail = htmlspecialchars((string) ($leadMember['email'] ?? ($project['owner_email'] ?? '')), ENT_QUOTES, 'UTF-8');
$leadMemberRole = htmlspecialchars((string) ($leadMember['role'] ?? 'Chủ dự án'), ENT_QUOTES, 'UTF-8');
$leadMemberAvatar = $buildAvatar(
    [
        'name' => $leadMember['name'] ?? ($project['owner_name'] ?? 'Owner'),
        'avatar' => $leadMember['avatar'] ?? ($project['owner_avatar'] ?? null),
    ],
    'name',
    'avatar',
    80
);
?>

<style>
    /* Container chính cho trang chi tiết dự án */
    .project-detail-shell {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.12), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #f8fafc 100%);
        margin: -1.5rem;
        padding: 1.5rem;
        min-height: 100%;
    }

    /* Card Header chứa thông tin tiêu đề và banner */
    .project-detail-header {
        background: #fff;
        border: 1px solid #e2e8f0;
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
    .project-panel,
    .project-table-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        box-shadow: 0 18px 40px -34px rgba(15, 23, 42, 0.4);
    }

    .project-stat-card {
        padding: 1.25rem;
        height: 100%;
    }

    .project-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
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
        font-size: 0.82rem;
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

    .project-member-row,
    .project-timeline-item {
        border-bottom: 1px solid #f1f5f9;
    }

    .project-member-row:last-child,
    .project-timeline-item:last-child {
        border-bottom: 0;
    }

    .project-member-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        object-fit: cover;
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

    .project-mini-note {
        font-size: 0.82rem;
        color: #64748b;
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
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div class="d-flex align-items-center text-slate-600 fs-6">
            <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
            <span class="mx-2 text-slate-400 d-flex align-items-center"><i data-lucide="chevron-right" style="width:16px;height:16px;"></i></span>
            <span class="fw-medium text-slate-800 fs-5"><?= $projectName ?></span>
        </div>
    </div>

    <!-- Khu vực Header Dự án (Banner & Tóm tắt) -->
    <section class="project-detail-header mb-4">
        <div class="project-detail-banner p-4 p-lg-5">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 position-relative" style="z-index: 1;">
                <div class="pe-xl-4">
                    <h1 class="h2 fw-bold mb-3"><?= $projectName ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="project-pill" style="background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.16);">
                            <?= $projectCode ?>
                        </span>
                        <span class="project-pill" style="background: rgba(255,255,255,0.16); color: #fff;">
                            <i data-lucide="sparkles" style="width:16px;height:16px;"></i>
                            <?= htmlspecialchars($currentStatus['text'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

                <!-- Các nút hành động chính -->
                <div class="d-flex flex-wrap align-items-start gap-2 flex-shrink-0">
                    <a href="<?= URLROOT ?>/projects/<?= $project['id'] ?>/edit" class="btn btn-light fw-semibold px-3 px-lg-4">
                        <i data-lucide="pencil" class="me-2" style="width:18px;height:18px;"></i>
                        Chỉnh sửa
                    </a>
                    <button
                        type="button"
                        class="btn btn-outline-light fw-semibold px-3 px-lg-4"
                        onclick="showDeleteModal('<?= URLROOT ?>/projects/<?= $project['id'] ?>/delete', '<?= $deleteMessage ?>')">
                        <i data-lucide="trash-2" class="me-2" style="width:18px;height:18px;"></i>
                        Xóa dự án
                    </button>
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
                                <i data-lucide="calendar-clock" style="width:22px;height:22px;"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="project-meta-label">Thời hạn & Còn lại</div>
                                <div class="fw-bold text-slate-900 mb-1" style="font-size: 0.85rem; white-space: nowrap;">
                                    <?= !empty($project['start_date']) ? date('d/m', strtotime($project['start_date'])) : '??' ?> - <?= !empty($project['due_date']) ? date('d/m/Y', strtotime($project['due_date'])) : '??' ?>
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
                                <i data-lucide="user-check" style="width:22px;height:22px;"></i>
                            </div>
                            <div class="overflow-hidden">
                                <div class="project-meta-label">Trưởng dự án</div>
                                <div class="fs-5 fw-bold text-slate-900 text-truncate" title="<?= $ownerName ?>"><?= $ownerName ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ô: Tổng số công việc -->
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-rose">
                                <i data-lucide="list-checks" style="width:22px;height:22px;"></i>
                            </div>
                            <div>
                                <div class="project-meta-label">Tổng công việc</div>
                                <div class="fs-3 fw-bold text-slate-900"><?= $totalTasks ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ô: Phần trăm tiến độ -->
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-green">
                                <i data-lucide="activity" style="width:22px;height:22px;"></i>
                            </div>
                            <div>
                                <div class="project-meta-label">Tiến độ dự án</div>
                                <div class="fs-3 fw-bold text-slate-900"><?= $progressPercent ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <!-- Cột Nội dung chính (Tabs) -->
        <div class="col-xl-8">
            <div class="project-panel p-4 p-lg-5">
                <!-- Danh sách Tab -->
                <ul class="nav project-tabset mb-4" id="projectDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview-pane" type="button" role="tab">
                            Tổng quan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-pane" type="button" role="tab">
                            Công việc
                        </button>
                    </li>
                </ul>

                <!-- Nội dung tương ứng của từng Tab -->
                <div class="tab-content">
                    <!-- Tab: Tổng quan (Hiển thị các công việc mới nhất) -->
                    <div class="tab-pane fade show active" id="overview-pane" role="tabpanel">
                        <div class="text-slate-600" style="line-height: 1.8; font-size: 0.95rem;">
                            <?= nl2br(htmlspecialchars($projectDescription !== '' ? $projectDescription : 'Dự án này hiện chưa có thông tin mô tả chi tiết.', ENT_QUOTES, 'UTF-8')) ?>
                        </div>
                    </div>

                    <!-- Tab: Danh sách Công việc (Chi tiết bảng) -->
                    <div class="tab-pane fade" id="tasks-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <div class="project-section-title mb-1">Công việc dự án</div>
                                <div class="project-mini-note">Quản lý và theo dõi các đầu việc chi tiết.</div>
                            </div>
                            <a href="<?= URLROOT ?>/tasks/create?project_id=<?= $project['id'] ?>" class="btn btn-sm btn-primary px-3 shadow-sm">
                                <i data-lucide="plus" class="me-1" style="width:16px;height:16px;"></i>
                                Thêm công việc
                            </a>
                        </div>

                        <div class="project-table-card overflow-hidden">
                            <div class="table-responsive">
                                <table class="table project-table align-middle mb-0">
                                    <thead>
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
                                                $taskState = $taskStatusMap[$task['status'] ?? 'todo'] ?? ['text' => $task['status'] ?? 'Không rõ', 'color' => '#64748b'];
                                                $taskPriority = $priorityMap[$task['priority'] ?? 'low'] ?? ['text' => $task['priority'] ?? 'Thấp', 'color' => '#475569', 'bg' => '#e2e8f0'];
                                                ?>
                                                <tr>
                                                    <td class="px-4 py-3">
                                                        <div class="fw-semibold text-slate-900"><?= htmlspecialchars((string) $task['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="<?= $buildAvatar(['name' => $task['assigned_name'] ?? 'Chưa giao', 'avatar' => $task['assigned_avatar'] ?? null], 'name', 'avatar', 36) ?>" alt="avatar" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                                                            <span class="text-slate-700"><?= htmlspecialchars((string) ($task['assigned_name'] ?? 'Chưa giao'), ENT_QUOTES, 'UTF-8') ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="project-pill" style="background: <?= $taskState['color'] ?>15; color: <?= $taskState['color'] ?>;">
                                                            <?= htmlspecialchars($taskState['text'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="project-pill" style="background: <?= $taskPriority['bg'] ?>; color: <?= $taskPriority['color'] ?>;">
                                                            <?= htmlspecialchars($taskPriority['text'], ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3 <?= (!empty($task['due_date']) && strtotime($task['due_date']) < $todayTs && ($task['status'] ?? '') !== 'done') ? 'text-danger fw-semibold' : 'text-slate-700' ?>">
                                                        <?= !empty($task['due_date']) ? date('d/m/Y', strtotime($task['due_date'])) : '-' ?>
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

                </div>
            </div>
        </div>

        <!-- Cột Sidebar bên phải (Thành viên) -->
        <div class="col-xl-4">
            <div class="project-panel p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <div class="project-section-title mb-1">Thành viên tham gia</div>
                        <div class="project-mini-note">Danh sách nhân sự đang thực hiện dự án này.</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMembersModal">
                        <i data-lucide="user-plus" class="me-1" style="width:16px;height:16px;"></i>
                        Thêm thành viên
                    </button>
                </div>

                <div class="project-table-card p-3 p-lg-4">
                    <?php if (!empty($members)): ?>
                        <div class="d-flex flex-column" style="max-height: 520px; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                            <?php foreach ($members as $member): ?>
                                <div class="project-member-row py-3">
                                    <div class="d-flex flex-column gap-3">
                                        <div class="d-flex align-items-center justify-content-between gap-3">
                                            <div class="d-flex align-items-center gap-3 overflow-hidden">
                                            <img src="<?= $buildAvatar($member, 'name', 'avatar', 48) ?>" alt="avatar" class="project-member-avatar">
                                                <div class="overflow-hidden">
                                                    <div class="fw-semibold text-slate-900 text-truncate"><?= htmlspecialchars((string) $member['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="project-mini-note text-truncate"><?= htmlspecialchars((string) ($member['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </div>
                                            <span class="project-pill" style="background:#f8fafc;color:#334155;">
                                                <?= htmlspecialchars((string) ($member['role'] ?? 'Thành viên'), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-end">
                                            <span class="project-mini-note">
                                                Tham gia từ <?= !empty($member['joined_at']) ? date('d/m/Y', strtotime($member['joined_at'])) : '-' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-slate-500">Dự án này hiện chưa có thành viên nào tham gia.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm thành viên -->
    <div class="modal fade" id="addMembersModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem;">
                <div class="modal-header border-bottom-0 pt-4 px-4">
                    <h5 class="fw-bold text-slate-900 mb-0">Thêm thành viên mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= URLROOT ?>/projects/<?= $project['id'] ?>/addMembers" method="POST">
                    <?php SecurityHelper::csrfInput(); ?>
                    <div class="modal-body px-4 py-3">
                        <div class="mb-4">
                            <label class="project-editor-label">Quyền hạn trong dự án</label>
                            <select name="role" class="form-select project-editor-select">
                                <option value="member" selected>Thành viên (Member)</option>
                                <option value="lead">Trưởng nhóm (Lead)</option>
                                <option value="manager">Quản lý (Manager)</option>
                                <option value="observer">Người quan sát (Observer)</option>
                            </select>
                        </div>
                        
                        <div class="project-section-title mb-2">Chọn nhân viên (có thể chọn nhiều)</div>
                        <div class="project-table-card border rounded-3 overflow-auto" style="max-height: 400px;">
                            <table class="table align-middle mb-0">
                                <thead class="sticky-top bg-slate-50 shadow-sm" style="z-index: 10;">
                                    <tr class="border-bottom">
                                        <th style="width: 40px;"></th>
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
                                                    <input type="checkbox" name="user_ids[]" value="<?= $u['id'] ?>" class="form-check-input">
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-slate-900"><?= htmlspecialchars($u['name']) ?></div>
                                                    <div class="project-mini-note"><?= htmlspecialchars($u['employee_code']) ?> • <?= htmlspecialchars($u['email']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="small text-slate-600"><?= htmlspecialchars($u['job_title'] ?? 'N/A') ?></div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 pb-4 px-4">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" style="border-radius: 0.75rem;">Hủy bỏ</button>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm" style="border-radius: 0.75rem;">
                            <i data-lucide="check-circle" class="me-2" style="width:16px;height:16px;"></i>Xác nhận thêm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

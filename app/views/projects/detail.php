<?php
$projectStatusMap = [
    'planning' => ['text' => 'Lên kế hoạch', 'tone' => 'info', 'color' => '#2563eb'],
    'active' => ['text' => 'Đang thực hiện', 'tone' => 'success', 'color' => '#0f766e'],
    'completed' => ['text' => 'Hoàn thành', 'tone' => 'primary', 'color' => '#7c3aed'],
];

$taskStatusMap = [
    'todo' => ['text' => 'Chưa làm', 'color' => '#64748b'],
    'in_progress' => ['text' => 'Đang làm', 'color' => '#d97706'],
    'done' => ['text' => 'Hoàn thành', 'color' => '#059669'],
];

$priorityMap = [
    'high' => ['text' => 'Cao', 'color' => '#dc2626', 'bg' => '#fee2e2'],
    'medium' => ['text' => 'Trung bình', 'color' => '#d97706', 'bg' => '#fef3c7'],
    'low' => ['text' => 'Thấp', 'color' => '#475569', 'bg' => '#e2e8f0'],
];

$currentStatus = $projectStatusMap[$project['status']] ?? [
    'text' => ucfirst((string) $project['status']),
    'tone' => 'secondary',
    'color' => '#64748b',
];

$todayTs = strtotime(date('Y-m-d'));
$totalTasks = count($tasks);
$completedTasks = 0;
$inProgressTasks = 0;
$todoTasks = 0;
$overdueTasks = 0;

foreach ($tasks as $task) {
    if (($task['status'] ?? '') === 'done') {
        $completedTasks++;
    } elseif (($task['status'] ?? '') === 'in_progress') {
        $inProgressTasks++;
    } else {
        $todoTasks++;
    }

    if (!empty($task['due_date']) && strtotime($task['due_date']) < $todayTs && ($task['status'] ?? '') !== 'done') {
        $overdueTasks++;
    }
}

$progressPercent = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;
$remainingDays = null;
$isOverdueProject = false;

if (!empty($project['due_date'])) {
    $dueTs = strtotime($project['due_date']);
    $remainingDays = (int) floor(($dueTs - $todayTs) / 86400);
    $isOverdueProject = $remainingDays < 0 && ($project['status'] ?? '') !== 'completed';
}

$recentTasks = array_slice($tasks, 0, 5);

$leadMember = null;
foreach ($members as $member) {
    if (stripos((string) ($member['role'] ?? ''), 'manager') !== false || stripos((string) ($member['role'] ?? ''), 'lead') !== false) {
        $leadMember = $member;
        break;
    }
}
if ($leadMember === null && !empty($members)) {
    $leadMember = $members[0];
}

$projectName = htmlspecialchars((string) ($project['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$projectDescription = trim((string) ($project['description'] ?? ''));
$deleteMessage = htmlspecialchars("Bạn có chắc chắn muốn xóa dự án {$project['name']}?", ENT_QUOTES, 'UTF-8');

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
    .project-detail-shell {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.12), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #f8fafc 100%);
        margin: -1.5rem;
        padding: 1.5rem;
        min-height: 100%;
    }

    .project-detail-header {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1.5rem;
        box-shadow: 0 20px 45px -32px rgba(15, 23, 42, 0.35);
        overflow: hidden;
    }

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

    .project-breadcrumb a {
        color: #64748b;
        text-decoration: none;
    }

    .project-breadcrumb .separator {
        color: #94a3b8;
    }

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

    .project-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
    }

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
    <div class="project-breadcrumb d-flex align-items-center gap-2 small mb-4">
        <a href="<?= URLROOT ?>/projects">Dự án</a>
        <span class="separator">/</span>
        <span class="text-slate-800 fw-semibold"><?= $projectName ?></span>
    </div>

    <section class="project-detail-header mb-4">
        <div class="project-detail-banner p-4 p-lg-5">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 position-relative" style="z-index: 1;">
                <div class="pe-xl-4">
                    <div class="d-inline-flex align-items-center rounded-pill px-3 py-2 mb-3" style="background: rgba(255,255,255,0.14);">
                        <span class="small fw-semibold">Project Overview</span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <h1 class="h2 fw-bold mb-0"><?= $projectName ?></h1>
                        <span class="project-pill" style="background: rgba(255,255,255,0.16); color: #fff;">
                            <i data-lucide="sparkles" style="width:16px;height:16px;"></i>
                            <?= htmlspecialchars($currentStatus['text'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <p class="mb-0 text-white-50" style="max-width: 860px;">
                        <?= nl2br(htmlspecialchars($projectDescription !== '' ? $projectDescription : 'Dự án hiện chưa có mô tả chi tiết.', ENT_QUOTES, 'UTF-8')) ?>
                    </p>
                </div>

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
            <div class="row g-3 g-lg-4">
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-blue">
                                <i data-lucide="list-todo" style="width:22px;height:22px;"></i>
                            </div>
                            <div>
                                <div class="project-meta-label">Tổng công việc</div>
                                <div class="fs-3 fw-bold text-slate-900"><?= $totalTasks ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-green">
                                <i data-lucide="check-check" style="width:22px;height:22px;"></i>
                            </div>
                            <div>
                                <div class="project-meta-label">Đã hoàn thành</div>
                                <div class="fs-3 fw-bold text-slate-900"><?= $completedTasks ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-rose">
                                <i data-lucide="alert-circle" style="width:22px;height:22px;"></i>
                            </div>
                            <div>
                                <div class="project-meta-label">Công việc trễ hạn</div>
                                <div class="fs-3 fw-bold text-slate-900"><?= $overdueTasks ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="project-stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="project-stat-icon project-soft-violet">
                                <i data-lucide="calendar-clock" style="width:22px;height:22px;"></i>
                            </div>
                            <div>
                                <div class="project-meta-label"><?= $isOverdueProject ? 'Quá hạn dự án' : 'Thời gian còn lại' ?></div>
                                <div class="fs-3 fw-bold text-slate-900">
                                    <?php if ($remainingDays === null): ?>
                                        -
                                    <?php elseif ($remainingDays >= 0): ?>
                                        <?= $remainingDays ?> ngày
                                    <?php else: ?>
                                        <?= abs($remainingDays) ?> ngày
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="project-panel p-4 p-lg-5 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div>
                        <div class="project-section-title mb-1">Tiến độ dự án</div>
                        <div class="project-mini-note">Tỷ lệ hoàn thành được tính theo số lượng công việc đã xong.</div>
                    </div>
                    <div class="project-pill" style="background: #eff6ff; color: #1d4ed8;">
                        <i data-lucide="gauge" style="width:16px;height:16px;"></i>
                        <?= $progressPercent ?>% hoàn thành
                    </div>
                </div>

                <div class="project-progress mb-4">
                    <div class="project-progress-bar" style="width: <?= $progressPercent ?>%;"></div>
                </div>

                <div class="row g-3 g-lg-4 mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="project-meta-label mb-1">Ngày bắt đầu</div>
                        <div class="fw-semibold text-slate-900">
                            <?= !empty($project['start_date']) ? date('d/m/Y', strtotime($project['start_date'])) : '-' ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="project-meta-label mb-1">Ngày kết thúc</div>
                        <div class="fw-semibold <?= $isOverdueProject ? 'text-danger' : 'text-slate-900' ?>">
                            <?= !empty($project['due_date']) ? date('d/m/Y', strtotime($project['due_date'])) : '-' ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="project-meta-label mb-1">Thành viên tham gia</div>
                        <div class="fw-semibold text-slate-900"><?= count($members) ?> người</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="project-meta-label mb-1">Trạng thái hiện tại</div>
                        <div class="fw-semibold" style="color: <?= $currentStatus['color'] ?>;">
                            <?= htmlspecialchars($currentStatus['text'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <span class="project-pill" style="background:#f8fafc;color:#475569;">Chưa làm: <?= $todoTasks ?></span>
                    <span class="project-pill" style="background:#fff7ed;color:#c2410c;">Đang làm: <?= $inProgressTasks ?></span>
                    <span class="project-pill" style="background:#ecfdf5;color:#047857;">Hoàn thành: <?= $completedTasks ?></span>
                </div>
            </div>

            <div class="project-panel p-4 p-lg-5">
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
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="members-tab" data-bs-toggle="tab" data-bs-target="#members-pane" type="button" role="tab">
                            Thành viên
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="overview-pane" role="tabpanel">
                        <div class="project-section-title mb-3">Cập nhật gần đây từ công việc</div>

                        <?php if (!empty($recentTasks)): ?>
                            <div class="d-flex flex-column">
                                <?php foreach ($recentTasks as $task): ?>
                                    <?php
                                    $taskState = $taskStatusMap[$task['status'] ?? 'todo'] ?? ['text' => $task['status'] ?? 'Không rõ', 'color' => '#64748b'];
                                    $assigneeName = htmlspecialchars((string) ($task['assigned_name'] ?? 'Chưa giao'), ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <div class="project-timeline-item py-3">
                                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                            <div>
                                                <div class="fw-semibold text-slate-900 mb-1"><?= htmlspecialchars((string) $task['title'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="project-mini-note">
                                                    Phụ trách: <?= $assigneeName ?>
                                                    <?php if (!empty($task['due_date'])): ?>
                                                        • Hạn chót <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <span class="project-pill align-self-md-start" style="background: <?= $taskState['color'] ?>15; color: <?= $taskState['color'] ?>;">
                                                <?= htmlspecialchars($taskState['text'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-slate-500">Dự án này chưa có công việc nào để hiển thị tiến độ.</div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="tasks-pane" role="tabpanel">
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

                    <div class="tab-pane fade" id="members-pane" role="tabpanel">
                        <div class="project-table-card p-3 p-lg-4">
                            <?php if (!empty($members)): ?>
                                <div class="d-flex flex-column">
                                    <?php foreach ($members as $member): ?>
                                        <div class="project-member-row py-3">
                                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= $buildAvatar($member, 'name', 'avatar', 48) ?>" alt="avatar" class="project-member-avatar">
                                                    <div>
                                                        <div class="fw-semibold text-slate-900"><?= htmlspecialchars((string) $member['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                        <div class="project-mini-note"><?= htmlspecialchars((string) ($member['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column flex-md-row align-items-md-center gap-2 gap-md-4">
                                                    <span class="project-pill" style="background:#f8fafc;color:#334155;">
                                                        <?= htmlspecialchars((string) ($member['role'] ?? 'Thành viên'), ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
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
        </div>

        <div class="col-xl-4">
            <div class="project-panel p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="project-section-title mb-0">Người phụ trách nổi bật</div>
                    <span class="project-mini-note"><?= count($members) ?> thành viên</span>
                </div>

                <?php if ($leadMember !== null): ?>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <img src="<?= $buildAvatar($leadMember, 'name', 'avatar', 80) ?>" alt="lead avatar" class="project-lead-avatar">
                        <div>
                            <div class="h5 mb-1 text-slate-900"><?= htmlspecialchars((string) $leadMember['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="project-mini-note mb-1"><?= htmlspecialchars((string) ($leadMember['role'] ?? 'Thành viên chủ chốt'), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($leadMember['email'])): ?>
                                <div class="small text-slate-600"><?= htmlspecialchars((string) $leadMember['email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-slate-500">Chưa có thông tin thành viên phụ trách.</div>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-6">
                        <div class="rounded-4 p-3" style="background:#eff6ff;">
                            <div class="project-meta-label">Tiến độ</div>
                            <div class="fs-4 fw-bold text-slate-900"><?= $progressPercent ?>%</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="rounded-4 p-3" style="background:#f8fafc;">
                            <div class="project-meta-label">Còn lại</div>
                            <div class="fs-4 fw-bold text-slate-900">
                                <?= $remainingDays === null ? '-' : max($remainingDays, 0) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="project-panel p-4">
                <div class="project-section-title mb-3">Mô tả nhanh</div>
                <div class="project-mini-note mb-3">Tóm tắt tình trạng hiện tại của dự án để theo dõi nhanh.</div>

                <div class="d-flex flex-column gap-3">
                    <div class="rounded-4 p-3" style="background:#f8fafc;">
                        <div class="project-meta-label mb-1">Công việc cần chú ý</div>
                        <div class="fw-semibold text-slate-900"><?= $overdueTasks ?> việc đang trễ hạn</div>
                    </div>
                    <div class="rounded-4 p-3" style="background:#f8fafc;">
                        <div class="project-meta-label mb-1">Khối lượng đã xử lý</div>
                        <div class="fw-semibold text-slate-900"><?= $completedTasks ?>/<?= $totalTasks ?> công việc đã hoàn tất</div>
                    </div>
                    <div class="rounded-4 p-3" style="background:#f8fafc;">
                        <div class="project-meta-label mb-1">Mốc dự án</div>
                        <div class="fw-semibold text-slate-900">
                            <?= !empty($project['start_date']) ? date('d/m/Y', strtotime($project['start_date'])) : '-' ?>
                            -
                            <?= !empty($project['due_date']) ? date('d/m/Y', strtotime($project['due_date'])) : '-' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

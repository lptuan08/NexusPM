<?php
$canCreateProject = \App\helpers\AuthHelper::can('projects.create.all');

$kpiCards = [
    [
        'label' => 'Nhân viên',
        'value' => '120',
        'meta' => '+8 trong tháng',
        'icon' => 'users',
        'tone' => 'primary',
        'href' => URLROOT . '/users',
    ],
    [
        'label' => 'Tổng dự án',
        'value' => '45',
        'meta' => '12 dự án mới',
        'icon' => 'folder-open',
        'tone' => 'violet',
        'href' => URLROOT . '/projects',
    ],
    [
        'label' => 'Đang thực hiện',
        'value' => '28',
        'meta' => '62% danh mục',
        'icon' => 'activity',
        'tone' => 'warning',
        'href' => URLROOT . '/projects',
    ],
    [
        'label' => 'Quá hạn',
        'value' => '07',
        'meta' => 'Cần xử lý',
        'icon' => 'alert-triangle',
        'tone' => 'danger',
        'href' => URLROOT . '/tasks',
    ],
];

$featuredProjects = [
    [
        'name' => 'Phát triển ứng dụng Nexus',
        'code' => 'NX-APP',
        'desc' => 'Xây dựng hệ thống quản lý dự án nội bộ cho doanh nghiệp.',
        'progress' => 75,
        'daysLeft' => 2,
        'owner' => 'Nguyễn An',
        'icon' => 'smartphone',
        'tone' => 'primary',
        'stage' => 'Sprint 05',
    ],
    [
        'name' => 'Nâng cấp API Backend',
        'code' => 'NX-API',
        'desc' => 'Tối ưu hóa tốc độ xử lý dữ liệu và bảo mật hệ thống.',
        'progress' => 40,
        'daysLeft' => 10,
        'owner' => 'Trần Bình',
        'icon' => 'server',
        'tone' => 'rose',
        'stage' => 'Hardening',
    ],
    [
        'name' => 'Module Báo cáo vận hành',
        'code' => 'NX-RPT',
        'desc' => 'Chuẩn hóa số liệu tiến độ, chi phí và năng lực đội nhóm.',
        'progress' => 58,
        'daysLeft' => 6,
        'owner' => 'Lê Chi',
        'icon' => 'bar-chart-3',
        'tone' => 'cyan',
        'stage' => 'Discovery',
    ],
];

$priorityMap = [
    'high' => ['label' => 'Cao', 'class' => 'priority-high'],
    'medium' => ['label' => 'Trung bình', 'class' => 'priority-medium'],
    'low' => ['label' => 'Thấp', 'class' => 'priority-low'],
];

$myTasks = [
    ['title' => 'Thiết kế UI Dashboard', 'project' => 'NexusPM', 'prio' => 'high', 'due' => 'Hôm nay', 'done' => false, 'tone' => 'primary'],
    ['title' => 'Fix lỗi CSS trên Mobile', 'project' => 'Website Công ty', 'prio' => 'medium', 'due' => 'Ngày mai', 'done' => false, 'tone' => 'amber'],
    ['title' => 'Viết tài liệu hướng dẫn', 'project' => 'Đào tạo', 'prio' => 'low', 'due' => '15/10', 'done' => true, 'tone' => 'emerald'],
    ['title' => 'Kiểm tra bảo mật API', 'project' => 'NexusPM', 'prio' => 'high', 'due' => 'Hôm nay', 'done' => false, 'tone' => 'rose'],
];

$activities = [
    ['name' => 'Nguyễn An', 'act' => 'hoàn thành', 'target' => 'Thiết kế UI', 'time' => '5 phút trước', 'icon' => 'check-circle-2', 'tone' => 'emerald'],
    ['name' => 'Trần Bình', 'act' => 'được giao dự án', 'target' => 'NexusPM', 'time' => '12 phút trước', 'icon' => 'folder-plus', 'tone' => 'primary'],
    ['name' => 'Lê Chi', 'act' => 'cập nhật tiến độ', 'target' => 'Module Báo cáo', 'time' => '45 phút trước', 'icon' => 'trending-up', 'tone' => 'warning'],
    ['name' => 'Phạm Duy', 'act' => 'gia nhập đội ngũ', 'target' => 'Backend', 'time' => '2 giờ trước', 'icon' => 'user-plus', 'tone' => 'slate'],
    ['name' => 'Hoàng Yến', 'act' => 'gửi phê duyệt', 'target' => 'Hợp đồng dự án A', 'time' => '5 giờ trước', 'icon' => 'send', 'tone' => 'danger'],
];

$completionRate = 68;
$openTaskCount = count(array_filter($myTasks, static fn ($task) => empty($task['done'])));
?>

<div class="page-toolbar dashboard-toolbar">
    <div>
        <h1 class="page-title">Tổng quan</h1>
        <p class="page-subtitle">Theo dõi tiến độ dự án, công việc ưu tiên và hoạt động mới nhất.</p>
    </div>

    <div class="page-actions">
        <a href="<?= URLROOT ?>/tasks/create" class="btn btn-outline-secondary">
            <i data-lucide="plus"></i>
            <span>Thêm công việc</span>
        </a>
        <?php if ($canCreateProject): ?>
        <a href="<?= URLROOT ?>/projects/createWizard" class="btn btn-primary">
            <i data-lucide="folder-plus"></i>
            <span>Tạo dự án</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-shell">
    <section class="dashboard-hero">
        <div class="dashboard-hero-content">
            <span class="dashboard-eyebrow">NexusPM Workspace</span>
            <h2>Trung tâm điều phối dự án</h2>
            <p>Ưu tiên hôm nay: xử lý các công việc quá hạn, giữ nhịp các dự án trọng tâm và cập nhật tiến độ cho đội nhóm.</p>
        </div>
        <div class="dashboard-health-panel" aria-label="Tỷ lệ hoàn thành">
            <div class="dashboard-health-ring" style="--value: <?= (int) $completionRate ?>%;">
                <span><?= (int) $completionRate ?>%</span>
            </div>
            <div>
                <div class="fw-bold text-slate-900">Nhịp hoàn thành</div>
                <div class="text-slate-500 small">Còn <?= (int) $openTaskCount ?> việc đang mở hôm nay</div>
            </div>
        </div>
    </section>

    <section class="dashboard-kpi-grid" aria-label="Chỉ số tổng quan">
        <?php foreach ($kpiCards as $card): ?>
            <a href="<?= htmlspecialchars($card['href'], ENT_QUOTES, 'UTF-8') ?>" class="dashboard-kpi-card ui-card">
                <div class="dashboard-kpi-icon tone-<?= htmlspecialchars($card['tone'], ENT_QUOTES, 'UTF-8') ?>">
                    <i data-lucide="<?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                </div>
                <div class="dashboard-kpi-main">
                    <span><?= htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars($card['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars($card['meta'], ENT_QUOTES, 'UTF-8') ?></small>
                </div>
                <i data-lucide="arrow-up-right" class="dashboard-kpi-arrow"></i>
            </a>
        <?php endforeach; ?>
    </section>

    <div class="dashboard-grid">
        <main class="dashboard-main">
            <section class="dashboard-section">
                <div class="dashboard-section-header">
                    <div>
                        <h3>Dự án trọng tâm</h3>
                        <p>Các dự án cần được theo dõi sát trong tuần này.</p>
                    </div>
                    <a href="<?= URLROOT ?>/projects" class="btn btn-white border border-slate-200 shadow-none">
                        <span>Xem tất cả</span>
                        <i data-lucide="arrow-right" size="16"></i>
                    </a>
                </div>

                <div class="dashboard-project-list">
                    <?php foreach ($featuredProjects as $project): ?>
                        <?php $isUrgent = $project['daysLeft'] <= 3; ?>
                        <article class="dashboard-project-item dashboard-compact-card tone-card-<?= htmlspecialchars($project['tone'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="dashboard-project-top">
                                <div class="dashboard-project-icon tone-<?= htmlspecialchars($project['tone'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i data-lucide="<?= htmlspecialchars($project['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                </div>
                                <span class="ui-badge dashboard-code-badge"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <h4><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <p><?= htmlspecialchars($project['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="dashboard-project-meta">
                                <span><i data-lucide="user" size="14"></i><?= htmlspecialchars($project['owner'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span><i data-lucide="milestone" size="14"></i><?= htmlspecialchars($project['stage'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="<?= $isUrgent ? 'text-danger fw-semibold' : '' ?>">
                                    <i data-lucide="clock" size="14"></i><?= (int) $project['daysLeft'] ?> ngày
                                </span>
                            </div>
                            <div class="dashboard-progress-row">
                                <div class="progress progress-thin">
                                    <div class="progress-bar tone-<?= htmlspecialchars($project['tone'], ENT_QUOTES, 'UTF-8') ?>" style="width: <?= (int) $project['progress'] ?>%"></div>
                                </div>
                                <strong><?= (int) $project['progress'] ?>%</strong>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="dashboard-section">
                <div class="dashboard-section-header">
                    <div>
                        <h3>Việc cần làm hôm nay</h3>
                        <p>Bạn còn <strong class="text-primary-600"><?= (int) $openTaskCount ?></strong> việc chưa hoàn thành.</p>
                    </div>
                    <a href="<?= URLROOT ?>/tasks" class="btn btn-white border border-slate-200 shadow-none">
                        <span>Mở danh sách</span>
                        <i data-lucide="list-checks" size="16"></i>
                    </a>
                </div>

                <div class="dashboard-task-list">
                    <?php foreach ($myTasks as $task): ?>
                        <?php $priority = $priorityMap[$task['prio']] ?? $priorityMap['low']; ?>
                        <article class="dashboard-task-item dashboard-compact-card tone-card-<?= htmlspecialchars($task['tone'], ENT_QUOTES, 'UTF-8') ?> <?= $task['done'] ? 'is-done' : '' ?>">
                            <div class="dashboard-task-top">
                                <input class="form-check-input dashboard-task-check" type="checkbox" <?= $task['done'] ? 'checked' : '' ?> aria-label="Đánh dấu hoàn thành">
                                <span class="ui-badge <?= htmlspecialchars($priority['class'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($priority['label'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="dashboard-task-content">
                                <h4><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                <div class="dashboard-task-meta">
                                    <span><i data-lucide="briefcase" size="14"></i><?= htmlspecialchars($task['project'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span><i data-lucide="calendar" size="14"></i><?= htmlspecialchars($task['due'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <div class="dashboard-task-footer">
                                <span><i data-lucide="<?= $task['done'] ? 'check-circle-2' : 'circle-dot' ?>" size="14"></i><?= $task['done'] ? 'Hoàn tất' : 'Đang mở' ?></span>
                                <button class="btn btn-icon-google dashboard-row-action" type="button" aria-label="Mở hành động">
                                    <i data-lucide="more-vertical" size="16"></i>
                                </button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <aside class="dashboard-aside">
            <section class="ui-card dashboard-side-card">
                <div class="dashboard-side-header">
                    <h3>Tải công việc</h3>
                    <span class="ui-badge status-muted">Tuần này</span>
                </div>
                <div class="dashboard-workload">
                    <div>
                        <span>Thiết kế</span>
                        <strong>12</strong>
                    </div>
                    <div>
                        <span>Backend</span>
                        <strong>09</strong>
                    </div>
                    <div>
                        <span>QA</span>
                        <strong>06</strong>
                    </div>
                </div>
                <div class="dashboard-capacity">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-slate-500">Công suất đội nhóm</span>
                        <strong class="small text-slate-800">82%</strong>
                    </div>
                    <div class="progress progress-thin">
                        <div class="progress-bar tone-primary" style="width: 82%"></div>
                    </div>
                </div>
            </section>

            <section class="ui-card dashboard-side-card">
                <div class="dashboard-side-header">
                    <h3>Hoạt động gần đây</h3>
                    <a href="#" class="text-decoration-none text-primary-600 small fw-semibold">Xem thêm</a>
                </div>
                <div class="dashboard-activity-list">
                    <?php foreach ($activities as $item): ?>
                        <article class="dashboard-activity-item">
                            <div class="dashboard-activity-icon tone-<?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8') ?>">
                                <i data-lucide="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>" size="16"></i>
                            </div>
                            <div>
                                <p>
                                    <strong><?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?= htmlspecialchars($item['act'], ENT_QUOTES, 'UTF-8') ?>
                                    <span><?= htmlspecialchars($item['target'], ENT_QUOTES, 'UTF-8') ?></span>
                                </p>
                                <small><?= htmlspecialchars($item['time'], ENT_QUOTES, 'UTF-8') ?></small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </aside>
    </div>
</div>

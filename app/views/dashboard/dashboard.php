<?php
$canViewTasks = \App\helpers\AuthHelper::canAny([
    'tasks.project',
    'tasks.view.all',
    'tasks.view.own'
]);
$canViewProjects = \App\helpers\AuthHelper::canAny([
    'projects.view.all',
    'projects.view.joined'
]);

$taskStats = [
    ['label' => 'Quá hạn', 'value' => 7, 'tone' => 'red', 'icon' => 'alarm-clock'],
    ['label' => 'Đến hạn hôm nay', 'value' => 12, 'tone' => 'amber', 'icon' => 'calendar-clock'],
    ['label' => 'Đang xử lý', 'value' => 38, 'tone' => 'blue', 'icon' => 'list-checks'],
];

$tasks = [
    [
        'title' => 'Chốt luồng tạo dự án wizard',
        'project' => 'NexusPM',
        'owner' => 'Nguyễn An',
        'status' => 'Review',
        'priority' => 'Khẩn cấp',
        'due' => 'Quá hạn 1 ngày',
        'progress' => 86,
        'tone' => 'red',
        'href' => URLROOT . '/tasks',
    ],
    [
        'title' => 'Rà soát quyền xem dự án theo thành viên',
        'project' => 'NexusPM',
        'owner' => 'Trần Bình',
        'status' => 'Đang xử lý',
        'priority' => 'Cao',
        'due' => 'Hôm nay',
        'progress' => 58,
        'tone' => 'amber',
        'href' => URLROOT . '/tasks',
    ],
    [
        'title' => 'Bổ sung trạng thái hoàn thành cho Kanban',
        'project' => 'Module Báo cáo',
        'owner' => 'Lê Chi',
        'status' => 'Todo',
        'priority' => 'Cao',
        'due' => 'Hôm nay',
        'progress' => 32,
        'tone' => 'blue',
        'href' => URLROOT . '/tasks',
    ],
    [
        'title' => 'Cập nhật tài liệu bàn giao API',
        'project' => 'Nâng cấp API Backend',
        'owner' => 'Hoàng Yến',
        'status' => 'Đang xử lý',
        'priority' => 'Trung bình',
        'due' => 'Ngày mai',
        'progress' => 64,
        'tone' => 'green',
        'href' => URLROOT . '/tasks',
    ],
];

$projectStats = [
    ['label' => 'Đang chạy', 'value' => 8, 'tone' => 'blue'],
    ['label' => 'Cần chú ý', 'value' => 3, 'tone' => 'amber'],
    ['label' => 'Hoàn thành', 'value' => 2, 'tone' => 'green'],
];

$projects = [
    [
        'name' => 'Phát triển ứng dụng Nexus',
        'code' => 'NX-APP',
        'manager' => 'Nguyễn An',
        'health' => 'Rủi ro cao',
        'due' => 'Còn 2 ngày',
        'progress' => 64,
        'tone' => 'red',
        'href' => URLROOT . '/projects',
    ],
    [
        'name' => 'Module Báo cáo vận hành',
        'code' => 'NX-RPT',
        'manager' => 'Lê Chi',
        'health' => 'Đang chậm',
        'due' => 'Còn 7 ngày',
        'progress' => 42,
        'tone' => 'amber',
        'href' => URLROOT . '/projects',
    ],
    [
        'name' => 'Nâng cấp API Backend',
        'code' => 'NX-API',
        'manager' => 'Trần Bình',
        'health' => 'Ổn định',
        'due' => 'Còn 10 ngày',
        'progress' => 71,
        'tone' => 'blue',
        'href' => URLROOT . '/projects',
    ],
];

$activities = [
    [
        'actor' => 'Nguyễn An',
        'action' => 'chuyển công việc sang Review',
        'target' => 'Chốt luồng tạo dự án wizard',
        'time' => '8 phút trước',
        'icon' => 'arrow-right-left',
        'tone' => 'blue',
    ],
    [
        'actor' => 'Lê Chi',
        'action' => 'báo rủi ro tiến độ',
        'target' => 'Module Báo cáo vận hành',
        'time' => '24 phút trước',
        'icon' => 'alert-circle',
        'tone' => 'amber',
    ],
    [
        'actor' => 'Trần Bình',
        'action' => 'cập nhật deadline',
        'target' => 'Nâng cấp API Backend',
        'time' => '1 giờ trước',
        'icon' => 'calendar-plus',
        'tone' => 'red',
    ],
    [
        'actor' => 'Hoàng Yến',
        'action' => 'hoàn thành kiểm thử',
        'target' => 'Kanban kéo thả',
        'time' => '3 giờ trước',
        'icon' => 'check-circle-2',
        'tone' => 'green',
    ],
];
?>

<div class="page-toolbar dashboard-toolbar">
    <div>
        <h1 class="page-title">Tổng quan</h1>
    </div>
</div>

<div class="dashboard-page">
    <?php if ($canViewTasks): ?>
    <section class="dashboard-panel dashboard-panel-tasks" aria-labelledby="dashboard-task-title">
        <div class="dashboard-panel-header">
            <div>
                <span class="dashboard-section-kicker">Task</span>
                <h2 id="dashboard-task-title">Công việc</h2>
            </div>
            <a href="<?= URLROOT ?>/tasks" class="dashboard-link-button">
                <span>Xem danh sách</span>
                <i data-lucide="arrow-up-right"></i>
            </a>
        </div>

        <div class="dashboard-stat-strip" aria-label="Thống kê công việc">
            <?php foreach ($taskStats as $stat): ?>
                <article class="dashboard-stat dashboard-tone-<?= htmlspecialchars($stat['tone'], ENT_QUOTES, 'UTF-8') ?>">
                    <i data-lucide="<?= htmlspecialchars($stat['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    <span><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= (int) $stat['value'] ?></strong>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-task-list">
            <?php foreach ($tasks as $task): ?>
                <a href="<?= htmlspecialchars($task['href'], ENT_QUOTES, 'UTF-8') ?>" class="dashboard-task-item dashboard-tone-<?= htmlspecialchars($task['tone'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="dashboard-task-main">
                        <div class="dashboard-item-title-row">
                            <h3><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="dashboard-chip"><?= htmlspecialchars($task['priority'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="dashboard-meta-row">
                            <span><i data-lucide="briefcase"></i><?= htmlspecialchars($task['project'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><i data-lucide="user"></i><?= htmlspecialchars($task['owner'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><i data-lucide="calendar-clock"></i><?= htmlspecialchars($task['due'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div class="dashboard-task-side">
                        <span class="dashboard-status"><?= htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="dashboard-progress" aria-label="Tiến độ <?= (int) $task['progress'] ?>%">
                            <span style="width: <?= (int) $task['progress'] ?>%"></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($canViewProjects): ?>
    <section class="dashboard-panel dashboard-panel-projects" aria-labelledby="dashboard-project-title">
        <div class="dashboard-panel-header">
            <div>
                <span class="dashboard-section-kicker">Project</span>
                <h2 id="dashboard-project-title">Dự án</h2>
            </div>
            <a href="<?= URLROOT ?>/projects" class="dashboard-link-button">
                <span>Xem dự án</span>
                <i data-lucide="arrow-up-right"></i>
            </a>
        </div>

        <div class="dashboard-project-summary" aria-label="Thống kê dự án">
            <?php foreach ($projectStats as $stat): ?>
                <div class="dashboard-project-stat dashboard-tone-<?= htmlspecialchars($stat['tone'], ENT_QUOTES, 'UTF-8') ?>">
                    <strong><?= (int) $stat['value'] ?></strong>
                    <span><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="dashboard-project-list">
            <?php foreach ($projects as $project): ?>
                <a href="<?= htmlspecialchars($project['href'], ENT_QUOTES, 'UTF-8') ?>" class="dashboard-project-item dashboard-tone-<?= htmlspecialchars($project['tone'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="dashboard-project-heading">
                        <div>
                            <span><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></span>
                            <h3><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        </div>
                        <span class="dashboard-chip"><?= htmlspecialchars($project['health'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="dashboard-meta-row">
                        <span><i data-lucide="user-round"></i><?= htmlspecialchars($project['manager'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span><i data-lucide="clock"></i><?= htmlspecialchars($project['due'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="dashboard-progress-row">
                        <div class="dashboard-progress" aria-label="Tiến độ <?= (int) $project['progress'] ?>%">
                            <span style="width: <?= (int) $project['progress'] ?>%"></span>
                        </div>
                        <strong><?= (int) $project['progress'] ?>%</strong>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="dashboard-panel dashboard-panel-activity" aria-labelledby="dashboard-activity-title">
        <div class="dashboard-panel-header">
            <div>
                <span class="dashboard-section-kicker">Recent</span>
                <h2 id="dashboard-activity-title">Hoạt động gần đây</h2>
            </div>
        </div>

        <div class="dashboard-activity-list">
            <?php foreach ($activities as $activity): ?>
                <article class="dashboard-activity-item dashboard-tone-<?= htmlspecialchars($activity['tone'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="dashboard-activity-icon">
                        <i data-lucide="<?= htmlspecialchars($activity['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    </div>
                    <div>
                        <p>
                            <strong><?= htmlspecialchars($activity['actor'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?= htmlspecialchars($activity['action'], ENT_QUOTES, 'UTF-8') ?>
                            <span><?= htmlspecialchars($activity['target'], ENT_QUOTES, 'UTF-8') ?></span>
                        </p>
                        <time><?= htmlspecialchars($activity['time'], ENT_QUOTES, 'UTF-8') ?></time>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>

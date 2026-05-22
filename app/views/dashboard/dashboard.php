<?php
$canCreateProject = \App\helpers\AuthHelper::can('projects.create.all');

$scopeTabs = [
    ['label' => 'Của tôi', 'active' => true],
    ['label' => 'Team', 'active' => false],
    ['label' => 'Tất cả', 'active' => false],
];

$kpiCards = [
    [
        'label' => 'Quá hạn',
        'value' => '07',
        'meta' => '3 việc ưu tiên cao',
        'icon' => 'alert-triangle',
        'tone' => 'danger',
        'href' => URLROOT . '/tasks',
    ],
    [
        'label' => 'Đến hạn hôm nay',
        'value' => '12',
        'meta' => '5 việc của tôi',
        'icon' => 'calendar-clock',
        'tone' => 'amber',
        'href' => URLROOT . '/tasks',
    ],
    [
        'label' => 'Đang mở',
        'value' => '38',
        'meta' => '9 việc chưa phân công',
        'icon' => 'list-todo',
        'tone' => 'primary',
        'href' => URLROOT . '/tasks',
    ],
    [
        'label' => 'Dự án rủi ro',
        'value' => '04',
        'meta' => 'Cần PM rà soát',
        'icon' => 'radar',
        'tone' => 'rose',
        'href' => URLROOT . '/projects',
    ],
];

$priorityMap = [
    'urgent' => ['label' => 'Khẩn cấp', 'class' => 'priority-urgent'],
    'high' => ['label' => 'Cao', 'class' => 'priority-high'],
    'medium' => ['label' => 'Trung bình', 'class' => 'priority-medium'],
    'low' => ['label' => 'Thấp', 'class' => 'priority-low'],
];

$actionTasks = [
    [
        'title' => 'Chốt luồng tạo dự án wizard',
        'project' => 'NexusPM',
        'assignee' => 'Bạn',
        'prio' => 'urgent',
        'due' => 'Quá hạn 1 ngày',
        'reason' => 'Đang chặn QA kiểm thử luồng onboarding',
        'status' => 'In Review',
        'tone' => 'danger',
        'href' => URLROOT . '/tasks',
    ],
    [
        'title' => 'Rà soát quyền xem dự án theo thành viên',
        'project' => 'NexusPM',
        'assignee' => 'Nguyễn An',
        'prio' => 'high',
        'due' => 'Hôm nay',
        'reason' => 'Có nguy cơ lộ dữ liệu dự án ngoài phạm vi',
        'status' => 'Đang xử lý',
        'tone' => 'rose',
        'href' => URLROOT . '/tasks',
    ],
    [
        'title' => 'Bổ sung trạng thái hoàn thành cho Kanban',
        'project' => 'Module Báo cáo',
        'assignee' => 'Lê Chi',
        'prio' => 'high',
        'due' => 'Hôm nay',
        'reason' => 'Tiến độ báo cáo đang tính sai khi status tùy chỉnh',
        'status' => 'Todo',
        'tone' => 'amber',
        'href' => URLROOT . '/tasks',
    ],
    [
        'title' => 'Cập nhật tài liệu bàn giao API',
        'project' => 'Nâng cấp API Backend',
        'assignee' => 'Trần Bình',
        'prio' => 'medium',
        'due' => 'Ngày mai',
        'reason' => 'Đội frontend cần contract mới để tích hợp',
        'status' => 'Đang xử lý',
        'tone' => 'primary',
        'href' => URLROOT . '/tasks',
    ],
];

$riskProjects = [
    [
        'name' => 'Phát triển ứng dụng Nexus',
        'code' => 'NX-APP',
        'owner' => 'Nguyễn An',
        'progress' => 64,
        'daysLeft' => 2,
        'overdue' => 3,
        'health' => 'Rủi ro cao',
        'signal' => 'Deadline gần, còn nhiều task review chưa đóng.',
        'tone' => 'danger',
        'icon' => 'flame',
        'href' => URLROOT . '/projects',
    ],
    [
        'name' => 'Module Báo cáo vận hành',
        'code' => 'NX-RPT',
        'owner' => 'Lê Chi',
        'progress' => 42,
        'daysLeft' => 7,
        'overdue' => 2,
        'health' => 'Đang chậm',
        'signal' => 'Tiến độ thấp hơn kế hoạch tuần này 18%.',
        'tone' => 'amber',
        'icon' => 'trending-down',
        'href' => URLROOT . '/projects',
    ],
    [
        'name' => 'Nâng cấp API Backend',
        'code' => 'NX-API',
        'owner' => 'Trần Bình',
        'progress' => 71,
        'daysLeft' => 10,
        'overdue' => 1,
        'health' => 'Cần theo dõi',
        'signal' => 'Một task bảo mật đang đứng yên 4 ngày.',
        'tone' => 'primary',
        'icon' => 'shield-alert',
        'href' => URLROOT . '/projects',
    ],
];

$workloadMembers = [
    ['name' => 'Nguyễn An', 'role' => 'PM', 'tasks' => 11, 'overdue' => 2, 'capacity' => 94, 'tone' => 'danger'],
    ['name' => 'Lê Chi', 'role' => 'Frontend', 'tasks' => 9, 'overdue' => 1, 'capacity' => 86, 'tone' => 'amber'],
    ['name' => 'Trần Bình', 'role' => 'Backend', 'tasks' => 7, 'overdue' => 0, 'capacity' => 72, 'tone' => 'primary'],
    ['name' => 'Hoàng Yến', 'role' => 'QA', 'tasks' => 5, 'overdue' => 0, 'capacity' => 58, 'tone' => 'emerald'],
];

$taskFlow = [
    ['label' => 'Todo', 'count' => 14, 'tone' => 'slate'],
    ['label' => 'Đang xử lý', 'count' => 16, 'tone' => 'primary'],
    ['label' => 'Review', 'count' => 8, 'tone' => 'amber'],
    ['label' => 'Hoàn thành', 'count' => 23, 'tone' => 'emerald'],
];

$activities = [
    ['name' => 'Nguyễn An', 'act' => 'chuyển', 'target' => 'Chốt luồng tạo dự án wizard', 'time' => '8 phút trước', 'icon' => 'arrow-right-left', 'tone' => 'primary'],
    ['name' => 'Lê Chi', 'act' => 'báo rủi ro', 'target' => 'Module Báo cáo vận hành', 'time' => '24 phút trước', 'icon' => 'alert-circle', 'tone' => 'amber'],
    ['name' => 'Trần Bình', 'act' => 'cập nhật deadline', 'target' => 'Nâng cấp API Backend', 'time' => '1 giờ trước', 'icon' => 'calendar-plus', 'tone' => 'rose'],
    ['name' => 'Hoàng Yến', 'act' => 'hoàn thành kiểm thử', 'target' => 'Kanban kéo thả', 'time' => '3 giờ trước', 'icon' => 'check-circle-2', 'tone' => 'emerald'],
];

$openActionCount = count($actionTasks);
$urgentActionCount = count(array_filter($actionTasks, static fn ($task) => in_array($task['prio'], ['urgent', 'high'], true)));
$teamCapacity = 78;
?>

<div class="page-toolbar dashboard-toolbar">
    <div>
        <h1 class="page-title">Tổng quan hôm nay</h1>
        <p class="page-subtitle">Tập trung vào việc cần xử lý, rủi ro dự án và tải công việc của đội nhóm.</p>
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
    <section class="dashboard-command-center">
        <div class="dashboard-command-copy">
            <span class="dashboard-eyebrow">NexusPM Workspace</span>
            <h2>Bàn điều phối công việc</h2>
            <p>Có <?= (int) $openActionCount ?> việc cần xử lý ngay, trong đó <?= (int) $urgentActionCount ?> việc đang ở mức ưu tiên cao.</p>
        </div>

        <div class="dashboard-command-actions" aria-label="Phạm vi dữ liệu">
            <?php foreach ($scopeTabs as $tab): ?>
                <button type="button" class="dashboard-scope-tab <?= $tab['active'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="dashboard-kpi-grid" aria-label="Chỉ số cần hành động">
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
                        <h3>Việc cần xử lý ngay</h3>
                        <p>Các việc quá hạn, đến hạn hôm nay hoặc đang chặn người khác.</p>
                    </div>
                    <a href="<?= URLROOT ?>/tasks" class="btn btn-white border border-slate-200 shadow-none">
                        <span>Mở danh sách</span>
                        <i data-lucide="list-checks" size="16"></i>
                    </a>
                </div>

                <div class="dashboard-action-list">
                    <?php foreach ($actionTasks as $task): ?>
                        <?php $priority = $priorityMap[$task['prio']] ?? $priorityMap['low']; ?>
                        <a href="<?= htmlspecialchars($task['href'], ENT_QUOTES, 'UTF-8') ?>" class="dashboard-action-item tone-card-<?= htmlspecialchars($task['tone'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="dashboard-action-status">
                                <span class="dashboard-status-dot"></span>
                            </div>
                            <div class="dashboard-action-content">
                                <div class="dashboard-action-title-row">
                                    <h4><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                    <span class="ui-badge <?= htmlspecialchars($priority['class'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($priority['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>
                                <p><?= htmlspecialchars($task['reason'], ENT_QUOTES, 'UTF-8') ?></p>
                                <div class="dashboard-action-meta">
                                    <span><i data-lucide="briefcase" size="14"></i><?= htmlspecialchars($task['project'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span><i data-lucide="user" size="14"></i><?= htmlspecialchars($task['assignee'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span><i data-lucide="calendar-clock" size="14"></i><?= htmlspecialchars($task['due'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <span><i data-lucide="circle-dot" size="14"></i><?= htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                            <i data-lucide="chevron-right" class="dashboard-action-arrow"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="dashboard-section">
                <div class="dashboard-section-header">
                    <div>
                        <h3>Dự án cần chú ý</h3>
                        <p>Dự án có deadline gần, tiến độ thấp hoặc task bị kẹt.</p>
                    </div>
                    <a href="<?= URLROOT ?>/projects" class="btn btn-white border border-slate-200 shadow-none">
                        <span>Xem dự án</span>
                        <i data-lucide="arrow-right" size="16"></i>
                    </a>
                </div>

                <div class="dashboard-project-list">
                    <?php foreach ($riskProjects as $project): ?>
                        <a href="<?= htmlspecialchars($project['href'], ENT_QUOTES, 'UTF-8') ?>" class="dashboard-project-item tone-card-<?= htmlspecialchars($project['tone'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="dashboard-project-top">
                                <div class="dashboard-project-icon tone-<?= htmlspecialchars($project['tone'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i data-lucide="<?= htmlspecialchars($project['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                </div>
                                <span class="ui-badge dashboard-code-badge"><?= htmlspecialchars($project['code'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div>
                                <h4><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                                <p><?= htmlspecialchars($project['signal'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="dashboard-project-meta">
                                <span><i data-lucide="user" size="14"></i><?= htmlspecialchars($project['owner'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span><i data-lucide="clock" size="14"></i><?= (int) $project['daysLeft'] ?> ngày</span>
                                <span><i data-lucide="alert-triangle" size="14"></i><?= (int) $project['overdue'] ?> quá hạn</span>
                            </div>
                            <div class="dashboard-progress-row">
                                <div class="progress progress-thin">
                                    <div class="progress-bar tone-<?= htmlspecialchars($project['tone'], ENT_QUOTES, 'UTF-8') ?>" style="width: <?= (int) $project['progress'] ?>%"></div>
                                </div>
                                <strong><?= (int) $project['progress'] ?>%</strong>
                            </div>
                            <div class="dashboard-risk-label">
                                <?= htmlspecialchars($project['health'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </a>
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
                <div class="dashboard-capacity-summary">
                    <div class="dashboard-health-ring" style="--value: <?= (int) $teamCapacity ?>%;">
                        <span><?= (int) $teamCapacity ?>%</span>
                    </div>
                    <div>
                        <strong>Công suất đội nhóm</strong>
                        <p>2 thành viên đang gần ngưỡng quá tải.</p>
                    </div>
                </div>

                <div class="dashboard-workload-list">
                    <?php foreach ($workloadMembers as $member): ?>
                        <article class="dashboard-workload-item">
                            <div>
                                <strong><?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8') ?> · <?= (int) $member['tasks'] ?> việc</span>
                            </div>
                            <div class="dashboard-workload-meter">
                                <span class="<?= $member['overdue'] > 0 ? 'text-danger fw-semibold' : 'text-slate-500' ?>">
                                    <?= (int) $member['overdue'] ?> quá hạn
                                </span>
                                <div class="progress progress-thin">
                                    <div class="progress-bar tone-<?= htmlspecialchars($member['tone'], ENT_QUOTES, 'UTF-8') ?>" style="width: <?= (int) $member['capacity'] ?>%"></div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="ui-card dashboard-side-card">
                <div class="dashboard-side-header">
                    <h3>Luồng công việc</h3>
                    <a href="<?= URLROOT ?>/tasks" class="text-decoration-none text-primary-600 small fw-semibold">Chi tiết</a>
                </div>
                <div class="dashboard-flow-list">
                    <?php foreach ($taskFlow as $item): ?>
                        <div class="dashboard-flow-item">
                            <span class="dashboard-flow-dot tone-<?= htmlspecialchars($item['tone'], ENT_QUOTES, 'UTF-8') ?>"></span>
                            <span><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            <strong><?= (int) $item['count'] ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="ui-card dashboard-side-card">
                <div class="dashboard-side-header">
                    <h3>Hoạt động liên quan</h3>
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

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
$taskTrendPeriod = $taskTrendPeriod ?? '7d';
$taskTrendPeriods = [
    '7d' => '7 ngày',
    '30d' => '30 ngày',
];

$metricCards = $metricCards ?? [];
$priorityTasks = $priorityTasks ?? [];

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
];

$chartPayload = $chartPayload ?? [
    'taskTrend' => [
        'labels' => ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
        'created' => [9, 15, 12, 18, 14, 11, 16],
        'completed' => [5, 8, 10, 12, 13, 9, 15],
    ],
    'taskStatus' => [
        'labels' => ['Todo', 'Đang xử lý', 'Review', 'Hoàn thành'],
        'values' => [18, 24, 9, 31],
        'colors' => ['#4dabf7', '#ffd166', '#ff8a65', '#51cf66'],
    ],
    'deadlineRisk' => [
        'labels' => ['Quá hạn', 'Hôm nay', '7 ngày tới', 'Chưa có hạn'],
        'values' => [7, 12, 21, 8],
        'colors' => ['#e03131', '#f08c00', '#1c7ed6', '#868e96'],
    ],
];
?>

<div class="page-toolbar dashboard-toolbar">
    <div>
        <h1 class="page-title">Tổng quan</h1>
    </div>
</div>

<div class="dashboard-page">
    <section class="dashboard-metrics" aria-label="Chỉ số tổng quan">
        <?php foreach ($metricCards as $metric): ?>
            <?php if (empty($metric['visible'])) continue; ?>
            <article class="dashboard-metric dashboard-tone-<?= htmlspecialchars($metric['tone'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="dashboard-metric-icon">
                    <i data-lucide="<?= htmlspecialchars($metric['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                </div>
                <div>
                    <strong><?= htmlspecialchars($metric['value'], ENT_QUOTES, 'UTF-8') ?></strong>
                    <span><?= htmlspecialchars($metric['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    <small><?= htmlspecialchars($metric['hint'], ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            </article>
        <?php endforeach; ?>
    </section>

    <div class="dashboard-grid <?= !$canViewTasks ? 'dashboard-grid-single' : '' ?>">
        <?php if ($canViewTasks): ?>
            <section class="dashboard-panel dashboard-panel-main" aria-labelledby="dashboard-trend-title">
                <div class="dashboard-panel-header">
                    <div>
                        <span class="dashboard-section-kicker">Reports</span>
                        <h2 id="dashboard-trend-title">Nhịp hoàn thành công việc</h2>
                    </div>
                    <form method="GET" action="<?= URLROOT ?>/" class="dashboard-date-range" aria-label="Lọc nhịp hoàn thành công việc">
                        <select name="task_trend_period" class="dashboard-date-range-select" onchange="this.form.submit()">
                            <?php foreach ($taskTrendPeriods as $periodValue => $periodLabel): ?>
                                <option value="<?= htmlspecialchars($periodValue, ENT_QUOTES, 'UTF-8') ?>" <?= $taskTrendPeriod === $periodValue ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($periodLabel, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i data-lucide="chevron-down"></i>
                    </form>
                </div>
                <div class="dashboard-chart-wrap dashboard-chart-wrap-line">
                    <canvas id="dashboardTaskTrendChart" aria-label="Biểu đồ công việc tạo mới và hoàn thành theo thời gian"></canvas>
                </div>
            </section>

            <section class="dashboard-panel dashboard-panel-side" aria-labelledby="dashboard-status-title">
                <div class="dashboard-panel-header">
                    <div>
                        <span class="dashboard-section-kicker">Analytics</span>
                        <h2 id="dashboard-status-title">Tỷ lệ trạng thái</h2>
                    </div>
                </div>
                <div class="dashboard-chart-wrap dashboard-chart-wrap-donut">
                    <canvas id="dashboardTaskStatusChart" aria-label="Biểu đồ tỷ lệ trạng thái công việc"></canvas>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($canViewTasks): ?>
            <section class="dashboard-panel dashboard-panel-table" aria-labelledby="dashboard-task-table-title">
                <div class="dashboard-panel-header">
                    <div>
                        <span class="dashboard-section-kicker">Priority</span>
                        <h2 id="dashboard-task-table-title">Công việc ưu tiên</h2>
                    </div>
                    <a href="<?= URLROOT ?>/tasks" class="dashboard-link-button">
                        <span>Xem tất cả</span>
                        <i data-lucide="arrow-up-right"></i>
                    </a>
                </div>

                <div class="dashboard-task-table">
                    <div class="dashboard-task-table-head" aria-hidden="true">
                        <span>Công việc</span>
                        <span>Phụ trách</span>
                        <span>Deadline</span>
                        <span>Tiến độ</span>
                    </div>
                    <?php if (empty($priorityTasks)): ?>
                        <div class="dashboard-empty-state">
                            Chưa có công việc ưu tiên cần theo dõi.
                        </div>
                    <?php endif; ?>
                    <?php foreach ($priorityTasks as $task): ?>
                        <a href="<?= htmlspecialchars($task['href'], ENT_QUOTES, 'UTF-8') ?>" class="dashboard-task-row dashboard-tone-<?= htmlspecialchars($task['tone'], ENT_QUOTES, 'UTF-8') ?>">
                            <span>
                                <strong><?= htmlspecialchars($task['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($task['project'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($task['status'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($task['priority'], ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                            <span><?= htmlspecialchars($task['owner'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars($task['due'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="dashboard-progress-cell">
                                <span class="dashboard-progress" aria-label="Tiến độ <?= (int) $task['progress'] ?>%">
                                    <span style="width: <?= (int) $task['progress'] ?>%"></span>
                                </span>
                                <strong><?= (int) $task['progress'] ?>%</strong>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <aside class="dashboard-side-stack">
            <?php if ($canViewTasks): ?>
                <section class="dashboard-panel dashboard-panel-risk" aria-labelledby="dashboard-risk-title">
                    <div class="dashboard-panel-header">
                        <div>
                            <span class="dashboard-section-kicker">Deadline</span>
                            <h2 id="dashboard-risk-title">Rủi ro thời hạn</h2>
                        </div>
                    </div>
                    <div class="dashboard-chart-wrap dashboard-chart-wrap-bar">
                        <canvas id="dashboardDeadlineChart" aria-label="Biểu đồ rủi ro deadline"></canvas>
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
        </aside>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    window.NexusDashboardCharts = <?= json_encode($chartPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    (function () {
        document.querySelectorAll('.dashboard-date-range').forEach(function (range) {
            const select = range.querySelector('.dashboard-date-range-select');
            if (!select) {
                return;
            }

            range.addEventListener('click', function (event) {
                if (event.target === select) {
                    return;
                }

                event.preventDefault();
                select.focus();

                if (typeof select.showPicker === 'function') {
                    select.showPicker();
                }
            });
        });
    })();

    (function () {
        if (!window.Chart || !window.NexusDashboardCharts) {
            return;
        }

        const chartData = window.NexusDashboardCharts;
        const fontFamily = "'Roboto', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
        Chart.defaults.font.family = fontFamily;
        Chart.defaults.color = '#5f6368';
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.boxWidth = 8;
        Chart.defaults.plugins.tooltip.backgroundColor = '#1f2937';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;

        const makeGradient = function (ctx, colorStart, colorEnd) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 260);
            gradient.addColorStop(0, colorStart);
            gradient.addColorStop(1, colorEnd);
            return gradient;
        };

        const taskTrend = document.getElementById('dashboardTaskTrendChart');
        if (taskTrend) {
            const trendLabels = Array.isArray(chartData.taskTrend.labels) ? chartData.taskTrend.labels : [];
            const xTickLimit = Math.max(2, trendLabels.length > 12 ? 8 : trendLabels.length);

            const ctx = taskTrend.getContext('2d');
            new Chart(taskTrend, {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [
                        {
                            label: 'Tạo mới',
                            data: chartData.taskTrend.created,
                            borderColor: '#2f80ed',
                            backgroundColor: makeGradient(ctx, 'rgba(47, 128, 237, 0.20)', 'rgba(47, 128, 237, 0)'),
                            borderWidth: 2.5,
                            cubicInterpolationMode: 'monotone',
                            fill: true,
                            pointBackgroundColor: '#2f80ed',
                            pointBorderColor: '#1c7ed6',
                            pointBorderWidth: 2,
                            pointHoverRadius: 5,
                            pointRadius: 0,
                            tension: 0.48,
                        },
                        {
                            label: 'Hoàn thành',
                            data: chartData.taskTrend.completed,
                            borderColor: '#12b886',
                            backgroundColor: makeGradient(ctx, 'rgba(18, 184, 134, 0.18)', 'rgba(18, 184, 134, 0)'),
                            borderWidth: 2.5,
                            cubicInterpolationMode: 'monotone',
                            fill: true,
                            pointBackgroundColor: '#12b886',
                            pointBorderColor: '#0ca678',
                            pointBorderWidth: 2,
                            pointHoverRadius: 5,
                            pointRadius: 0,
                            tension: 0.48,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    resizeDelay: 120,
                    interaction: { intersect: false, mode: 'index' },
                    layout: {
                        padding: { top: 4, right: 8, bottom: 0, left: 0 },
                    },
                    plugins: {
                        legend: {
                            align: 'end',
                            labels: {
                                boxWidth: 8,
                                padding: 18,
                                usePointStyle: true,
                            },
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.92)',
                            borderColor: 'rgba(255, 255, 255, 0.16)',
                            borderWidth: 1,
                            padding: 12,
                            titleMarginBottom: 8,
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: {
                                autoSkip: true,
                                color: '#64748b',
                                font: { size: 11, weight: '600' },
                                maxTicksLimit: xTickLimit,
                                maxRotation: 0,
                                minRotation: 0,
                                padding: 8,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#94a3b8',
                                precision: 0,
                                stepSize: 5,
                            },
                            grid: { color: 'rgba(148, 163, 184, 0.16)' },
                            border: { display: false },
                        },
                    },
                },
            });
        }

        const taskStatus = document.getElementById('dashboardTaskStatusChart');
        if (taskStatus) {
            new Chart(taskStatus, {
                type: 'doughnut',
                data: {
                    labels: chartData.taskStatus.labels,
                    datasets: [{
                        data: chartData.taskStatus.values,
                        backgroundColor: chartData.taskStatus.colors,
                        borderColor: '#ffffff',
                        borderWidth: 4,
                        hoverOffset: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '64%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 16 },
                        },
                    },
                },
            });
        }

        const deadline = document.getElementById('dashboardDeadlineChart');
        if (deadline) {
            new Chart(deadline, {
                type: 'bar',
                data: {
                    labels: chartData.deadlineRisk.labels,
                    datasets: [{
                        label: 'Số lượng',
                        data: chartData.deadlineRisk.values,
                        backgroundColor: chartData.deadlineRisk.colors,
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 14,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(95, 99, 104, 0.12)' },
                            border: { display: false },
                        },
                        y: {
                            grid: { display: false },
                            border: { display: false },
                        },
                    },
                },
            });
        }
    })();
</script>

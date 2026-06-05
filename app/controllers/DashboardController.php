<?php
namespace App\controllers;

use App\core\Controller;
use App\core\View;
use App\helpers\AuthHelper;
use App\models\ProjectModel;
use App\models\TaskModel;

/**
 * Controller Dashboard - Hiển thị trang tổng quan
 */
class DashboardController extends Controller
{
    private const DASHBOARD_TIMEZONE = 'Asia/Ho_Chi_Minh';

    private ProjectModel $projectModel;
    private TaskModel $taskModel;

    /**
     * =============================================================
     * NHOM KHOI TAO
     * =============================================================
     */
    public function __construct()
    {
        parent::__construct();
        $this->projectModel = $this->model('ProjectModel');
        $this->taskModel = $this->model('TaskModel');
    }

    /**
     * =============================================================
     * NHOM HIEN THI TONG QUAN
     * =============================================================
     */
    public function index()
    {
        $taskTrendPeriod = $this->normalizeTaskTrendPeriod($this->request->getQuery()['task_trend_period'] ?? '7d');
        $today = $this->dashboardToday();

        View::render('dashboard/dashboard', [
            'title' => 'Tổng quan',
            'extra_css' => 'dashboard',
            'chartPayload' => $this->buildChartPayload($taskTrendPeriod),
            'metricCards' => $this->buildDashboardMetricCards($today),
            'priorityTasks' => $this->buildDashboardPriorityTasks($today),
            'taskTrendPeriod' => $taskTrendPeriod,
        ]);
    }

    /**
     * Chuẩn bị dữ liệu biểu đồ dashboard.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildChartPayload(string $taskTrendPeriod): array
    {
        [$startAt, $endAt] = $this->taskTrendDateRange($taskTrendPeriod);
        $trend = $this->taskModel->getTaskTrendByDate($this->taskVisibilityFilters(), $startAt, $endAt);

        $labels = [];
        $created = [];
        $completed = [];

        for ($day = $startAt; $day <= $endAt; $day = $day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            $labels[] = $day->format('d/m');
            $created[] = $trend['created'][$key] ?? 0;
            $completed[] = $trend['completed'][$key] ?? 0;
        }

        return [
            'taskTrend' => [
                'labels' => $labels,
                'created' => $created,
                'completed' => $completed,
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
    }

    /**
     * Chuẩn bị dữ liệu cho các thẻ thống kê trên Dashboard.
     *
     * =============================================================
     * NHOM DU LIEU DASHBOARD
     * =============================================================
     *
     * @param \DateTimeImmutable $today Ngày hiện tại theo timezone Dashboard.
     * @return array<int, array<string, mixed>> Danh sách card đã sẵn sàng để render.
     */
    private function buildDashboardMetricCards(\DateTimeImmutable $today): array
    {
        $canViewTasks = $this->canViewTasks();
        $canViewProjects = $this->canViewProjects();

        $taskSummary = $canViewTasks
            ? $this->taskModel->getDashboardTaskSummary($this->taskVisibilityFilters(), $today)
            : $this->emptyTaskSummary();

        $projectSummary = $canViewProjects
            ? $this->projectModel->getDashboardProjectSummary($this->projectVisibilityFilters(), $today)
            : $this->emptyProjectSummary();

        $totalTasks = max(0, (int) ($taskSummary['total_tasks'] ?? 0));
        $completedTasks = max(0, (int) ($taskSummary['completed_tasks'] ?? 0));
        $completionRate = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;

        return [
            [
                'label' => 'Công việc mở',
                'value' => $this->formatNumber((int) ($taskSummary['open_tasks'] ?? 0)),
                'hint' => $this->dashboardRecentTaskHint((int) ($taskSummary['created_recent_tasks'] ?? 0)),
                'tone' => 'blue',
                'icon' => 'list-checks',
                'visible' => $canViewTasks,
            ],
            [
                'label' => 'Quá hạn',
                'value' => $this->formatNumber((int) ($taskSummary['overdue_tasks'] ?? 0)),
                'hint' => $this->dashboardDueTodayHint((int) ($taskSummary['due_today_tasks'] ?? 0)),
                'tone' => 'red',
                'icon' => 'alarm-clock',
                'visible' => $canViewTasks,
            ],
            [
                'label' => 'Dự án đang chạy',
                'value' => $this->formatNumber((int) ($projectSummary['active_projects'] ?? 0)),
                'hint' => $this->dashboardProjectAttentionHint((int) ($projectSummary['attention_projects'] ?? 0)),
                'tone' => 'amber',
                'icon' => 'briefcase-business',
                'visible' => $canViewProjects,
            ],
            [
                'label' => 'Hoàn thành',
                'value' => $completionRate . '%',
                'hint' => "{$completedTasks}/{$totalTasks} công việc",
                'tone' => 'violet',
                'icon' => 'badge-check',
                'visible' => $canViewTasks,
            ],
        ];
    }

    /**
     * Chuẩn bị danh sách công việc ưu tiên cho Dashboard.
     *
     * @param \DateTimeImmutable $today Ngày hiện tại theo timezone Dashboard.
     * @return array<int, array<string, mixed>> Danh sách task đã format cho view.
     */
    private function buildDashboardPriorityTasks(\DateTimeImmutable $today): array
    {
        if (!$this->canViewTasks()) {
            return [];
        }

        $tasks = $this->taskModel->getDashboardPriorityTasks($this->taskVisibilityFilters(), $today, 4);

        return array_map(
            fn (array $task): array => $this->formatDashboardPriorityTask($task, $today),
            $tasks
        );
    }

    /**
     * Format một task ưu tiên thành cấu trúc view cần dùng.
     *
     * @param array<string, mixed> $task Dữ liệu task từ TaskModel.
     * @param \DateTimeImmutable $today Ngày hiện tại theo timezone Dashboard.
     * @return array<string, mixed> Task đã chuẩn hóa label/tone/progress.
     */
    private function formatDashboardPriorityTask(array $task, \DateTimeImmutable $today): array
    {
        $priority = (string) ($task['priority'] ?? 'medium');
        $dueDate = !empty($task['due_date'])
            ? \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $task['due_date'], $today->getTimezone())
            : false;

        return [
            'title' => (string) ($task['title'] ?? 'Công việc chưa đặt tên'),
            'project' => (string) ($task['project_name'] ?? 'Chưa có dự án'),
            'owner' => (string) ($task['assigned_name'] ?? 'Chưa giao'),
            'status' => (string) ($task['status_name'] ?? 'Chưa có trạng thái'),
            'priority' => $this->priorityLabel($priority),
            'due' => $this->formatDashboardDueDate($dueDate ?: null, $today),
            'progress' => $this->estimateTaskProgress($task),
            'tone' => $this->dashboardTaskTone($priority, $dueDate ?: null, $today),
            'href' => URLROOT . '/tasks/' . (int) ($task['id'] ?? 0) . '/edit',
        ];
    }

    /**
     * Ngày hiện tại dùng chung cho toàn bộ Dashboard.
     *
     * @return \DateTimeImmutable Mốc hôm nay đã set timezone Dashboard.
     */
    private function dashboardToday(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today', new \DateTimeZone(self::DASHBOARD_TIMEZONE));
    }

    /**
     * Kiểm tra quyền xem task cho các khối Dashboard.
     *
     * @return bool True nếu user được phép xem dữ liệu task.
     */
    private function canViewTasks(): bool
    {
        return AuthHelper::canAny([
            'tasks.project',
            'tasks.view.all',
            'tasks.view.own',
        ]);
    }

    /**
     * Kiểm tra quyền xem project cho các khối Dashboard.
     *
     * @return bool True nếu user được phép xem dữ liệu project.
     */
    private function canViewProjects(): bool
    {
        return AuthHelper::canAny([
            'projects.view.all',
            'projects.view.joined',
        ]);
    }

    /**
     * Tạo bộ lọc quyền xem project cho các truy vấn Dashboard.
     *
     * @return array<string, mixed> Bộ lọc quyền xem project.
     */
    private function projectVisibilityFilters(): array
    {
        if (AuthHelper::can('projects.view.all')) {
            return [];
        }

        return [
            'visibility_user_id' => AuthHelper::id(),
        ];
    }

    /**
     * Summary task rỗng dùng khi user không có quyền xem task.
     *
     * @return array<string, int>
     */
    private function emptyTaskSummary(): array
    {
        return [
            'total_tasks' => 0,
            'open_tasks' => 0,
            'completed_tasks' => 0,
            'overdue_tasks' => 0,
            'due_today_tasks' => 0,
            'created_recent_tasks' => 0,
        ];
    }

    /**
     * Summary project rỗng dùng khi user không có quyền xem project.
     *
     * @return array<string, int>
     */
    private function emptyProjectSummary(): array
    {
        return [
            'total_projects' => 0,
            'active_projects' => 0,
            'attention_projects' => 0,
        ];
    }

    /**
     * Format số cho thẻ thống kê Dashboard.
     *
     * @param int $value Giá trị số nguyên.
     * @return string Số đã format theo nhóm hàng nghìn.
     */
    private function formatNumber(int $value): string
    {
        return number_format(max(0, $value), 0, ',', '.');
    }

    /**
     * Tạo hint cho thẻ công việc mở.
     *
     * @param int $count Số task mới trong 7 ngày.
     * @return string Nội dung hint.
     */
    private function dashboardRecentTaskHint(int $count): string
    {
        return $count > 0 ? '+' . $this->formatNumber($count) . ' trong 7 ngày' : 'Không có việc mới 7 ngày';
    }

    /**
     * Tạo hint cho thẻ quá hạn.
     *
     * @param int $count Số task đến hạn hôm nay.
     * @return string Nội dung hint.
     */
    private function dashboardDueTodayHint(int $count): string
    {
        return $count > 0 ? $this->formatNumber($count) . ' đến hạn hôm nay' : 'Không có việc đến hạn hôm nay';
    }

    /**
     * Tạo hint cho thẻ dự án đang chạy.
     *
     * @param int $count Số dự án cần chú ý.
     * @return string Nội dung hint.
     */
    private function dashboardProjectAttentionHint(int $count): string
    {
        return $count > 0 ? $this->formatNumber($count) . ' dự án cần chú ý' : 'Không có dự án rủi ro';
    }

    /**
     * Chuyển priority lưu trong DB sang nhãn tiếng Việt.
     *
     * @param string $priority Priority raw của task.
     * @return string Nhãn hiển thị.
     */
    private function priorityLabel(string $priority): string
    {
        return match ($priority) {
            'urgent' => 'Khẩn cấp',
            'high' => 'Cao',
            'low' => 'Thấp',
            default => 'Trung bình',
        };
    }

    /**
     * Format deadline của task ưu tiên.
     *
     * @param \DateTimeImmutable|null $dueDate Ngày đến hạn hoặc null.
     * @param \DateTimeImmutable $today Ngày hiện tại theo timezone Dashboard.
     * @return string Nhãn deadline.
     */
    private function formatDashboardDueDate(?\DateTimeImmutable $dueDate, \DateTimeImmutable $today): string
    {
        if ($dueDate === null) {
            return 'Chưa có hạn';
        }

        $days = (int) $today->diff($dueDate)->format('%r%a');

        return match (true) {
            $days < 0 => 'Quá hạn ' . abs($days) . ' ngày',
            $days === 0 => 'Hôm nay',
            $days === 1 => 'Ngày mai',
            default => $dueDate->format('d/m/Y'),
        };
    }

    /**
     * Ước lượng tiến độ task theo trạng thái vì bảng tasks chưa có cột progress.
     *
     * @param array<string, mixed> $task Dữ liệu task từ DB.
     * @return int Tiến độ ước lượng 0-100.
     */
    private function estimateTaskProgress(array $task): int
    {
        if ((int) ($task['status_is_done'] ?? 0) === 1) {
            return 100;
        }

        $slug = (string) ($task['status_slug'] ?? '');

        return match (true) {
            str_contains($slug, 'review'), str_contains($slug, 'testing'), str_contains($slug, 'qa') => 76,
            str_contains($slug, 'development'), str_contains($slug, 'progress'), str_contains($slug, 'etl') => 58,
            str_contains($slug, 'analysis'), str_contains($slug, 'model') => 38,
            str_contains($slug, 'blocked') => 28,
            str_contains($slug, 'backlog'), str_contains($slug, 'todo') => 18,
            default => 45,
        };
    }

    /**
     * Chọn tone màu cho task ưu tiên.
     *
     * @param string $priority Priority raw của task.
     * @param \DateTimeImmutable|null $dueDate Ngày đến hạn hoặc null.
     * @param \DateTimeImmutable $today Ngày hiện tại theo timezone Dashboard.
     * @return string Tone CSS của dashboard.
     */
    private function dashboardTaskTone(string $priority, ?\DateTimeImmutable $dueDate, \DateTimeImmutable $today): string
    {
        if ($dueDate !== null) {
            $days = (int) $today->diff($dueDate)->format('%r%a');
            if ($days < 0 || $priority === 'urgent') {
                return 'red';
            }
            if ($days <= 1 || $priority === 'high') {
                return 'amber';
            }
        }

        return match ($priority) {
            'urgent' => 'red',
            'high' => 'amber',
            'low' => 'green',
            default => 'blue',
        };
    }

    /**
     * Chuẩn hóa filter thời gian của biểu đồ nhịp công việc.
     *
     * @param mixed $period Giá trị từ query string.
     * @return string 7d hoặc 30d.
     */
    private function normalizeTaskTrendPeriod($period): string
    {
        $period = (string) $period;

        return match ($period) {
            '30d', 'month' => '30d',
            default => '7d',
        };
    }

    /**
     * Tính khoảng ngày cho filter nhịp công việc.
     *
     * @return array{0:\DateTimeImmutable, 1:\DateTimeImmutable}
     */
    private function taskTrendDateRange(string $period): array
    {
        $timezone = new \DateTimeZone(self::DASHBOARD_TIMEZONE);
        $endAt = (new \DateTimeImmutable('today', $timezone))->setTime(23, 59, 59);

        $days = $period === '30d' ? 30 : 7;

        return [
            $endAt->modify('-' . ($days - 1) . ' days')->setTime(0, 0, 0),
            $endAt,
        ];
    }

    /**
     * Tạo bộ lọc quyền xem task giống TaskController để dashboard không lộ dữ liệu.
     *
     * @return array<string, mixed>
     */
    private function taskVisibilityFilters(): array
    {
        if (AuthHelper::can('tasks.view.all')) {
            return [];
        }

        return [
            'visibility_user_id' => AuthHelper::id(),
            'visibility_project' => AuthHelper::can('tasks.project'),
            'visibility_own' => AuthHelper::can('tasks.view.own'),
        ];
    }
}

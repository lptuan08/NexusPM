<?php
// Cấu hình hiển thị trạng thái và độ ưu tiên (tương tự như detail.php để giữ nhất quán)
$projectStatusMap = [
    1           => ['text' => 'Mới', 'class' => 'bg-info'],
    2           => ['text' => 'Đang thực hiện', 'class' => 'bg-primary'],
    3           => ['text' => 'Hoàn thành', 'class' => 'bg-success'],
    'planning'  => ['text' => 'Lên kế hoạch', 'class' => 'bg-info'],
    'active'    => ['text' => 'Đang thực hiện', 'class' => 'bg-primary'],
    'completed' => ['text' => 'Hoàn thành', 'class' => 'bg-success'],
];

$priorityMap = [
    'high'   => 'bg-danger',
    'medium' => 'bg-warning',
    'low'    => 'bg-secondary',
];

$taskStatusMap = [
    'todo'        => ['text' => 'Chưa làm', 'class' => 'bg-secondary'],
    'in_progress' => ['text' => 'Đang làm', 'class' => 'bg-primary'],
    'done'        => ['text' => 'Xong', 'class' => 'bg-success'],
];

// Dữ liệu mẫu cho Dự án trọng tâm
$featuredProjects = [
    [
        'name' => 'Phát triển ứng dụng Nexus',
        'desc' => 'Xây dựng hệ thống quản lý dự án nội bộ cho doanh nghiệp.',
        'progress' => 75,
        'daysLeft' => 2,
        'icon' => 'smartphone',
        'bg' => 'project-icon-bg-indigo',
        'bar' => 'progress-bar-indigo'
    ],
    [
        'name' => 'Nâng cấp API Backend',
        'desc' => 'Tối ưu hóa tốc độ xử lý dữ liệu và bảo mật hệ thống.',
        'progress' => 40,
        'daysLeft' => 10,
        'icon' => 'server',
        'bg' => 'project-icon-bg-rose',
        'bar' => 'progress-bar-rose'
    ]
];

// Dữ liệu mẫu cho Công việc của tôi (Modern Checklist)
$myTasks = [
    ['title' => 'Thiết kế UI Dashboard', 'project' => 'NexusPM', 'prio' => 'high', 'due' => 'Hôm nay', 'done' => false],
    ['title' => 'Fix lỗi CSS trên Mobile', 'project' => 'Website Công ty', 'prio' => 'medium', 'due' => 'Ngày mai', 'done' => false],
    ['title' => 'Viết tài liệu hướng dẫn', 'project' => 'Đào tạo', 'prio' => 'low', 'due' => '15/10', 'done' => true],
    ['title' => 'Kiểm tra bảo mật API', 'project' => 'NexusPM', 'prio' => 'high', 'due' => 'Hôm nay', 'done' => false],
];
?>

<!-- BREADCRUMB -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>" class="text-decoration-none text-slate-500 hover-text-primary">PMS</a>
        <span class="mx-2 text-slate-400 d-flex align-items-center" style="width: 16px;"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="fw-medium text-slate-800 fs-5">Tổng quan</span>
    </div>
</div>

<div class="container-fluid p-0">
    <!-- Row 1: KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-5 kpi-card kpi-gradient-1 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="card-title text-white text-opacity-75 fw-medium mb-1">Nhân viên</h6>
                        <p class="card-text fs-2 fw-bold text-white mb-0">120</p>
                    </div>
                    <div class="kpi-icon-shape"><i data-lucide="users" class="text-white"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-5 kpi-card kpi-gradient-2 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="card-title text-white text-opacity-75 fw-medium mb-1">Tổng dự án</h6>
                        <p class="card-text fs-2 fw-bold text-white mb-0">45</p>
                    </div>
                    <div class="kpi-icon-shape"><i data-lucide="folder-open" class="text-white"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-5 kpi-card kpi-gradient-3 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="card-title text-white text-opacity-75 fw-medium mb-1">Đang chạy</h6>
                        <p class="card-text fs-2 fw-bold text-white mb-0">28</p>
                    </div>
                    <div class="kpi-icon-shape"><i data-lucide="activity" class="text-white"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-5 kpi-card kpi-gradient-4 h-100">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div>
                        <h6 class="card-title text-white text-opacity-75 fw-medium mb-1">Quá hạn</h6>
                        <p class="card-text fs-2 fw-bold text-white mb-0">07</p>
                    </div>
                    <div class="kpi-icon-shape"><i data-lucide="alert-triangle" class="text-white"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Main Content Area -->
    <div class="row g-4">
        <!-- Left Column: Projects and Tasks -->
        <div class="col-lg-8">
            <!-- Projects Section - Visual Cards -->
            <section class="mb-5">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Dự án trọng tâm</h5>
                        <p class="text-muted small">Các dự án bạn đang tham gia</p>
                    </div>
                    <button class="btn btn-link text-primary fw-semibold text-decoration-none p-0">Xem tất cả</button>
                </div>
                
                <div class="row g-4">
                    <?php foreach($featuredProjects as $proj): ?>
                    <div class="col-md-6">
                        <div class="project-card bg-white p-4 shadow-sm h-100">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="project-icon-wrapper <?= $proj['bg'] ?>">
                                    <i data-lucide="<?= $proj['icon'] ?>"></i>
                                </div>
                                <span class="badge rounded-pill <?= $proj['daysLeft'] <= 3 ? 'bg-danger-subtle text-danger' : 'bg-light text-muted' ?> d-flex align-items-center">
                                    <i data-lucide="clock" size="12" class="me-1"></i> Còn <?= $proj['daysLeft'] ?> ngày
                                </span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1"><?= $proj['name'] ?></h6>
                            <p class="text-muted small mb-4 line-clamp-2"><?= $proj['desc'] ?></p>
                            
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-xs fw-bold text-muted uppercase">Tiến độ</span>
                                <span class="text-xs fw-bold text-dark"><?= $proj['progress'] ?>%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar <?= $proj['bar'] ?>" style="width: <?= $proj['progress'] ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Tasks Section - Modern Checklist -->
            <section class="mb-5">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Việc cần làm hôm nay</h5>
                        <p class="text-muted small mb-0">Bạn còn <span class="fw-bold text-primary-600">3</span> việc chưa hoàn thành</p>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= URLROOT ?>/cong-viec/them-moi" class="btn btn-primary btn-sm rounded-pill">
                            <i data-lucide="plus" size="16"></i>
                            <span>Thêm công việc</span>
                        </a>
                        <a href="<?= URLROOT ?>/cong-viec" class="btn btn-link text-primary-600 fw-semibold text-decoration-none p-0 hover-underline">Xem tất cả</a>
                    </div>
                </div>

                <div class="task-checklist-container bg-white rounded-5 shadow-sm p-3">
                    <div class="row g-3">
                        <?php foreach($myTasks as $t): ?>
                        <div class="col-12">
                            <div class="task-card p-3 rounded-4 <?= $t['prio'] == 'high' ? 'task-prio-high' : ($t['prio'] == 'medium' ? 'task-prio-medium' : 'task-prio-low') ?>">
                                <input class="form-check-input custom-checkbox" type="checkbox" <?= $t['done'] ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold <?= $t['done'] ? 'text-decoration-line-through text-slate-500' : 'text-dark' ?>"><?= $t['title'] ?></h6>
                                    <div class="d-flex align-items-center gap-3 mt-1">
                                        <span class="text-xs text-slate-500 d-flex align-items-center"><i data-lucide="briefcase" size="14" class="me-1"></i><?= $t['project'] ?></span>
                                        <span class="text-xs text-slate-500 d-flex align-items-center"><i data-lucide="calendar" size="12" class="me-1"></i><?= $t['due'] ?></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill <?= $priorityMap[$t['prio']] ?> text-uppercase px-2 py-1" style="font-size: 0.6rem; letter-spacing: 0.5px;">
                                        <?= $t['prio'] ?>
                                    </span>
                                    <div class="dropdown">
                                        <button class="btn btn-icon-google p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 32px; height: 32px;">
                                            <i data-lucide="more-vertical" size="16"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li><a class="dropdown-item small" href="#"><i data-lucide="edit-3" size="14" class="me-2"></i>Chỉnh sửa</a></li>
                                            <li><a class="dropdown-item small text-danger" href="#">Xóa</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column: Recent Activities -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-5 mb-4 overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 pb-2">
                    <h5 class="fw-bold text-dark mb-0">Hoạt động gần đây</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <!-- Placeholder data -->
                        <?php 
                        $activities = [
                            ['name' => 'Nguyễn An', 'act' => 'vừa hoàn thành công việc', 'target' => 'Thiết kế UI', 'time' => '5 phút trước'],
                            ['name' => 'Trần Bình', 'act' => 'đã được giao dự án', 'target' => 'NexusPM', 'time' => '12 phút trước'],
                            ['name' => 'Lê Chi', 'act' => 'vừa cập nhật tiến độ', 'target' => 'Module Báo cáo', 'time' => '45 phút trước'],
                            ['name' => 'Phạm Duy', 'act' => 'vừa gia nhập đội ngũ', 'target' => '', 'time' => '2 giờ trước'],
                            ['name' => 'Hoàng Yến', 'act' => 'đã gửi phê duyệt', 'target' => 'Hợp đồng dự án A', 'time' => '5 giờ trước'],
                        ];
                        foreach ($activities as $item): ?>
                        <li class="list-group-item d-flex align-items-center gap-3 py-3 px-4">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($item['name']) ?>&background=random&color=fff&rounded=true&size=40" 
                                 alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <p class="mb-0 text-dark small"><span class="fw-bold"><?= $item['name'] ?></span> <?= $item['act'] ?> <span class="text-primary fw-medium"><?= $item['target'] ?></span></p>
                                <span class="text-muted" style="font-size: 0.7rem;"><?= $item['time'] ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
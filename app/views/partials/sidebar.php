    <?php
    // Lấy URI hiện tại để xử lý trạng thái active
    $currentUri = \App\core\Request::uri();
    $user = \App\core\Session::get('user', []);
    $canViewDashboard = \App\helpers\AuthHelper::canAny([
        'dashboard.view.all',
        'dashboard.view.own'
    ]);
    $canViewProjects = \App\helpers\AuthHelper::canAny([
        'projects.view.all',
        'projects.view.joined'
    ]);
    $canViewTasks = \App\helpers\AuthHelper::canAny([
        'tasks.project',
        'tasks.view.all',
        'tasks.view.own'
    ]);
    $canViewUsers = \App\helpers\AuthHelper::can('users.view.all');
    $canViewJobTitles = \App\helpers\AuthHelper::can('job_titles.view.all');
    $canViewProjectStatuses = \App\helpers\AuthHelper::can('project_statuses.view.all');
    $canViewTaskStatuses = \App\helpers\AuthHelper::can('task_statuses.view.all');
    $canViewRoles = \App\helpers\AuthHelper::canAny([
        'roles.view.all',
        'roles.update_permissions.all'
    ]);
    $canViewSettings = \App\helpers\AuthHelper::canAny([
        'settings.view.all',
        'job_titles.view.all',
        'project_statuses.view.all',
        'task_statuses.view.all',
        'roles.view.all',
        'roles.update_permissions.all'
    ]);
    $homeUrl = URLROOT . '/';
    if (!$canViewDashboard) {
        if ($canViewProjects) {
            $homeUrl = URLROOT . '/projects';
        } elseif ($canViewTasks) {
            $homeUrl = URLROOT . '/tasks';
        } elseif ($canViewUsers) {
            $homeUrl = URLROOT . '/users';
        } elseif ($canViewSettings) {
            $homeUrl = URLROOT . '/settings';
        } else {
            $homeUrl = URLROOT . '/account/password';
        }
    }

    $tasksUrl = URLROOT . '/tasks';
    $selectedTaskProjectId = (int) \App\core\Session::get('selected_project_id', 0);
    if ($selectedTaskProjectId > 0) {
        $tasksUrl = URLROOT . '/tasks/kanban';
    }
    ?>
    <!-- KÊNH SIDEBAR -->
    <aside id="sidebar-container" class="sidebar-wrapper">
        <div class="sidebar-inner">
            <div class="sidebar-logo-container flex-shrink-0">
                <a href="<?= $homeUrl ?>" class="d-flex align-items-center text-decoration-none">
                    <img src="<?= URLROOT; ?>/assets/images/logo/logo_nexus.svg" alt="NexusPM Logo" class="sidebar-logo">
                </a>
            </div>

            <nav class="flex-grow-1 overflow-auto d-flex flex-column gap-1 mt-4">
                <!-- NHÓM DỰ ÁN & CÔNG VIỆC -->
                <!-- <div class="sidebar-section-title mt-2 text-xs fw-bold text-slate-400">
                    Dự án & Công việc
                </div> -->

                <?php if ($canViewDashboard): ?>
                <a href="<?= URLROOT ?>/" class="nav-link-custom <?= $currentUri === '/' ? 'active' : '' ?>" title="Tổng quan">
                    <i data-lucide="layout-grid"></i>
                    <span class="nav-text">Tổng quan</span>
                </a>
                <?php endif; ?>

                <?php if ($canViewProjects): ?>
                <a href="<?= URLROOT ?>/projects" class="nav-link-custom <?= str_contains($currentUri, '/projects') ? 'active' : '' ?>" title="Dự án">
                    <i data-lucide="folder-kanban"></i>
                    <span class="nav-text">Dự án</span>
                </a>
                <?php endif; ?>

                <?php if ($canViewTasks): ?>
                <a href="<?= $tasksUrl ?>" class="nav-link-custom <?= str_contains($currentUri, '/tasks') ? 'active' : '' ?>" title="Công việc">
                    <i data-lucide="check-square"></i>
                    <span class="nav-text">Công việc</span>
                </a>
                <?php endif; ?>

                <?php if ($canViewUsers): ?>
                <a href="<?= URLROOT ?>/users" class="nav-link-custom <?= str_contains($currentUri, '/users') ? 'active' : '' ?>" title="Nhân viên">
                    <i data-lucide="users"></i>
                    <span class="nav-text">Nhân viên</span>
                </a>
                <?php endif; ?>

                <?php if ($canViewSettings): ?>
                    <!-- NHÓM HỆ THỐNG -->
                    <div class="sidebar-section-title mt-3 text-xs text-slate-400">
                        <a href="<?= URLROOT ?>/settings" class="text-decoration-none text-slate-400 hover-text-primary" title="Hệ thống">
                            Hệ thống
                        </a>
                    </div>

                    <?php if ($canViewJobTitles): ?>
                    <a href="<?= URLROOT ?>/settings/job" class="nav-link-custom <?= $currentUri === '/settings/job' ? 'active' : '' ?>" title="Chức danh nhân viên">
                        <i data-lucide="briefcase"></i>
                        <span class="nav-text">Chức danh nhân viên</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($canViewProjectStatuses): ?>
                    <a href="<?= URLROOT ?>/settings/project" class="nav-link-custom <?= $currentUri === '/settings/project' ? 'active' : '' ?>" title="Trạng thái dự án">
                        <i data-lucide="tags"></i>
                        <span class="nav-text">Trạng thái dự án</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($canViewTaskStatuses): ?>
                    <a href="<?= URLROOT ?>/settings/task" class="nav-link-custom <?= $currentUri === '/settings/task' ? 'active' : '' ?>" title="Trạng thái công việc">
                        <i data-lucide="list-todo"></i>
                        <span class="nav-text">Trạng thái công việc</span>
                    </a>
                    <?php endif; ?>

                    <?php if ($canViewRoles): ?>
                    <a href="<?= URLROOT ?>/admin/roles" class="nav-link-custom <?= str_contains($currentUri, '/admin/roles') ? 'active' : '' ?>" title="Vai trò & phân quyền">
                        <i data-lucide="shield-check"></i>
                        <span class="nav-text">Vai trò & phân quyền</span>
                    </a>
                    <?php endif; ?>
                <?php endif; ?>
            </nav>
        </div>
    </aside>

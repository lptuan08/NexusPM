    <?php
    // Lấy URI hiện tại để xử lý trạng thái active
    $currentUri = \App\core\Request::uri();
    // Kiểm tra quyền admin để hiển thị menu thiết lập
    $user = \App\core\Session::get('user', []);
    $isAdmin = ($user['role'] ?? '') === 'admin';
    ?>
    <!-- KÊNH SIDEBAR -->
    <aside id="sidebar-container" class="sidebar-wrapper">
        <div class="sidebar-inner">
            <div class="sidebar-logo-container flex-shrink-0">
                <a href="<?= URLROOT ?>" class="d-flex align-items-center text-decoration-none">
                    <img src="<?= URLROOT; ?>/assets/images/logo/logo_nexus.svg" alt="NexusPM Logo" class="sidebar-logo">
                </a>
            </div>

            <nav class="flex-grow-1 overflow-auto d-flex flex-column gap-1 mt-4">
                <a href="<?= URLROOT ?>/" class="nav-link-custom <?= $currentUri === '/' ? 'active' : '' ?>" title="Tổng quan">
                    <i data-lucide="layout-grid"></i>
                    <span class="nav-text">Tổng quan</span>
                </a>

                <a href="<?= URLROOT ?>/tasks" class="nav-link-custom <?= str_contains($currentUri, '/tasks') ? 'active' : '' ?>" title="Công việc">
                    <i data-lucide="folder-kanban"></i>
                    <span class="nav-text">Công việc</span>
                </a>

                <a href="<?= URLROOT ?>/projects" class="nav-link-custom <?= str_contains($currentUri, '/projects') ? 'active' : '' ?>" title="Dự án">
                    <i data-lucide="briefcase"></i>
                    <span class="nav-text">Dự án</span>
                </a>

                <a href="<?= URLROOT ?>/users" class="nav-link-custom <?= str_contains($currentUri, '/users') ? 'active' : '' ?>" title="Nhân viên">
                    <i data-lucide="users"></i>
                    <span class="nav-text">Nhân viên</span>
                </a>

                <?php if ($isAdmin): ?>
                    <!-- Nhóm thiết lập hệ thống -->
                    <div class="sidebar-section-label mt-4 px-3 mb-2 text-xs text-uppercase fw-bold text-slate-400">
                        Hệ thống
                    </div>
                    
                    <a href="<?= URLROOT ?>/settings/project" class="nav-link-custom <?= $currentUri === '/settings/project' ? 'active' : '' ?>" title="Trạng thái dự án">
                        <i data-lucide="settings-2"></i>
                        <span class="nav-text">Trạng thái dự án</span>
                    </a>

                    <a href="<?= URLROOT ?>/settings/task" class="nav-link-custom <?= $currentUri === '/settings/task' ? 'active' : '' ?>" title="Trạng thái công việc">
                        <i data-lucide="list-checks"></i>
                        <span class="nav-text">Trạng thái công việc</span>
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </aside>

    <!-- KÊNH SIDEBAR -->
    <aside id="sidebar-container" class="sidebar-wrapper">
        <div class="sidebar-inner">
            <div class="sidebar-logo-container flex-shrink-0">
                <a href="<?= URLROOT ?>" class="d-flex align-items-center text-decoration-none">
                    <img src="<?= URLROOT; ?>/assets/images/logo/logo_nexus.svg" alt="NexusPM Logo" class="sidebar-logo">
                </a>
            </div>

            <nav class="flex-grow-1 overflow-auto d-flex flex-column gap-1 mt-4">
                <a href="<?= URLROOT ?>/" class="nav-link-custom" title="Tổng quan">
                    <i data-lucide="layout-grid"></i>
                    <span class="nav-text">Tổng quan</span>
                </a>

                <a href="<?= URLROOT ?>/tasks" class="nav-link-custom" title="Công việc">
                    <i data-lucide="folder-kanban"></i>
                    <span class="nav-text">Công việc</span>
                </a>

                <a href="<?= URLROOT ?>/projects" class="nav-link-custom" title="Dự án">
                    <i data-lucide="briefcase"></i>
                    <span class="nav-text">Dự án</span>
                </a>

                <a href="<?= URLROOT ?>/users" class="nav-link-custom" title="Nhân viên">
                    <i data-lucide="users"></i>
                    <span class="nav-text">Nhân viên</span>
                </a>
            </nav>
        </div>
    </aside>

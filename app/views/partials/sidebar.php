    <!-- KÊNH SIDEBAR -->
    <aside id="sidebar-container" class="sidebar-wrapper">
        <div class="sidebar-inner">
            <div class="sidebar-logo-container flex-shrink-0 mb-3">
                <div class="rounded d-flex align-items-center justify-content-center flex-shrink-0"
                    style="width: 32px; height: 32px; color: var(--primary-600);">
                    <i data-lucide="layout-dashboard"></i>
                </div>
                <span class="sidebar-title-text">PMS</span>
            </div>

            <nav class="flex-grow-1 overflow-auto d-flex flex-column gap-1">
                <a href="<?= URLROOT ?>/trang-chu" class="nav-link-custom" title="Tổng quan">
                    <i data-lucide="layout-grid"></i>
                    <span class="nav-text">Tổng quan</span>
                </a>

                <a href="<?= URLROOT ?>/cong-viec" class="nav-link-custom" title="Công việc">
                    <i data-lucide="folder-kanban"></i>
                    <span class="nav-text">Công việc</span>
                </a>

                <a href="<?= URLROOT ?>/du-an" class="nav-link-custom" title="Dự án">
                    <i data-lucide="briefcase"></i>
                    <span class="nav-text">Dự án</span>
                </a>

                <a href="<?= URLROOT ?>/nguoi-dung" class="nav-link-custom" title="Nhân viên">
                    <i data-lucide="users"></i>
                    <span class="nav-text">Nhân viên</span>
                </a>

                <div class="mt-3 mb-1 sidebar-section-title"></div>
                <p class="mb-1 mt-1 fw-medium sidebar-section-title"
                    style="font-size: 0.875rem; color: var(--slate-500);">Hệ thống</p>

                <a href="#" class="nav-link-custom" title="Cài đặt">
                    <i data-lucide="settings-2"></i>
                    <span class="nav-text">Cài đặt</span>
                </a>
            </nav>
        </div>
    </aside>
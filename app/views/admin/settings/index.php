<?php
/**
 * Giao diện chính của trung tâm Thiết lập hệ thống
 */
?>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <span class="page-title">Hệ thống</span>
    </div>
</div>

<div class="row g-4 mt-2">
    <!-- Nhóm: Quản lý Nhân sự -->
    <div class="col-md-6 col-lg-4">
        <div class="ui-card h-100">
            <div class="ui-card-header bg-white d-flex align-items-center gap-3">
                <div class="btn-icon-google bg-primary-50 text-primary-600">
                    <i data-lucide="users" size="20"></i>
                </div>
                <h6 class="mb-0 fw-bold text-slate-800">Tổ chức & Nhân sự</h6>
            </div>
            <div class="ui-card-body p-0">
                <div class="info-list">
                    <a href="<?= URLROOT ?>/admin/roles" class="info-list-item text-decoration-none hover-bg-slate-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-slate-500"><i data-lucide="shield-check" size="18"></i></div>
                            <span class="text-slate-700 fw-medium">Vai trò & Quyền hạn</span>
                        </div>
                        <i data-lucide="chevron-right" class="text-slate-300" size="16"></i>
                    </a>
                    <a href="<?= URLROOT ?>/settings/job" class="info-list-item text-decoration-none hover-bg-slate-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-slate-500"><i data-lucide="briefcase" size="18"></i></div>
                            <span class="text-slate-700 fw-medium">Chức danh nhân viên</span>
                        </div>
                        <i data-lucide="chevron-right" class="text-slate-300" size="16"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Nhóm: Quy trình & Dự án -->
    <div class="col-md-6 col-lg-4">
        <div class="ui-card h-100">
            <div class="ui-card-header bg-white d-flex align-items-center gap-3">
                <div class="btn-icon-google bg-emerald-50 text-emerald-600">
                    <i data-lucide="layers" size="20"></i>
                </div>
                <h6 class="mb-0 fw-bold text-slate-800">Quy trình & Dự án</h6>
            </div>
            <div class="ui-card-body p-0">
                <div class="info-list">
                    <a href="<?= URLROOT ?>/settings/project" class="info-list-item text-decoration-none hover-bg-slate-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-slate-500"><i data-lucide="git-pull-request" size="18"></i></div>
                            <span class="text-slate-700 fw-medium">Trạng thái dự án</span>
                        </div>
                        <i data-lucide="chevron-right" class="text-slate-300" size="16"></i>
                    </a>
                    <a href="<?= URLROOT ?>/settings/task" class="info-list-item text-decoration-none hover-bg-slate-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-slate-500"><i data-lucide="list-todo" size="18"></i></div>
                            <span class="text-slate-700 fw-medium">Trạng thái công việc</span>
                        </div>
                        <i data-lucide="chevron-right" class="text-slate-300" size="16"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Nhóm: Bảo trì Hệ thống -->
    <div class="col-md-6 col-lg-4">
        <div class="ui-card h-100">
            <div class="ui-card-header bg-white d-flex align-items-center gap-3">
                <div class="btn-icon-google bg-red-50 text-red-600">
                    <i data-lucide="settings-2" size="20"></i>
                </div>
                <h6 class="mb-0 fw-bold text-slate-800">Bảo trì & Nhật ký</h6>
            </div>
            <div class="ui-card-body p-0">
                <div class="info-list">
                    <span class="info-list-item text-decoration-none text-muted">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-slate-500"><i data-lucide="activity" size="18"></i></div>
                            <span class="text-slate-700 fw-medium">Nhật ký hoạt động</span>
                        </div>
                        <span class="text-slate-400 small">Sắp có</span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Giao diện chính của trung tâm Thiết lập hệ thống
 */
?>

<style>
    .setting-card-link {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none !important;
        display: block;
        height: 100%;
    }

    .setting-card-link:hover {
        transform: translateY(-6px);
    }

    .setting-card-link:hover .ui-card {
        box-shadow: var(--google-shadow-card);
        border-color: var(--primary-200);
        background-color: #fff;
    }

    .setting-card-link:hover h6 {
        color: var(--primary-600) !important;
    }

    .setting-card-link:hover p {
        color: var(--slate-700) !important;
    }

    .setting-icon-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.03);
    }

    .setting-card-link:hover .setting-icon-wrapper {
        transform: scale(1.1);
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <span class="page-title">Hệ thống</span>
    </div>
</div>

<div class="row g-4">
    <!-- Chức năng: Nhân viên -->
    <div class="col-md-6 col-lg-4 col-xl-3">
        <a href="<?= URLROOT ?>/users" class="setting-card-link">
            <div class="ui-card h-100 p-4 d-flex flex-column align-items-center text-center">
                <div class="setting-icon-wrapper" style="background-color: #ecfeff; color: #0891b2;">
                    <i data-lucide="users" size="40"></i>
                </div>
                <h6 class="fw-bold text-slate-800 mb-2">Nhân viên</h6>
                <p class="text-slate-500 small mb-0">Quản lý hồ sơ nhân sự, tài khoản và thông tin liên lạc</p>
            </div>
        </a>
    </div>

    <!-- Chức năng: Chức danh -->
    <div class="col-md-6 col-lg-4 col-xl-3">
        <a href="<?= URLROOT ?>/settings/job" class="setting-card-link">
            <div class="ui-card h-100 p-4 d-flex flex-column align-items-center text-center">
                <div class="setting-icon-wrapper" style="background-color: #f5f3ff; color: #8b5cf6;">
                    <i data-lucide="briefcase" size="40"></i>
                </div>
                <h6 class="fw-bold text-slate-800 mb-2">Chức danh nhân viên</h6>
                <p class="text-slate-500 small mb-0">Quản lý danh sách các vị trí công tác trong tổ chức</p>
            </div>
        </a>
    </div>

    <!-- Chức năng: Trạng thái dự án -->
    <div class="col-md-6 col-lg-4 col-xl-3">
        <a href="<?= URLROOT ?>/settings/project" class="setting-card-link">
            <div class="ui-card h-100 p-4 d-flex flex-column align-items-center text-center">
                <div class="setting-icon-wrapper" style="background-color: #ecfdf5; color: #10b981;">
                    <i data-lucide="git-pull-request" size="40"></i>
                </div>
                <h6 class="fw-bold text-slate-800 mb-2">Trạng thái dự án</h6>
                <p class="text-slate-500 small mb-0">Định nghĩa vòng đời và các giai đoạn thực hiện dự án</p>
            </div>
        </a>
    </div>

    <!-- Chức năng: Trạng thái công việc -->
    <div class="col-md-6 col-lg-4 col-xl-3">
        <a href="<?= URLROOT ?>/settings/task" class="setting-card-link">
            <div class="ui-card h-100 p-4 d-flex flex-column align-items-center text-center">
                <div class="setting-icon-wrapper" style="background-color: #fff7ed; color: #f97316;">
                    <i data-lucide="list-todo" size="40"></i>
                </div>
                <h6 class="fw-bold text-slate-800 mb-2">Trạng thái công việc</h6>
                <p class="text-slate-500 small mb-0">Cấu hình luồng xử lý công việc cho từng loại dự án</p>
            </div>
        </a>
    </div>

    <!-- Chức năng: Vai trò & Phân quyền -->
    <div class="col-md-6 col-lg-4 col-xl-3">
        <a href="<?= URLROOT ?>/admin/roles" class="setting-card-link">
            <div class="ui-card h-100 p-4 d-flex flex-column align-items-center text-center">
                <div class="setting-icon-wrapper" style="background-color: #eef2ff; color: #4f46e5;">
                    <i data-lucide="shield-check" size="40"></i>
                </div>
                <h6 class="fw-bold text-slate-800 mb-2">Vai trò & Phân quyền</h6>
                <p class="text-slate-500 small mb-0">Thiết lập nhóm người dùng và phân quyền truy cập chức năng</p>
            </div>
        </a>
    </div>

    <!-- Chức năng: Nhật ký hệ thống -->
    <div class="col-md-6 col-lg-4 col-xl-3">
        <a href="#" class="setting-card-link opacity-75 cursor-not-allowed">
            <div class="ui-card h-100 p-4 d-flex flex-column align-items-center text-center">
                <div class="setting-icon-wrapper" style="background-color: #fff1f2; color: #f43f5e;">
                    <i data-lucide="activity" size="40"></i>
                </div>
                <h6 class="fw-bold text-slate-800 mb-2">Nhật ký hoạt động</h6>
                <div class="badge bg-slate-100 text-slate-500 fw-normal">Sắp ra mắt</div>
            </div>
        </a>
    </div>
</div>

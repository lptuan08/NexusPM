<?php
/**
 * Giao diện chỉnh sửa dự án
 * 
 * @var array $project Dữ liệu dự án hiện tại
 * @var array $ownerOptions Danh sách nhân viên làm owner
 * @var array $statusOptions Danh sách trạng thái dự án
 * @var array $errors Mảng lỗi validation
 * @var array $old Dữ liệu cũ khi submit lỗi
 * @var string $action_url URL xử lý form
 */

$project = $project ?? [];
$old = $old ?? [];
$errors = $errors ?? [];

// Ưu tiên lấy dữ liệu từ old (nếu vừa submit lỗi) sau đó mới đến dữ liệu gốc của dự án
$getValue = function($field) use ($project, $old) {
    return $old[$field] ?? $project[$field] ?? '';
};
?>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <a href="<?= URLROOT; ?>/projects/<?= (int) ($project['id'] ?? 0) ?>" class="text-decoration-none text-slate-500 hover-text-primary"><?= htmlspecialchars((string) ($project['name'] ?? 'Chi tiết'), ENT_QUOTES, 'UTF-8') ?></a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Chỉnh sửa</span>
    </div>

    <div class="page-actions">
        <a href="<?= URLROOT ?>/projects/<?= (int) ($project['id'] ?? 0) ?>" class="btn btn-outline-secondary px-3">
            <i data-lucide="x"></i>
            <span>Hủy bỏ</span>
        </a>
    </div>
</div>

<div class="form-main-container">
    <div class="ui-card shadow-sm border-0">
        <div class="ui-card-header bg-white border-bottom py-3 px-4">
            <h5 class="mb-0 fw-bold text-slate-800">Thông tin dự án</h5>
        </div>
        <div class="ui-card-body p-4">
            <form action="<?= htmlspecialchars((string) $action_url, ENT_QUOTES, 'UTF-8') ?>" method="POST">
                <?php App\helpers\SecurityHelper::csrfInput(); ?>
                
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên dự án <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
                                   value="<?= htmlspecialchars((string) $getValue('name'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nhập tên dự án">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars((string) $errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mô tả dự án</label>
                            <textarea name="description" class="form-control" rows="6" 
                                      placeholder="Mô tả chi tiết mục tiêu dự án..."><?= htmlspecialchars((string) $getValue('description'), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Trưởng dự án <span class="text-danger">*</span></label>
                            <select name="owner_id" class="form-select <?= isset($errors['owner_id']) ? 'is-invalid' : '' ?>">
                                <option value="">-- Chọn người phụ trách --</option>
                                <?php foreach ($ownerOptions as $opt): ?>
                                    <option value="<?= (int) ($opt['id'] ?? 0) ?>" <?= (int) $getValue('owner_id') === (int) ($opt['id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($opt['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($opt['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['owner_id'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars((string) $errors['owner_id'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status_id" class="form-select <?= isset($errors['status_id']) ? 'is-invalid' : '' ?>">
                                <?php foreach ($statusOptions as $status): ?>
                                    <option value="<?= (int) ($status['id'] ?? 0) ?>" <?= (int) $getValue('status_id') === (int) ($status['id'] ?? 0) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) ($status['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['status_id'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars((string) $errors['status_id'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ngày bắt đầu</label>
                            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars((string) $getValue('start_date'), ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Hạn xử lý (Due Date)</label>
                            <input type="date" name="due_date" class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars((string) $getValue('due_date'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (isset($errors['due_date'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars((string) $errors['due_date'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i data-lucide="save"></i>
                        <span>Lưu thay đổi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

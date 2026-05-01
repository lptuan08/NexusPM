<?php
$isEdit = !empty($project['id']);
$currentStatus = $old['status'] ?? $project['status'] ?? 'planning';
$currentOwnerId = (int) ($old['owner_id'] ?? $project['owner_id'] ?? 0);
$currentName = $old['name'] ?? $project['name'] ?? '';
$currentDescription = $old['description'] ?? $project['description'] ?? '';
$currentStartDate = $old['start_date'] ?? $project['start_date'] ?? '';
$currentDueDate = $old['due_date'] ?? $project['due_date'] ?? '';

$statusOptions = [
    'planning' => 'Lên kế hoạch',
    'active' => 'Đang thực hiện',
    'on_hold' => 'Tạm dừng',
    'completed' => 'Hoàn thành',
];
?>

<style>
    .project-form-container {
        max-width: 960px;
    }

    .project-form-textarea {
        min-height: 220px;
    }

    .project-form-updated {
        white-space: nowrap;
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <?php if ($isEdit): ?>
            <a href="<?= URLROOT ?>/projects/<?= $project['id'] ?>" class="text-decoration-none text-slate-500 hover-text-primary"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></a>
            <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
            <span class="page-title">Chỉnh sửa</span>
        <?php else: ?>
            <span class="page-title">Thêm mới</span>
        <?php endif; ?>
    </div>

    <div class="page-actions">
        <a href="<?= URLROOT ?>/projects" class="btn btn-outline-secondary px-3">
            <i data-lucide="arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>
</div>

<div class="form-main-container project-form-container">
    <div class="ui-card overflow-hidden">
            <div class="ui-card-header d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1 fw-semibold text-slate-900"><?= $isEdit ? 'Chỉnh sửa dự án' : 'Tạo dự án mới' ?></h5>
                    <p class="mb-0 small text-slate-500">Nhập các thông tin cơ bản của dự án theo bố cục đơn giản, rõ ràng và dễ theo dõi.</p>
                </div>
                <?php if ($isEdit && !empty($project['updated_at'])): ?>
                    <div class="text-end text-slate-500 text-xs project-form-updated mt-1">
                        <span class="d-none d-sm-inline">Cập nhật lần cuối:</span> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($project['updated_at'])), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ui-card-body">
                <form action="<?= $action_url ?>" method="POST" autocomplete="off">
                    <?php App\helpers\SecurityHelper::csrfInput(); ?>

                    <div class="row g-4">
                        <!-- Cột trái: Thông tin chính -->
                        <div class="col-lg-6">
                            <div class="form-section h-100">
                                <div class="mb-3">
                                    <label class="form-label">Tên dự án <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                        placeholder="Nhập tên dự án"
                                        value="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Trưởng dự án -->
                                <div class="mb-3">
                                    <label class="form-label">Trưởng dự án <span class="text-danger">*</span></label>
                                    <select name="owner_id" class="form-select <?= isset($errors['owner_id']) ? 'is-invalid' : '' ?>">
                                        <option value="">Chọn người phụ trách chính</option>
                                        <?php foreach (($ownerOptions ?? []) as $owner): ?>
                                            <option value="<?= (int) $owner['id'] ?>" <?= $currentOwnerId === (int) $owner['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($owner['name'], ENT_QUOTES, 'UTF-8') ?><?= !empty($owner['email']) ? ' - ' . htmlspecialchars($owner['email'], ENT_QUOTES, 'UTF-8') : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['owner_id'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['owner_id'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Trạng thái -->
                                <div class="mb-3">
                                    <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>">
                                        <?php foreach ($statusOptions as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $currentStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['status'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Thời gian bắt đầu -->
                                <div class="mb-3">
                                    <label class="form-label">Thời gian bắt đầu</label>
                                    <input
                                        type="date"
                                        name="start_date"
                                        class="form-control"
                                        value="<?= htmlspecialchars($currentStartDate, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <!-- Thời gian kết thúc -->
                                <div class="mb-0">
                                    <label class="form-label">Thời gian kết thúc</label>
                                    <input
                                        type="date"
                                        name="due_date"
                                        class="form-control <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>"
                                        value="<?= htmlspecialchars($currentDueDate, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($errors['due_date'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['due_date'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Cột phải: Mô tả -->
                        <div class="col-lg-6">
                            <div class="form-section h-100 d-flex flex-column">
                                <label class="form-label">Mô tả dự án</label>
                                <textarea
                                    name="description"
                                    class="form-control project-form-textarea flex-grow-1"
                                    placeholder="Mô tả ngắn gọn mục tiêu, phạm vi và kết quả kỳ vọng của dự án"><?= htmlspecialchars($currentDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions-container">
                        <button type="submit" class="btn btn-success px-4">
                            <i data-lucide="save"></i>
                            <?= $isEdit ? 'Lưu cập nhật' : 'Lưu dự án' ?>
                        </button>
                        <a href="<?= URLROOT ?>/projects" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
                    </div>
                </form>
            </div>
    </div>
</div>

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
    .project-editor-card {
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        box-shadow: 0 10px 30px -24px rgba(15, 23, 42, 0.28);
        background: #ffffff;
    }

    .project-editor-muted {
        color: #64748b;
    }

    .project-editor-section {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #f8fafc;
        padding: 1rem;
    }

    .project-editor-label {
        font-size: 0.84rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.45rem;
    }

    .project-editor-input,
    .project-editor-select,
    .project-editor-textarea {
        border: 1px solid #dbe3ef;
        border-radius: 12px;
        background: #ffffff;
        color: #0f172a;
        box-shadow: none;
    }

    .project-editor-input,
    .project-editor-select {
        min-height: 44px;
    }

    .project-editor-textarea {
        min-height: 220px;
        resize: vertical;
    }

    .project-editor-input:focus,
    .project-editor-select:focus,
    .project-editor-textarea:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
    }

    .project-editor-meta {
        border-top: 1px solid #eef2f7;
        padding-top: 1rem;
    }
</style>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/projects" class="text-decoration-none text-slate-500 hover-text-primary">Dự án</a>
        <span class="mx-2 text-slate-400 d-flex align-items-center" style="width: 16px;"><i data-lucide="chevron-right" size="16"></i></span>
        <?php if ($isEdit): ?>
            <a href="<?= URLROOT ?>/projects/<?= $project['id'] ?>" class="text-decoration-none text-slate-500 hover-text-primary"><?= htmlspecialchars($project['name'], ENT_QUOTES, 'UTF-8') ?></a>
            <span class="mx-2 text-slate-400 d-flex align-items-center" style="width: 16px;"><i data-lucide="chevron-right" size="16"></i></span>
            <span class="fw-medium text-slate-800 fs-5">Chỉnh sửa</span>
        <?php else: ?>
            <span class="fw-medium text-slate-800 fs-5">Thêm mới</span>
        <?php endif; ?>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="<?= URLROOT ?>/projects" class="btn btn-outline-secondary px-3">
            <i data-lucide="arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="project-editor-card overflow-hidden">
            <div class="px-4 px-lg-5 py-4 border-bottom border-slate-100 d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1 fw-semibold text-slate-900"><?= $isEdit ? 'Chỉnh sửa dự án' : 'Tạo dự án mới' ?></h5>
                    <p class="mb-0 small project-editor-muted">Nhập các thông tin cơ bản của dự án theo bố cục đơn giản, rõ ràng và dễ theo dõi.</p>
                </div>
                <?php if ($isEdit && !empty($project['updated_at'])): ?>
                    <div class="text-end project-editor-muted opacity-75 mt-1" style="font-size: 0.75rem; white-space: nowrap;">
                        <span class="d-none d-sm-inline">Cập nhật lần cuối:</span> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($project['updated_at'])), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="p-4 p-lg-5">
                <form action="<?= $action_url ?>" method="POST" autocomplete="off">
                    <?php SecurityHelper::csrfInput(); ?>

                    <div class="row g-4">
                        <!-- Cột trái: Thông tin chính -->
                        <div class="col-lg-6">
                            <div class="project-editor-section h-100">
                                <div class="mb-3">
                                    <label class="project-editor-label">Tên dự án <span class="text-danger">*</span></label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control project-editor-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                        placeholder="Nhập tên dự án"
                                        value="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($errors['name'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>

                                <!-- Trưởng dự án -->
                                <div class="mb-3">
                                    <label class="project-editor-label">Trưởng dự án <span class="text-danger">*</span></label>
                                    <select name="owner_id" class="form-select project-editor-select <?= isset($errors['owner_id']) ? 'is-invalid' : '' ?>">
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
                                    <label class="project-editor-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select project-editor-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>">
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
                                    <label class="project-editor-label">Thời gian bắt đầu</label>
                                    <input
                                        type="date"
                                        name="start_date"
                                        class="form-control project-editor-input"
                                        value="<?= htmlspecialchars($currentStartDate, ENT_QUOTES, 'UTF-8') ?>">
                                </div>

                                <!-- Thời gian kết thúc -->
                                <div class="mb-0">
                                    <label class="project-editor-label">Thời gian kết thúc</label>
                                    <input
                                        type="date"
                                        name="due_date"
                                        class="form-control project-editor-input <?= isset($errors['due_date']) ? 'is-invalid' : '' ?>"
                                        value="<?= htmlspecialchars($currentDueDate, ENT_QUOTES, 'UTF-8') ?>">
                                    <?php if (isset($errors['due_date'])): ?>
                                        <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['due_date'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Cột phải: Mô tả -->
                        <div class="col-lg-6">
                            <div class="project-editor-section h-100 d-flex flex-column">
                                <label class="project-editor-label">Mô tả dự án</label>
                                <textarea
                                    name="description"
                                    class="form-control project-editor-textarea flex-grow-1"
                                    placeholder="Mô tả ngắn gọn mục tiêu, phạm vi và kết quả kỳ vọng của dự án"><?= htmlspecialchars($currentDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 pt-4 mt-5 border-top border-slate-100">
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
</div>

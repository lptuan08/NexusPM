<?php
$isEdit = !empty($project['id']);
$pageHeading = $isEdit ? 'Cập nhật dự án' : 'Tạo dự án mới';
$pageDescription = $isEdit
    ? 'Điều chỉnh thông tin cốt lõi để dự án luôn rõ mục tiêu, mốc thời gian và trạng thái triển khai.'
    : 'Thiết lập nhanh một dự án mới với tên, mô tả, trạng thái và các mốc thời gian quan trọng.';

$currentStatus = $old['status'] ?? $project['status'] ?? 'planning';
$currentName = $old['name'] ?? $project['name'] ?? '';
$currentDescription = $old['description'] ?? $project['description'] ?? '';
$currentStartDate = $old['start_date'] ?? $project['start_date'] ?? '';
$currentDueDate = $old['due_date'] ?? $project['due_date'] ?? '';

$statusOptions = [
    'planning' => [
        'label' => 'Lên kế hoạch',
        'hint' => 'Dự án đang ở giai đoạn chuẩn bị phạm vi, nguồn lực và timeline.',
        'color' => '#2563eb',
        'bg' => '#dbeafe',
    ],
    'active' => [
        'label' => 'Đang thực hiện',
        'hint' => 'Dự án đã bắt đầu triển khai với công việc đang được xử lý.',
        'color' => '#0f766e',
        'bg' => '#ccfbf1',
    ],
    'completed' => [
        'label' => 'Hoàn thành',
        'hint' => 'Dự án đã chốt deliverable và không còn đầu việc mở.',
        'color' => '#7c3aed',
        'bg' => '#ede9fe',
    ],
];

$selectedStatus = $statusOptions[$currentStatus] ?? $statusOptions['planning'];
?>

<style>
    .project-form-shell {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.12), transparent 28%),
            linear-gradient(180deg, #f8fbff 0%, #f8fafc 100%);
        margin: -1.5rem;
        padding: 1.5rem;
        min-height: 100%;
    }

    .project-form-header,
    .project-form-card,
    .project-form-sidebar {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1.5rem;
        box-shadow: 0 20px 45px -34px rgba(15, 23, 42, 0.35);
    }

    .project-form-header {
        overflow: hidden;
    }

    .project-form-banner {
        background:
            linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #38bdf8 100%);
        color: #fff;
        position: relative;
    }

    .project-form-banner::after {
        content: "";
        position: absolute;
        inset: auto -10% -48% auto;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .project-form-breadcrumb a {
        color: #64748b;
        text-decoration: none;
    }

    .project-form-breadcrumb .separator {
        color: #94a3b8;
    }

    .project-form-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.5rem 0.85rem;
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .project-form-card,
    .project-form-sidebar {
        padding: 1.75rem;
    }

    .project-form-section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
    }

    .project-form-section-note {
        color: #64748b;
        font-size: 0.92rem;
    }

    .project-form-label {
        font-size: 0.84rem;
        font-weight: 700;
        color: #334155;
        margin-bottom: 0.55rem;
    }

    .project-form-input,
    .project-form-select,
    .project-form-textarea {
        border-radius: 1rem;
        border: 1px solid #dbe3ef;
        background: #fff;
        padding: 0.9rem 1rem;
        box-shadow: none;
    }

    .project-form-input:focus,
    .project-form-select:focus,
    .project-form-textarea:focus {
        border-color: #60a5fa;
        box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.12);
    }

    .project-form-textarea {
        min-height: 150px;
        resize: vertical;
    }

    .project-status-preview,
    .project-help-box {
        border-radius: 1.25rem;
        padding: 1rem 1.1rem;
    }

    .project-help-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
    }

    .project-status-option {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.85rem 0;
        border-bottom: 1px solid #eef2f7;
    }

    .project-status-option:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .project-status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-top: 0.35rem;
        flex-shrink: 0;
    }

    @media (max-width: 991.98px) {
        .project-form-shell {
            margin: -1rem;
            padding: 1rem;
        }
    }
</style>

<div class="project-form-shell">
    <div class="project-form-breadcrumb d-flex align-items-center gap-2 small mb-4">
        <a href="<?= URLROOT ?>/projects">Dự án</a>
        <span class="separator">/</span>
        <span class="text-slate-800 fw-semibold"><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <section class="project-form-header mb-4">
        <div class="project-form-banner p-4 p-lg-5">
            <div class="d-flex flex-column flex-xl-row justify-content-between gap-4 position-relative" style="z-index: 1;">
                <div class="pe-xl-4">
                    <div class="project-form-chip mb-3" style="background: rgba(255,255,255,0.14); color: #fff;">
                        <i data-lucide="<?= $isEdit ? 'pencil-ruler' : 'folder-plus' ?>" style="width:16px;height:16px;"></i>
                        Project Setup
                    </div>

                    <h1 class="h2 fw-bold mb-3"><?= htmlspecialchars($pageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mb-0 text-white-50" style="max-width: 780px;">
                        <?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

                <div class="d-flex flex-wrap align-items-start gap-2 flex-shrink-0">
                    <a href="<?= URLROOT ?>/projects" class="btn btn-light fw-semibold px-3 px-lg-4">
                        <i data-lucide="arrow-left" class="me-2" style="width:18px;height:18px;"></i>
                        Quay lại
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="project-form-card">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div>
                        <div class="project-form-section-title mb-1">Thông tin cốt lõi</div>
                        <div class="project-form-section-note">Điền các trường chính để hệ thống có thể hiển thị dự án nhất quán ở danh sách và trang chi tiết.</div>
                    </div>
                    <div class="project-form-chip" style="background: <?= $selectedStatus['bg'] ?>; color: <?= $selectedStatus['color'] ?>;">
                        <i data-lucide="flag" style="width:16px;height:16px;"></i>
                        <?= htmlspecialchars($selectedStatus['label'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <form action="<?= $action_url ?>" method="POST">
                    <?php SecurityHelper::csrfInput(); ?>

                    <div class="mb-4">
                        <label class="project-form-label">Tên dự án <span class="text-danger">*</span></label>
                        <input
                            type="text"
                            name="name"
                            class="form-control project-form-input <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ví dụ: Thiết kế và phát triển app thương mại điện tử">
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="project-form-label">Mô tả dự án</label>
                        <textarea
                            name="description"
                            class="form-control project-form-textarea"
                            placeholder="Tóm tắt mục tiêu, phạm vi công việc, khách hàng nội bộ hoặc kết quả bạn kỳ vọng cho dự án này."><?= htmlspecialchars($currentDescription, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="project-form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status" class="form-select project-form-select <?= isset($errors['status']) ? 'is-invalid' : '' ?>">
                                <?php foreach ($statusOptions as $value => $option): ?>
                                    <option value="<?= $value ?>" <?= $currentStatus === $value ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['status'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="project-form-label">Ngày bắt đầu</label>
                            <input
                                type="date"
                                name="start_date"
                                class="form-control project-form-input"
                                value="<?= htmlspecialchars($currentStartDate, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="project-form-label">Hạn xử lý</label>
                            <input
                                type="date"
                                name="due_date"
                                class="form-control project-form-input"
                                value="<?= htmlspecialchars($currentDueDate, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 pt-4 border-top">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold">
                            <i data-lucide="save" class="me-2" style="width:18px;height:18px;"></i>
                            <?= $isEdit ? 'Lưu cập nhật' : 'Lưu dự án' ?>
                        </button>
                        <a href="<?= URLROOT ?>/projects" class="btn btn-outline-secondary px-4 py-2 fw-semibold">Hủy bỏ</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="project-form-sidebar mb-4">
                <div class="project-form-section-title mb-3">Xem trước trạng thái</div>
                <div class="project-status-preview mb-3" style="background: <?= $selectedStatus['bg'] ?>; color: <?= $selectedStatus['color'] ?>;">
                    <div class="fw-semibold mb-1"><?= htmlspecialchars($selectedStatus['label'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small"><?= htmlspecialchars($selectedStatus['hint'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <div class="project-form-section-note mb-3">Ba trạng thái dưới đây giúp phân biệt rõ giai đoạn vận hành của dự án.</div>

                <?php foreach ($statusOptions as $option): ?>
                    <div class="project-status-option">
                        <span class="project-status-dot" style="background: <?= $option['color'] ?>;"></span>
                        <div>
                            <div class="fw-semibold text-slate-900"><?= htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-slate-500"><?= htmlspecialchars($option['hint'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="project-form-sidebar">
                <div class="project-form-section-title mb-3">Gợi ý khi nhập</div>

                <div class="project-help-box mb-3">
                    <div class="fw-semibold text-slate-900 mb-1">Tên dự án</div>
                    <div class="small text-slate-500">Nên cụ thể theo mục tiêu hoặc deliverable để dễ tìm kiếm và báo cáo sau này.</div>
                </div>

                <div class="project-help-box mb-3">
                    <div class="fw-semibold text-slate-900 mb-1">Mô tả</div>
                    <div class="small text-slate-500">Có thể ghi phạm vi, nhóm phụ trách, khách hàng nội bộ, hoặc KPI chính của dự án.</div>
                </div>

                <div class="project-help-box">
                    <div class="fw-semibold text-slate-900 mb-1">Mốc thời gian</div>
                    <div class="small text-slate-500">Nếu đã có deadline, hãy nhập đủ ngày bắt đầu và ngày kết thúc để theo dõi trễ hạn chính xác hơn.</div>
                </div>
            </div>
        </div>
    </div>
</div>

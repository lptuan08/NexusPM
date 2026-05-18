<?php

/**
 * @var array $projects Danh sách dự án
 * @var array $users Danh sách nhân viên
 * @var array $statuses Danh sách trạng thái
 * @var string $pageTitle
 * @var string $action_url
 */
$old = $old ?? [];
$errors = $errors ?? [];
$statuses = $statuses ?? [];
$statusesByProject = $statusesByProject ?? [];

$selectedProjectId = (string) ($old['project_id'] ?? '');
$selectedStatusId = (string) ($old['status_id'] ?? '');
?>

<!-- Thêm CSS của Quill Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    /* Giao diện 100% chiều ngang */
    .form-main-container {
        max-width: 100%;
        margin: 0;
        width: 100%;
    }

    /* Làm cho editor lấp đầy khoảng trống để cân bằng với thẻ bên phải */
    .editor-wrapper {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .editor-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-height: 400px; /* Chiều cao tối thiểu đảm bảo giao diện đẹp */
    }

    .ql-toolbar.ql-snow {
        border-top-left-radius: var(--radius-lg);
        border-top-right-radius: var(--radius-lg);
        background: var(--slate-50);
        border-color: var(--slate-200);
    }

    .ql-container.ql-snow {
        flex: 1;
        border-bottom-left-radius: var(--radius-lg);
        border-bottom-right-radius: var(--radius-lg);
        border-color: var(--slate-200);
        font-family: inherit;
        font-size: 0.95rem;
    }

    /* Điều chỉnh thẻ thuộc tính công việc để sticky đúng vị trí */
    .sticky-sidebar-card {
        position: sticky;
        top: 1.5rem;
        /* Đảm bảo thẻ không bị che bởi các phần tử khác khi sticky */
        z-index: 10; 
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/tasks" class="text-decoration-none text-slate-500 hover-text-primary">Công việc</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title"><?= htmlspecialchars($pageTitle) ?></span>
    </div>

    <div class="page-actions">
        <div class="d-flex gap-2">
            <a href="<?= URLROOT ?>/tasks" class="btn btn-outline-secondary">
                <i data-lucide="arrow-left" size="18"></i>
                <span>Trở về</span>
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('taskForm').reset();">
                <i data-lucide="refresh-ccw" size="18"></i>
                <span>Làm mới</span>
            </button>
            <button type="submit" form="taskForm" class="btn btn-primary shadow-sm">
                <i data-lucide="check-circle" size="18"></i>
                <span>Lưu lại</span>
            </button>
        </div>
    </div>
</div>

<div class="container-fluid p-0">
    <form action="<?= $action_url ?>" method="POST" id="taskForm" class="form-main-container m-0 pb-5">
        <?php \App\helpers\SecurityHelper::csrfInput(); ?>

        <div class="row g-4">
            <!-- Cột trái: Nội dung chính -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 h-100">
                    <div class="form-body-stack h-100">
                        <div class="form-group-stack mb-0">
                            <label class="form-label fw-bold">Tiêu đề công việc <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg" placeholder="Nhập tên công việc..." value="<?= $old['title'] ?? '' ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['title'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group-stack editor-wrapper">
                            <label class="form-label fw-bold">Mô tả chi tiết</label>
                            <div id="description-editor" class="editor-container">
                                <?= htmlspecialchars_decode($old['description'] ?? '') ?>
                            </div>
                            <input type="hidden" name="description" id="description-input">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phải: Thông tin bổ sung & Hành động -->
            <div class="col-lg-4">
                <!-- Bỏ h-100 để sticky-sidebar-card hoạt động chính xác -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 sticky-sidebar-card">
                        <div class="form-group-stack mb-4">
                            <label class="form-label">Dự án <span class="text-danger">*</span></label>
                            <select name="project_id" id="task_project_id" class="form-select select2" required>
                                <option value="">-- Chọn dự án --</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>" <?= (isset($old['project_id']) && (string) $old['project_id'] === (string) $project['id']) ? 'selected' : '' ?>><?= htmlspecialchars($project['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['project_id'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['project_id'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group-stack mb-4">
                            <label class="form-label">Người thực hiện</label>
                            <select name="assigned_to" class="form-select select2">
                                <option value="">-- Chưa giao --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= (isset($old['assigned_to']) && (string) $old['assigned_to'] === (string) $user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['employee_code'] ?? 'N/A') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-4">
                            <div class="col-6">
                                <div class="form-group-stack">
                                    <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select name="status_id" id="task_status_id" class="form-select" required>
                                        <?php if (empty($statuses)): ?>
                                            <option value="">-- Chọn dự án trước --</option>
                                        <?php else: ?>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['id'] ?>" <?= ($selectedStatusId !== '' && $selectedStatusId === (string) $status['id']) ? 'selected' : '' ?>><?= htmlspecialchars($status['name']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <?php if (isset($errors['status_id'])): ?>
                                        <div class="text-danger small mt-1"><?= $errors['status_id'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group-stack">
                                    <label class="form-label">Ưu tiên</label>
                                    <select name="priority" class="form-select">
                                        <option value="low" <?= (($old['priority'] ?? 'medium') === 'low') ? 'selected' : '' ?>>Thấp</option>
                                        <option value="medium" <?= (($old['priority'] ?? 'medium') === 'medium') ? 'selected' : '' ?>>Trung bình</option>
                                        <option value="high" <?= (($old['priority'] ?? 'medium') === 'high') ? 'selected' : '' ?>>Cao</option>
                                        <option value="urgent" <?= (($old['priority'] ?? 'medium') === 'urgent') ? 'selected' : '' ?>>Khẩn cấp</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group-stack mb-4">
                            <label class="form-label">Thời gian bắt đầu</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-lucide="calendar" size="16"></i></span>
                                <input type="date" name="start_date" class="form-control" value="<?= $old['start_date'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="form-group-stack mb-4">
                            <label class="form-label">Thời gian kết thúc</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-lucide="calendar" size="16"></i></span>
                                <input type="date" name="due_date" class="form-control" value="<?= $old['due_date'] ?? '' ?>">
                            </div>
                        </div>


                        <div class="form-group-stack">
                            <label class="form-label">Ước tính (giờ)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i data-lucide="clock" size="16"></i></span>
                                <input type="number" name="estimated_hours" class="form-control" placeholder="0" min="0" step="0.5" value="<?= $old['estimated_hours'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </form>
</div>

<!-- Scripts cho Editor -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusesByProject = <?= json_encode($statusesByProject, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        const initialProjectId = <?= json_encode($selectedProjectId) ?>;
        const initialStatusId = <?= json_encode($selectedStatusId) ?>;
        const projectSelect = document.getElementById('task_project_id');
        const statusSelect = document.getElementById('task_status_id');

        function renderStatuses(projectId, preferredStatusId = '') {
            if (!statusSelect) return;

            const statuses = statusesByProject[String(projectId)] || [];
            statusSelect.innerHTML = '';

            if (!projectId) {
                statusSelect.appendChild(new Option('-- Chọn dự án trước --', ''));
                statusSelect.disabled = true;
                return;
            }

            if (statuses.length === 0) {
                statusSelect.appendChild(new Option('-- Dự án chưa có trạng thái --', ''));
                statusSelect.disabled = true;
                return;
            }

            statuses.forEach(function(status, index) {
                const option = new Option(status.name || 'Chưa đặt tên', status.id);
                if ((preferredStatusId && String(status.id) === String(preferredStatusId)) || (!preferredStatusId && index === 0)) {
                    option.selected = true;
                }
                statusSelect.appendChild(option);
            });
            statusSelect.disabled = false;
        }

        if (projectSelect && statusSelect) {
            renderStatuses(projectSelect.value || initialProjectId, initialStatusId);
            projectSelect.addEventListener('change', function() {
                renderStatuses(this.value);
            });
        }

        // Cấu hình Toolbar cho Quill
        var toolbarOptions = [
            ['bold', 'italic', 'underline', 'strike'], // in đậm, nghiêng...
            ['blockquote', 'code-block'],
            [{
                'header': 1
            }, {
                'header': 2
            }], // tiêu đề
            [{
                'list': 'ordered'
            }, {
                'list': 'bullet'
            }],
            [{
                'color': []
            }, {
                'background': []
            }], // màu chữ
            ['link', 'image'], // chèn link và ảnh
            ['clean'] // xóa định dạng
        ];

        var quill = new Quill('#description-editor', {
            modules: {
                toolbar: toolbarOptions
            },
            theme: 'snow',
            placeholder: 'Viết mô tả công việc tại đây...'
        });

        // Xử lý đồng bộ dữ liệu Quill trước khi submit form
        const form = document.getElementById('taskForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Lấy nội dung HTML từ Quill và gán vào hidden input
                const descriptionInput = document.getElementById('description-input');
                
                if (descriptionInput && typeof quill !== 'undefined') {
                    descriptionInput.value = quill.root.innerHTML;

                    // Nếu editor trống (chỉ chứa các thẻ HTML mặc định) thì trả về rỗng để validator xử lý
                    if (quill.getText().trim().length === 0 && quill.root.innerHTML.indexOf('<img') === -1) {
                        descriptionInput.value = '';
                    }
                }
            });
        }
    });
</script>

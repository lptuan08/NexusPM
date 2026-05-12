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
?>

<!-- Thêm CSS của Quill Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<style>
    .task-form-card {
        background: #fff;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        border: none;
    }

    .editor-container {
        height: 300px;
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
    }

    .ql-toolbar {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        background: #f8fafc;
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/tasks" class="text-decoration-none text-slate-500 hover-text-primary">Công việc</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title"><?= $pageTitle ?></span>
    </div>
</div>

<div class="container-fluid p-0">
    <form action="<?= $action_url ?>" method="POST" id="taskForm">
        <?php \App\helpers\SecurityHelper::csrfInput(); ?>

        <div class="row g-4">
            <!-- Cột chính: Thông tin tiêu đề và mô tả -->
            <div class="col-lg-8">
                <div class="card task-form-card">
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Tiêu đề công việc <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg border-2 shadow-none"
                                placeholder="Nhập tên công việc..." value="<?= $old['title'] ?? '' ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="text-danger small mt-1"><?= $errors['title'] ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Mô tả chi tiết</label>
                            <!-- Container cho Quill -->
                            <div id="description-editor" class="editor-container">
                                <?= htmlspecialchars_decode($old['description'] ?? '') ?>
                            </div>
                            <!-- Hidden input để chứa dữ liệu HTML từ editor khi submit -->
                            <input type="hidden" name="description" id="description-input">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột phụ: Các thuộc tính công việc -->
            <div class="col-lg-4">
                <div class="card task-form-card">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Dự án <span class="text-danger">*</span></label>
                            <select name="project_id" class="form-select select2">
                                <option value="">-- Chọn dự án --</option>
                                <?php foreach ($projects as $project): ?>
                                    <option value="<?= $project['id'] ?>" <?= (isset($old['project_id']) && (string) $old['project_id'] === (string) $project['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($project['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['project_id'])): ?>
                                <div class="text-danger small mt-1"><?= htmlspecialchars($errors['project_id']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Người thực hiện</label>
                            <select name="assigned_to" class="form-select select2">
                                <option value="">-- Chưa giao --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= (isset($old['assigned_to']) && (string) $old['assigned_to'] === (string) $user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Trạng thái</label>
                                <select name="status_id" class="form-select">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= $status['id'] ?>" <?= (isset($old['status_id']) && (string) $old['status_id'] === (string) $status['id']) ? 'selected' : '' ?>><?= htmlspecialchars($status['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['status_id'])): ?>
                                    <div class="text-danger small mt-1"><?= htmlspecialchars($errors['status_id']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Mức độ ưu tiên</label>
                                <select name="priority" class="form-select">
                                    <option value="low" <?= (($old['priority'] ?? 'medium') === 'low') ? 'selected' : '' ?>>Thấp</option>
                                    <option value="medium" <?= (($old['priority'] ?? 'medium') === 'medium') ? 'selected' : '' ?>>Trung bình</option>
                                    <option value="high" <?= (($old['priority'] ?? 'medium') === 'high') ? 'selected' : '' ?>>Cao</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Hạn chót (Due Date)</label>
                            <input type="date" name="due_date" class="form-control" value="<?= $old['due_date'] ?? '' ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Thời gian ước tính (Giờ)</label>
                            <input type="number" name="estimated_hours" class="form-control" placeholder="0" min="0" step="0.5">
                        </div>

                        <hr>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-bold">
                                <i data-lucide="save" class="me-1" size="18"></i> Lưu công việc
                            </button>
                            <a href="<?= URLROOT ?>/tasks" class="btn btn-light py-2 fw-semibold">Hủy bỏ</a>
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

        // Xử lý trước khi submit form
        var form = document.getElementById('taskForm');
        form.onsubmit = function() {
            // Lấy nội dung HTML từ Quill và gán vào hidden input
            var descriptionInput = document.querySelector('input[name=description]');
            descriptionInput.value = quill.root.innerHTML;

            // Nếu editor trống (chỉ chứa <p><br></p>) thì set rỗng
            if (quill.getText().trim().length === 0 && quill.root.innerHTML.indexOf('<img') === -1) {
                descriptionInput.value = '';
            }
            return true;
        };
    });
</script>
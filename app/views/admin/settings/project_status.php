<?php
/**
 * Giao diện quản lý trạng thái dự án (Thiết lập hệ thống)
 */
?>

<style>
    /* Container danh sách trạng thái */
    .status-list-container {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        box-shadow: 0 10px 25px -15px rgba(15, 23, 42, 0.1);
        overflow: hidden;
    }

    /* Item trạng thái trong danh sách */
    .status-item {
        padding: 1.25rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        background: #fff;
        transition: background-color 0.2s;
    }

    .status-item:last-child {
        border-bottom: none;
    }

    /* Hiệu ứng khi kéo thả */
    .status-item.sortable-ghost {
        opacity: 0.4;
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
    }

    .drag-handle {
        cursor: grab;
        color: #94a3b8;
        padding: 0.5rem;
        margin-right: 0.75rem;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .drag-handle:hover {
        background: #f1f5f9;
        color: #64748b;
    }

    .drag-handle:active {
        cursor: grabbing;
    }

    /* Xem trước màu sắc */
    .status-color-circle {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 1rem;
        box-shadow: 0 0 0 4px rgba(0,0,0,0.03);
    }

    .btn-action-icon {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.75rem;
        color: #64748b;
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .btn-action-icon:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
        color: #0f172a;
    }

    .btn-action-icon.text-danger:hover {
        background: #fff1f2;
        border-color: #fecdd3;
        color: #e11d48;
    }
</style>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/admin/settings" class="text-decoration-none text-slate-500 hover-text-primary">Thiết lập</a>
        <span class="mx-2 text-slate-400 d-flex align-items-center"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="fw-medium text-slate-800 fs-5">Trạng thái dự án</span>
    </div>

    <button type="button" class="btn btn-primary px-4 shadow-sm rounded-3 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#statusModal" onclick="resetStatusForm()">
        <i data-lucide="plus" size="18"></i>
        <span>Thêm trạng thái</span>
    </button>
</div>

<div class="row">
    <div class="col-xl-8 col-lg-10 mx-auto">
        <div class="status-list-container">
            <div class="bg-light bg-opacity-50 py-3 px-4 border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold text-slate-900 mb-0">Thứ tự trạng thái</h6>
                    <span class="badge bg-white text-slate-500 border fw-medium px-2 py-1" style="font-size: 0.7rem;">Kéo thả để sắp xếp</span>
                </div>
            </div>
            <div id="sortableStatusList">
                <?php if (!empty($statuses)): ?>
                    <?php foreach ($statuses as $status): ?>
                        <div class="status-item" data-id="<?= $status['id'] ?>">
                            <div class="drag-handle">
                                <i data-lucide="grip-vertical" size="20"></i>
                            </div>
                            <div class="flex-grow-1 d-flex align-items-center">
                                <span class="status-color-circle" style="background-color: <?= htmlspecialchars($status['color'] ?? '#94a3b8') ?>"></span>
                                <div>
                                    <div class="fw-bold text-slate-900"><?= htmlspecialchars($status['name']) ?></div>
                                    <div class="text-slate-400 small" style="font-size: 0.75rem;">Slug: <?= htmlspecialchars($status['slug']) ?></div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn-action-icon" onclick='editStatus(<?= json_encode($status) ?>)' title="Chỉnh sửa">
                                    <i data-lucide="edit-3" size="18"></i>
                                </button>
                                <button type="button" class="btn-action-icon text-danger" onclick="deleteStatus(<?= $status['id'] ?>, '<?= htmlspecialchars($status['name']) ?>')" title="Xóa">
                                    <i data-lucide="trash-2" size="18"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="text-slate-300 mb-3"><i data-lucide="layers" size="48"></i></div>
                        <p class="text-slate-500 mb-0">Chưa có trạng thái nào. Hãy tạo mới ngay!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Trạng thái -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="fw-bold text-slate-900 mb-0" id="statusModalLabel">Cấu hình trạng thái</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusForm" action="<?= URLROOT ?>/admin/settings/project-status/save" method="POST">
                <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                <input type="hidden" name="id" id="field_id">
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold small">Tên hiển thị <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="field_name" class="form-control rounded-3" placeholder="Ví dụ: Đã hoàn thành" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold small">Mã định danh (Slug) <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="field_slug" class="form-control rounded-3" placeholder="Ví dụ: completed" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-slate-600 fw-semibold small">Màu sắc nhận diện</label>
                        <div class="d-flex align-items-center gap-3 p-2 border rounded-3 bg-slate-50">
                            <input type="color" name="color" id="field_color" class="form-control form-control-color border-0 bg-transparent" style="width: 44px; height: 44px; padding: 2px;" value="#6366f1">
                            <div class="flex-grow-1">
                                <input type="text" id="color_hex_display" class="form-control form-control-sm border-0 bg-transparent fw-mono" placeholder="#6366F1" style="font-size: 0.9rem;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4">
                    <button type="button" class="btn btn-light px-4 rounded-3 text-slate-600 fw-semibold" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 shadow-sm fw-semibold">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo kéo thả
        const sortableList = document.getElementById('sortableStatusList');
        if (sortableList) {
            new Sortable(sortableList, {
                animation: 200,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    const items = sortableList.querySelectorAll('.status-item');
                    const orderData = Array.from(items).map((item, index) => ({
                        id: item.dataset.id,
                        position: index
                    }));
                    console.log('New Order:', orderData);
                    // Gọi AJAX gửi orderData lên server tại đây để cập nhật cột 'position'
                }
            });
        }

        // Đồng bộ mã màu giữa Picker và Input Text
        const colorPicker = document.getElementById('field_color');
        const colorText = document.getElementById('color_hex_display');
        
        colorPicker.addEventListener('input', (e) => colorText.value = e.target.value.toUpperCase());
        colorText.addEventListener('input', (e) => {
            if (/^#[0-9A-F]{6}$/i.test(e.target.value)) colorPicker.value = e.target.value;
        });
        colorText.value = colorPicker.value.toUpperCase();
    });

    function resetStatusForm() {
        document.getElementById('statusForm').reset();
        document.getElementById('field_id').value = '';
        document.getElementById('statusModalLabel').innerText = 'Thêm trạng thái mới';
        document.getElementById('color_hex_display').value = '#6366F1';
    }

    function editStatus(status) {
        resetStatusForm();
        document.getElementById('statusModalLabel').innerText = 'Chỉnh sửa trạng thái';
        document.getElementById('field_id').value = status.id;
        document.getElementById('field_name').value = status.name;
        document.getElementById('field_slug').value = status.slug;
        document.getElementById('field_color').value = status.color || '#6366f1';
        document.getElementById('color_hex_display').value = (status.color || '#6366F1').toUpperCase();
        
        const modal = new bootstrap.Modal(document.getElementById('statusModal'));
        modal.show();
    }

    function deleteStatus(id, name) {
        if (confirm(`Bạn có chắc chắn muốn xóa trạng thái "${name}" không?`)) {
            window.location.href = `<?= URLROOT ?>/admin/settings/project-status/delete/${id}`;
        }
    }
</script>
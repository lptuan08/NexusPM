<?php

/**
 * Giao diện quản lý trạng thái dự án (Thiết lập hệ thống)
 */
?>

<style>
    .color-box,
    .status-color-circle {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-flex;
        flex-shrink: 0;
        margin-right: 0.5rem;
        border: 1px solid rgba(32, 33, 36, 0.1);
    }

    .sortable-ghost {
        opacity: 0.5;
        background: var(--primary-50) !important;
    }

    .status-position-col {
        width: 80px;
    }

    .status-actions-col {
        width: 150px;
    }

    .status-modal-dialog {
        max-width: 450px;
    }

    .sort-modal-dialog {
        max-width: 480px;
    }

    .status-color-input {
        width: 44px;
        height: 44px;
        padding: 2px;
    }

    .status-color-hex {
        font-size: 0.9rem;
    }
</style>



<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/admin/settings" class="text-decoration-none text-slate-500 hover-text-primary">Thiết lập</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Trạng thái dự án</span>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#sortModal">
            <i data-lucide="arrow-up-down"></i>
            <span>Sắp xếp</span>
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal" onclick="resetStatusForm()">
            <i data-lucide="plus"></i>
            <span>Thêm trạng thái</span>
        </button>
    </div>
</div>

<!-- Cảnh báo quyền Admin -->
<div class="alert alert-danger d-flex align-items-center border-0 shadow-sm" role="alert">
    <i data-lucide="alert-triangle" class="me-3"></i>
    <div>
        <strong>Cảnh báo quản trị (Admin):</strong> Đây là chức năng thiết lập hệ thống cốt lõi. Vui lòng cẩn trọng khi thêm, sửa hoặc thay đổi vị trí các trạng thái vì điều này sẽ ảnh hưởng trực tiếp đến toàn bộ dự án đang vận hành.
    </div>
</div>

<!-- Bảng danh sách trạng thái -->
<div class="table-container">
        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="text-center status-position-col">Vị trí</th>
                        <th scope="col">Tên trạng thái</th>
                        <th scope="col">Slug</th>
                        <th scope="col">Mã màu</th>
                        <th scope="col" class="text-center">Hệ thống</th>
                        <th scope="col" class="text-center status-actions-col">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($statuses)): ?>
                        <?php foreach ($statuses as $index => $status): ?>
                            <tr class="<?= ($status['is_locked'] ?? false) ? 'table-light text-muted' : '' ?>">
                                <td class="text-center text-stt"><?= $status['position'] ?? ($index + 1) ?></td>
                                <td class="text-name">
                                    <?= htmlspecialchars($status['name']) ?>
                                    <?php if ($status['is_locked'] ?? false): ?>
                                        <span class="ui-badge priority-high ms-1">Mặc định</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="ui-badge status-muted"><?= htmlspecialchars($status['slug']) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="color-box" style="background-color: <?= htmlspecialchars($status['color'] ?? '#94a3b8') ?>; <?= ($status['is_locked'] ?? false) ? 'opacity: 0.5;' : '' ?>"></span>
                                        <code><?= htmlspecialchars($status['color'] ?? '#94a3b8') ?></code>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input" type="checkbox" role="switch" <?= ($status['is_locked'] ?? false) ? 'checked disabled' : '' ?>>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button class="btn btn-white btn-action" onclick='editStatus(<?= htmlspecialchars(json_encode($status, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)' <?= ($status['is_locked'] ?? false) ? 'disabled' : '' ?> title="Chỉnh sửa">
                                            <i data-lucide="edit-3" size="14"></i>
                                        </button>
                                        <button class="btn btn-white btn-action text-danger" onclick="deleteStatus(<?= (int) $status['id'] ?>, <?= htmlspecialchars(json_encode($status['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" <?= ($status['is_locked'] ?? false) ? 'disabled' : '' ?> title="Xóa">
                                            <i data-lucide="trash-2" size="14"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="table-empty">Chưa có dữ liệu trạng thái.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- Modal Form Trạng thái -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered status-modal-dialog">
        <div class="modal-content border-0 shadow-lg">
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
                        <input type="text" name="name" id="field_name" class="form-control" placeholder="Ví dụ: Đã hoàn thành" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold small">Mã định danh (Slug) <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="field_slug" class="form-control" placeholder="Ví dụ: completed" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-slate-600 fw-semibold small">Màu sắc nhận diện</label>
                        <div class="d-flex align-items-center gap-3 p-2 border rounded-3 bg-slate-50">
                            <input type="color" name="color" id="field_color" class="form-control form-control-color border-0 bg-transparent status-color-input" value="#6366f1">
                            <div class="flex-grow-1">
                                <input type="text" id="color_hex_display" class="form-control form-control-sm border-0 bg-transparent fw-mono status-color-hex" placeholder="#6366F1">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary shadow-sm">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Sắp xếp vị trí -->
<div class="modal fade" id="sortModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered sort-modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="fw-bold text-slate-900 mb-0">Thay đổi thứ tự hiển thị</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-2">
                <p class="text-slate-500 small mb-3">Kéo thả các trạng thái để sắp xếp thứ tự ưu tiên hiển thị trên hệ thống.</p>
                <div id="sortableContainer" class="d-flex flex-column gap-2">
                    <?php foreach ($statuses as $status): ?>
                        <div class="d-flex align-items-center p-3 bg-white border rounded-3 sortable-item shadow-sm" data-id="<?= $status['id'] ?>">
                            <div class="handle cursor-move me-3 text-slate-300">
                                <i data-lucide="grip-vertical" size="18"></i>
                            </div>
                            <span class="status-color-circle mb-0" style="background-color: <?= htmlspecialchars($status['color'] ?? '#94a3b8') ?>"></span>
                            <span class="fw-medium text-slate-700 flex-grow-1"><?= htmlspecialchars($status['name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer border-top-0 pb-4 px-4 mt-2">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-primary shadow-sm" id="saveOrderBtn">
                    Lưu thứ tự mới
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Đồng bộ mã màu giữa Picker và Input Text
        const colorPicker = document.getElementById('field_color');
        const colorText = document.getElementById('color_hex_display');

        colorPicker.addEventListener('input', (e) => colorText.value = e.target.value.toUpperCase());
        colorText.addEventListener('input', (e) => {
            if (/^#[0-9A-F]{6}$/i.test(e.target.value)) colorPicker.value = e.target.value;
        });
        colorText.value = colorPicker.value.toUpperCase();

        // Khởi tạo SortableJS cho modal sắp xếp
        const sortableContainer = document.getElementById('sortableContainer');
        if (sortableContainer) {
            new Sortable(sortableContainer, {
                animation: 150,
                handle: '.handle',
                ghostClass: 'sortable-ghost'
            });
        }

        // Xử lý lưu thứ tự qua nút "Lưu thứ tự mới"
        document.getElementById('saveOrderBtn')?.addEventListener('click', function() {
            const items = sortableContainer.querySelectorAll('.sortable-item');
            const order = Array.from(items).map((item, index) => ({
                id: item.dataset.id,
                position: index + 1
            }));
            console.log('Order to save:', order);
            // Tại đây bạn có thể gọi AJAX để gửi mảng order lên backend
        });
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

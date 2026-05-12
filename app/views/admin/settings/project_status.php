<?php

/**
 * Giao diện quản lý trạng thái dự án (Thiết lập hệ thống)
 * 
 * @var array $statuses
 * @var array $old
 * @var array $errors
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
        <a href="<?= URLROOT; ?>/settings" class="text-decoration-none text-slate-500 hover-text-primary">Hệ thống</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Trạng thái dự án</span>
    </div>
    <div class="page-actions">
        <!-- Mở modal sắp xếp bằng data-attributes (Bootstrap tự xử lý) -->
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#sortModal">
            <i data-lucide="arrow-up-down"></i>
            <span>Sắp xếp</span>
        </button>
        <!-- Mở modal thêm mới bằng data-attributes và gọi resetStatusForm để làm trống form -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal" onclick="resetStatusForm()">
            <i data-lucide="plus"></i>
            <span>Thêm mới</span>
        </button>
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
                    <th scope="col" class="text-center">Kích hoạt</th>
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
                                    <span class="color-box" style="background-color: <?= htmlspecialchars($status['color'] ?? '#94a3b8') ?>; <?= ($status['is_active'] ?? false) ? 'opacity: 0.5;' : '' ?>"></span>
                                    <code><?= htmlspecialchars($status['color'] ?? '#94a3b8') ?></code>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" <?= ($status['is_active'] ?? false) ? 'checked' : '' ?> disabled>
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
            <form id="statusForm" action="<?= URLROOT ?>/settings/project/create" method="POST">
                <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                <input type="hidden" name="id" id="field_id" value="<?= htmlspecialchars($old['id'] ?? '') ?>">
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold small">Tên hiển thị <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="field_name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" placeholder="Ví dụ: Đã hoàn thành" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= $errors['name'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold small">Mã định danh (Slug) <span class="text-danger">*</span></label>
                        <input type="text" name="slug" id="field_slug" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" placeholder="Ví dụ: completed" value="<?= htmlspecialchars($old['slug'] ?? '') ?>" required>
                        <?php if (isset($errors['slug'])): ?>
                            <div class="invalid-feedback"><?= $errors['slug'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-slate-600 fw-semibold small">Màu sắc nhận diện</label>
                        <div class="d-flex align-items-center gap-3 p-2 border rounded-3 bg-slate-50 <?= isset($errors['color']) ? 'border-danger' : '' ?>">
                            <input type="color" name="color" id="field_color" class="form-control form-control-color border-0 bg-transparent status-color-input" value="<?= htmlspecialchars($old['color'] ?? '#6366f1') ?>">
                            <div class="flex-grow-1">
                                <input type="text" id="color_hex_display" class="form-control form-control-sm border-0 bg-transparent fw-mono status-color-hex" placeholder="#6366F1" value="<?= htmlspecialchars($old['color'] ?? '#6366F1') ?>">
                            </div>
                        </div>
                        <?php if (isset($errors['color'])): ?>
                            <div class="form-error-message text-danger small mt-1"><?= $errors['color'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3 mt-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="field_is_active" value="1" <?= (isset($old['is_active']) && $old['is_active'] == 0) ? '' : 'checked' ?>>
                            <label class="form-check-label text-slate-600 fw-semibold small" for="field_is_active">Kích hoạt trạng thái</label>
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
            <!-- 
                FLOW XỬ LÝ SẮP XẾP (PHP FORM):
                1. Các item được bọc trong một form POST truyền thống.
                2. Mỗi item chứa một <input type="hidden" name="status_ids[]">.
                3. Khi dùng SortableJS kéo thả, vị trí của các thẻ div (chứa input) trong DOM sẽ thay đổi.
                4. Khi submit form, PHP sẽ nhận được mảng status_ids[] theo đúng thứ tự từ trên xuống dưới của DOM.
            -->
            <form action="<?= URLROOT ?>/settings/project/reorder" method="POST">
                <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                <div class="modal-body px-4 py-2">
                    <p class="text-slate-500 small mb-3">Kéo thả các trạng thái để sắp xếp thứ tự ưu tiên hiển thị trên hệ thống.</p>
                    <div id="sortableContainer" class="d-flex flex-column gap-2">
                        <?php foreach ($statuses as $status): ?>
                            <div class="d-flex align-items-center p-3 bg-white border rounded-3 sortable-item shadow-sm" data-id="<?= $status['id'] ?>">
                                <!-- Input ẩn này sẽ gửi ID về server. Thứ tự trong mảng $_POST['status_ids'] phụ thuộc vào vị trí thẻ div này -->
                                <input type="hidden" name="status_ids[]" value="<?= $status['id'] ?>">

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
                    <button type="submit" class="btn btn-primary shadow-sm">Lưu thứ tự mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg">
            <form id="deleteStatusForm" action="" method="POST">
                <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i data-lucide="alert-circle" size="48"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Xác nhận xóa</h5>
                    <p class="text-slate-500 mb-4">Bạn có chắc chắn muốn xóa trạng thái <br><strong id="delete_item_name" class="text-slate-700"></strong>? Hành động này không thể hoàn tác.</p>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-white shadow-sm" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-danger shadow-sm px-4">Xác nhận xóa</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        /**
         * 1. Xử lý đồng bộ mã màu giữa Color Picker (input type="color") và Input Text
         */
        const colorPicker = document.getElementById('field_color');
        const colorText = document.getElementById('color_hex_display');

        // Khi thay đổi màu trên bảng chọn, cập nhật mã Hex vào ô text
        colorPicker.addEventListener('input', (e) => colorText.value = e.target.value.toUpperCase());

        // Khi người dùng tự nhập mã Hex, kiểm tra định dạng và cập nhật ngược lại bảng chọn màu
        colorText.addEventListener('input', (e) => {
            if (/^#[0-9A-F]{6}$/i.test(e.target.value)) colorPicker.value = e.target.value;
        });
        // Khởi tạo giá trị mặc định cho ô text
        colorText.value = colorPicker.value.toUpperCase();

        /**
         * 2. Khởi tạo tính năng kéo thả (Drag and Drop) cho Modal sắp xếp vị trí
         */
        const sortableContainer = document.getElementById('sortableContainer');
        if (sortableContainer) {
            new Sortable(sortableContainer, {
                animation: 150, // Tốc độ hiệu ứng di chuyển (ms)
                handle: '.handle', // Chỉ cho phép kéo tại icon grip
                ghostClass: 'sortable-ghost', // Class CSS áp dụng cho phần tử đang được kéo
                forceFallback: true // Đảm bảo hoạt động ổn định trên các trình duyệt di động
            });
        }

        /**
         * 4. Tự động mở Modal nếu có lỗi validate từ server trả về
         */
        <?php if (!empty($errors)): ?>
            const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            statusModal.show();
        <?php endif; ?>

        const modalEl = document.getElementById('statusModal');
        modalEl.addEventListener('hidden.bs.modal', function() {
            resetStatusForm();
        });
    });

    function setStatusColor(value) {
        const color = value || '#6366f1';
        document.getElementById('field_color').value = color;
        document.getElementById('color_hex_display').value = color.toUpperCase();
    }

    function resetStatusForm() {
        const form = document.getElementById('statusForm');
        window.NexusPM?.clearFormValidation(form);
        form.action = '<?= URLROOT ?>/settings/project/create';

        document.getElementById('field_id').value = '';
        document.getElementById('field_name').value = '';
        document.getElementById('field_slug').value = '';
        document.getElementById('statusModalLabel').innerText = 'Thêm trạng thái mới';
        setStatusColor('#6366f1');
        document.getElementById('field_is_active').checked = true;
    }

    function editStatus(status) {
        resetStatusForm();

        const form = document.getElementById('statusForm');
        form.action = `<?= URLROOT ?>/settings/project/${status.id}/edit`;

        document.getElementById('statusModalLabel').innerText = 'Chỉnh sửa trạng thái';
        document.getElementById('field_id').value = status.id;
        document.getElementById('field_name').value = status.name;
        document.getElementById('field_slug').value = status.slug;
        setStatusColor(status.color);
        document.getElementById('field_is_active').checked = status.is_active == 1;

        const modal = new bootstrap.Modal(document.getElementById('statusModal'));
        modal.show();
    }

    /**
     * Hiển thị xác nhận và thực hiện xóa trạng thái
     * @param {number} id ID của trạng thái
     * @param {string} name Tên trạng thái để hiển thị trong thông báo
     */
    function deleteStatus(id, name) {
        document.getElementById('delete_item_name').innerText = name;
        document.getElementById('deleteStatusForm').action = `<?= URLROOT ?>/settings/project/${id}/delete`;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>
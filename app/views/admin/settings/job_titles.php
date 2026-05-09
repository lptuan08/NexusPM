<?php

/**
 * Giao diện quản lý chức danh nhân viên (Thiết lập hệ thống)
 * 
 * @var array $titles
 * @var array $old
 * @var array $errors
 */
?>

<style>
    .sortable-ghost {
        opacity: 0.5;
        background: var(--primary-50) !important;
    }

    .title-actions-col {
        width: 150px;
    }

    .title-stt-col {
        width: 60px;
    }

    .title-modal-dialog {
        max-width: 450px;
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/admin/settings" class="text-decoration-none text-slate-500 hover-text-primary">Thiết lập</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Chức danh nhân viên</span>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#jobTitleModal" onclick="resetJobTitleForm()">
            <i data-lucide="plus" size="18"></i>
            <span>Thêm chức danh</span>
        </button>
    </div>
</div>

<div class="table-container mt-4">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="text-center title-stt-col">STT</th>
                    <th scope="col">Tên chức danh</th>
                    <th scope="col">Ngày tạo</th>
                    <th scope="col">Ngày cập nhật</th>
                    <th scope="col" class="text-center title-actions-col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($titles)): ?>
                    <?php foreach ($titles as $index => $title): ?>
                        <tr data-id="<?= $title['id'] ?>">
                            <td class="text-center text-slate-500">
                                <?= $index + 1 ?>
                            </td>
                            <td class="text-name fw-semibold text-slate-700">
                                <?= htmlspecialchars($title['name']) ?>
                            </td>
                            <td class="text-slate-500">
                                <?= isset($title['created_at']) ? date('H:i d/m/Y', strtotime($title['created_at'])) : '---' ?>
                            </td>
                            <td class="text-slate-500">
                                <?= isset($title['updated_at']) ? date('H:i d/m/Y', strtotime($title['updated_at'])) : '---' ?>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <button class="btn btn-white btn-action" onclick='editJobTitle(<?= htmlspecialchars(json_encode($title, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)' title="Chỉnh sửa">
                                        <i data-lucide="edit-3" size="16"></i>
                                    </button>
                                    <button class="btn btn-white btn-action text-danger" onclick="deleteJobTitle(<?= (int) $title['id'] ?>, <?= htmlspecialchars(json_encode($title['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)" title="Xóa">
                                        <i data-lucide="trash-2" size="16"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="table-empty">Chưa có dữ liệu chức danh.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Chức danh -->
<div class="modal fade" id="jobTitleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered title-modal-dialog">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="fw-bold text-slate-900 mb-0" id="jobTitleModalLabel">Cấu hình chức danh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="jobTitleForm" action="<?= URLROOT ?>/settings/job/create" method="POST">
                <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                <input type="hidden" name="id" id="field_id" value="<?= htmlspecialchars($old['id'] ?? '') ?>">
                <div class="modal-body px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label text-slate-600 fw-semibold small">Tên chức danh <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="field_name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" placeholder="Ví dụ: Senior Developer" value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                        <?php if (isset($errors['name'])): ?>
                            <div class="invalid-feedback"><?= $errors['name'] ?></div>
                        <?php endif; ?>
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

<!-- Modal Xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg">
            <form id="deleteJobTitleForm" action="" method="POST">
                <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i data-lucide="alert-circle" size="48"></i>
                    </div>
                    <h5 class="fw-bold text-slate-900 mb-2">Xác nhận xóa</h5>
                    <p class="text-slate-500 mb-4">Bạn có chắc chắn muốn xóa chức danh <br><strong id="delete_item_name" class="text-slate-700"></strong>? Hành động này không thể hoàn tác.</p>

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-white shadow-sm" data-bs-dismiss="modal">Hủy bỏ</button>
                        <button type="submit" class="btn btn-danger shadow-sm px-4">Xác nhận xóa</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        /**
         * Tự động mở Modal nếu có lỗi validate từ server
         */
        <?php if (!empty($errors)): ?>
            const modalEl = document.getElementById('jobTitleModal');
            const jobTitleModal = new bootstrap.Modal(modalEl);

            // Nếu có ID trong dữ liệu cũ, tức là đang thực hiện Edit nhưng bị lỗi
            <?php if (!empty($old['id'])): ?>
                document.getElementById('jobTitleModalLabel').innerText = 'Chỉnh sửa chức danh';
                document.getElementById('jobTitleForm').action = `<?= URLROOT ?>/settings/job/<?= $old['id'] ?>/edit`;
            <?php else: ?>
                document.getElementById('jobTitleModalLabel').innerText = 'Thêm chức danh mới';
                document.getElementById('jobTitleForm').action = `<?= URLROOT ?>/settings/job/create`;
            <?php endif; ?>

            jobTitleModal.show();
        <?php endif; ?>
    });

    /**
     * Làm mới form trong modal
     */
    function resetJobTitleForm() {
        const form = document.getElementById('jobTitleForm');
        //Reset form theo cách tiêu chuẩn
        form.reset();

        // Xóa bỏ các class báo lỗi và ẩn các thông báo lỗi cũ
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = '');


        // Khôi phục trạng thái mặc định cho Form
        form.action = '<?= URLROOT ?>/settings/job/create';
        document.getElementById('field_id').value = '';
        document.getElementById('field_name').value = '';
        document.getElementById('jobTitleModalLabel').innerText = 'Thêm chức danh mới';
    }


    /**
     * Đổ dữ liệu vào modal để chỉnh sửa
     */
    function editJobTitle(title) {
        resetJobTitleForm();
        const form = document.getElementById('jobTitleForm');
        form.action = `<?= URLROOT ?>/settings/job/${title.id}/edit`;

        document.getElementById('jobTitleModalLabel').innerText = 'Chỉnh sửa chức danh';
        document.getElementById('field_id').value = title.id;
        document.getElementById('field_name').value = title.name;

        const modal = new bootstrap.Modal(document.getElementById('jobTitleModal'));
        modal.show();
    }

    /**
     * Hiển thị xác nhận và thực hiện xóa
     */
    function deleteJobTitle(id, name) {
        document.getElementById('delete_item_name').innerText = name;
        document.getElementById('deleteJobTitleForm').action = `<?= URLROOT ?>/settings/job/${id}/delete`;

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>
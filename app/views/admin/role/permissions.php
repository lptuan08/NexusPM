<?php
/**
 * @var array $role
 * @var array $permissionsByGroup (Mảng quyền đã được nhóm theo module)
 * @var array $activePermissions (Danh sách ID quyền mà Role này đang có)
 */
?>
<div class="page-toolbar">
    <div class="d-flex align-items-center">
        <a href="<?= URLROOT ?>/admin/roles" class="btn btn-icon-google me-2">
            <i data-lucide="arrow-left"></i>
        </a>
        <div>
            <h1 class="page-title">Phân quyền: <?= htmlspecialchars($role['name']) ?></h1>
            <p class="page-subtitle text-primary fw-medium">ID: #<?= $role['id'] ?></p>
        </div>
    </div>

    <div class="page-actions">
        <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload();">
            <i data-lucide="refresh-cw"></i> Làm lại
        </button>
        <a href="<?= URLROOT ?>/admin/roles" class="btn btn-outline-secondary">
            Hủy bỏ
        </a>
        <button type="submit" form="permissionsForm" class="btn btn-primary px-4">
            <i data-lucide="save"></i> Lưu phân quyền
        </button>
    </div>
</div>

<form id="permissionsForm" action="<?= URLROOT ?>/admin/roles/<?= $role['id'] ?>/permissions" method="POST">
    <div class="row g-4">
        <?php foreach ($permissionsByGroup as $groupName => $permissions): ?>
            <div class="col-12 col-lg-6 col-xl-4">
                <div class="ui-card h-100 overflow-hidden">
                    <div class="ui-card-header bg-slate-50 d-flex justify-content-between align-items-center py-3">
                        <h6 class="mb-0 fw-bold text-slate-700 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                            Module: <?= htmlspecialchars($groupName) ?>
                        </h6>
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input select-all-group" type="checkbox" role="switch">
                        </div>
                    </div>
                    <div class="ui-card-body p-3">
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($permissions as $permission): ?>
                                <div class="form-check d-flex align-items-center gap-2 min-vh-0">
                                    <input class="form-check-input permission-checkbox" 
                                           type="checkbox" 
                                           name="permission_ids[]" 
                                           value="<?= $permission['id'] ?>" 
                                           id="perm_<?= $permission['id'] ?>"
                                           <?= in_array($permission['id'], $activePermissions) ? 'checked' : '' ?>>
                                    <label class="form-check-label flex-grow-1 cursor-pointer" for="perm_<?= $permission['id'] ?>">
                                        <span class="d-block fw-medium text-slate-800" style="font-size: 0.875rem;">
                                            <?= htmlspecialchars($permission['name']) ?>
                                        </span>
                                        <small class="text-slate-500 d-block" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($permission['slug']) ?>
                                        </small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 text-slate-500 small">
        <i data-lucide="info" class="size-4 me-1"></i>
        Lưu ý: Các thay đổi về quyền hạn sẽ có hiệu lực sau khi người dùng đăng nhập lại vào hệ thống.
    </div>
</form>

<script>
    document.querySelectorAll('.select-all-group').forEach(switcher => {
        switcher.addEventListener('change', function() {
            const card = this.closest('.ui-card');
            const checkboxes = card.querySelectorAll('.permission-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Khởi tạo trạng thái ban đầu cho nút "Select All"
        const card = switcher.closest('.ui-card');
        const checkboxes = card.querySelectorAll('.permission-checkbox');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        if (checkedCount === checkboxes.length && checkboxes.length > 0) {
            switcher.checked = true;
        }
    });

    // Hiệu ứng hover cho form-check
    document.querySelectorAll('.form-check').forEach(el => {
        el.addEventListener('mouseenter', () => el.style.backgroundColor = 'var(--slate-50)');
        el.addEventListener('mouseleave', () => el.style.backgroundColor = 'transparent');
    });
</script>
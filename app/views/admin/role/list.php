<?php

/**
 * @var array $roles
 * @var string $pageTitle
 * @var array $old
 * @var array $errors
 */
$firstRole = $roles[0] ?? null;
$canCreateRole = \App\helpers\AuthHelper::can('roles.create.all');
$canUpdateRole = \App\helpers\AuthHelper::can('roles.update.all');
$canDeleteRole = \App\helpers\AuthHelper::can('roles.delete.all');
$canUpdateRolePermissions = \App\helpers\AuthHelper::can('roles.update_permissions.all');
$canManageRole = $canUpdateRole || $canDeleteRole || $canUpdateRolePermissions;
?>
<style>
    .role-badge-count {
        background-color: var(--primary-50);
        color: var(--primary-700);
        font-weight: 600;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-size: 0.75rem;
    }

    .col-permissions {
        width: 150px;
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/settings" class="text-decoration-none text-slate-500 hover-text-primary">Hệ thống</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Vai trò & Phân quyền</span>
    </div>
    <div class="page-actions">
        <?php if ($canUpdateRolePermissions): ?>
        <?php if ($firstRole): ?>
            <a href="<?= URLROOT ?>/admin/roles/<?= (int)$firstRole['id'] ?>/permissions" class="btn btn-outline-secondary">
                <i data-lucide="shield-check" size="18"></i>
                <span>Phân quyền</span>
            </a>
        <?php else: ?>
            <button type="button" class="btn btn-outline-secondary" disabled title="Cần tạo vai trò trước">
                <i data-lucide="shield-check" size="18"></i>
                <span>Phân quyền</span>
            </button>
        <?php endif; ?>
        <?php endif; ?>
        <?php if ($canCreateRole): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#roleModal" onclick="resetRoleForm()">
            <i data-lucide="plus" size="18"></i>
            <span>Thêm mới</span>
        </button>
        <?php endif; ?>
    </div>
</div>
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="text-center col-stt">STT</th>
                    <th scope="col">Tên vai trò</th>
                    <th scope="col">Mô tả</th>
                    <th scope="col" class="text-center">Phân loại</th>
                    <th scope="col" class="text-center">Kích hoạt</th>
                    <th scope="col" class="text-center">Quyền hạn</th>
                    <?php if ($canManageRole): ?>
                    <th scope="col" class="text-center col-actions"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($roles)): ?>
                    <?php foreach ($roles as $index => $role): ?>
                        <?php $canManageThisRole = $canUpdateRolePermissions || $canUpdateRole || ($canDeleteRole && !($role['is_system'] ?? false)); ?>
                        <tr>
                            <td class="text-center text-stt"><?= $index + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-name fw-semibold"><?= htmlspecialchars($role['name']) ?></span>
                                </div>
                                <small class="text-slate-400">Slug: <?= htmlspecialchars($role['slug']) ?></small>
                            </td>
                            <td class="text-meta">
                                <?= htmlspecialchars($role['description'] ?? 'Không có mô tả') ?>
                            </td>
                            <td class="text-center">
                                <?php if (isset($role['is_system']) && $role['is_system']): ?>
                                    <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.65rem;">Hệ thống</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center">
                                    <input class="form-check-input" type="checkbox" <?= ($role['is_active'] ?? false) ? 'checked' : '' ?> disabled>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="role-badge-count">
                                    <?= (int)($role['permissions_count'] ?? 0) ?> quyền
                                </span>
                            </td>

                            <?php if ($canManageRole): ?>
                            <td>
                                <?php if ($canManageThisRole): ?>
                                <div class="dropdown position-static">
                                    <button class="btn btn-link btn-action shadow-none" data-bs-toggle="dropdown">
                                        <i data-lucide="more-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <?php if ($canUpdateRolePermissions): ?>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/admin/roles/<?= $role['id'] ?>/permissions">
                                                <i data-lucide="shield-check" class="size-4 text-primary"></i> Phân quyền
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <?php if ($canUpdateRole): ?>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="javascript:void(0)" onclick="editRole(<?= htmlspecialchars(json_encode($role)) ?>)">
                                                <i data-lucide="edit-3" class="size-4"></i> Chỉnh sửa
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <?php if ($canDeleteRole && !($role['is_system'] ?? false)): ?>
                                            <?php if ($canUpdateRolePermissions || $canUpdateRole): ?>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="javascript:void(0)"
                                                    onclick="showDeleteModal('<?= URLROOT ?>/admin/roles/<?= $role['id'] ?>/delete', 'Bạn có chắc chắn muốn xóa vai trò <?= $role['name'] ?>?')">
                                                    <i data-lucide="trash-2" class="size-4"></i> Xóa
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $canManageRole ? 7 : 6 ?>" class="table-empty">Chưa có vai trò nào được thiết lập.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm/Sửa Role -->
<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="roleModalTitle">Thêm vai trò mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="roleForm" action="<?= URLROOT ?>/admin/roles/create" method="POST">
                <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                <div class="modal-body py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Tên vai trò <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="roleName" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['name'] ?? '') ?>" placeholder="Ví dụ: Quản trị viên" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= $errors['name'] ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Mã định danh (Slug) <span class="text-danger">*</span></label>
                            <input type="text" name="slug" id="roleSlug" class="form-control <?= isset($errors['slug']) ? 'is-invalid' : '' ?>" value="<?= htmlspecialchars($old['slug'] ?? '') ?>" placeholder="Ví dụ: admin" required>
                            <?php if (isset($errors['slug'])): ?>
                                <div class="invalid-feedback"><?= $errors['slug'] ?></div>
                            <?php endif; ?>
                            <div id="slugWarning" class="form-text text-warning d-none" style="font-size: 0.7rem;">Vai trò hệ thống không nên đổi slug.</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" id="roleDescription" class="form-control" rows="3" placeholder="Mô tả ngắn gọn về trách nhiệm của vai trò này"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="roleIsActive" value="1" <?= (isset($old['is_active']) && $old['is_active'] == 0) ? '' : 'checked' ?>>
                            <label class="form-check-label" for="roleIsActive">Kích hoạt vai trò</label>
                        </div>
                        <input type="hidden" name="is_system" id="roleIsSystem" value="0">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4">Lưu thông tin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xác nhận Xóa -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-danger">
                    <i data-lucide="alert-triangle" class="size-12 mx-auto"></i>
                </div>
                <h5 class="fw-bold mb-2">Xác nhận xóa</h5>
                <p class="text-slate-500 mb-0" id="deleteConfirmMessage">Bạn có chắc chắn muốn xóa mục này?</p>
            </div>
            <div class="modal-footer border-top-0 justify-content-center pt-0 pb-4">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Hủy</button>
                <form id="deleteForm" method="POST" action="">
                    <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                    <button type="submit" class="btn btn-danger px-4">Đồng ý xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function resetRoleForm() {
        const form = document.getElementById('roleForm');
        window.NexusPM?.clearFormValidation(form);

        document.getElementById('roleModalTitle').innerText = 'Thêm vai trò mới';
        form.action = `<?= URLROOT ?>/admin/roles/create`;
        document.getElementById('roleName').value = '';
        document.getElementById('roleSlug').value = '';
        document.getElementById('roleDescription').value = '';
        document.getElementById('roleIsActive').checked = true;
        document.getElementById('roleIsSystem').value = '0';
        document.getElementById('slugWarning').classList.add('d-none');
    }

    function editRole(role) {
        resetRoleForm();
        const modal = new bootstrap.Modal(document.getElementById('roleModal'));
        const slugInput = document.getElementById('roleSlug');
        const slugWarning = document.getElementById('slugWarning');

        document.getElementById('roleModalTitle').innerText = 'Chỉnh sửa vai trò';
        document.getElementById('roleForm').action = `<?= URLROOT ?>/admin/roles/${role.id}/update`;
        document.getElementById('roleName').value = role.name;
        slugInput.value = role.slug;
        document.getElementById('roleDescription').value = role.description || '';
        document.getElementById('roleIsActive').checked = parseInt(role.is_active) === 1;

        // Nếu là vai trò hệ thống, hạn chế sửa slug để tránh lỗi logic permission
        if (role.is_system == 1) {
            slugWarning.classList.remove('d-none');
        } else {
            slugWarning.classList.add('d-none');
        }

        document.getElementById('roleIsSystem').value = role.is_system || 0;
        modal.show();
    }

    document.getElementById('roleModal').addEventListener('hidden.bs.modal', function() {
        resetRoleForm();
    });

    <?php if (!empty($errors)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($old['id'])): ?>
                document.getElementById('roleModalTitle').innerText = 'Chỉnh sửa vai trò';
                document.getElementById('roleForm').action = `<?= URLROOT ?>/admin/roles/<?= (int)$old['id'] ?>/update`;
            <?php endif; ?>
            const roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
            roleModal.show();
        });
    <?php endif; ?>
</script>

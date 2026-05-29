<?php

/**
 * @var array $role
 * @var array $roles
 * @var array $permissionsByGroup (Mảng quyền đã được nhóm theo module)
 * @var array $activePermissions (Danh sách ID quyền mà Role này đang có)
 */
$roles = $roles ?? [];
$permissionsByGroup = $permissionsByGroup ?? [];
$activePermissionIds = array_map('strval', $activePermissions ?? []);
$currentRoleId = (string)($role['id'] ?? '');
$normalizeSearchText = static function (string $text): string {
    return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
};
$moduleNames = [
    'dashboard' => 'Tổng quan',
    'users' => 'Người dùng',
    'projects' => 'Dự án',
    'tasks' => 'Công việc',
    'settings' => 'Cài đặt hệ thống',
    'project_statuses' => 'Trạng thái dự án',
    'task_statuses' => 'Trạng thái công việc',
    'job_titles' => 'Chức danh',
    'roles' => 'Vai trò & phân quyền',
];
$moduleIcons = [
    'dashboard' => 'layout-dashboard',
    'users' => 'users',
    'projects' => 'folder-kanban',
    'tasks' => 'list-checks',
    'settings' => 'settings',
    'project_statuses' => 'flag',
    'task_statuses' => 'columns-3',
    'job_titles' => 'briefcase-business',
    'roles' => 'shield-check',
];
$moduleOrder = array_keys($moduleNames);
$getModuleSortIndex = static function (string $module) use ($moduleOrder): int {
    $index = array_search($module, $moduleOrder, true);
    return $index === false ? 999 : $index;
};
$getPermissionScope = static function (array $permission): string {
    $slug = (string)($permission['slug'] ?? '');
    return preg_match('/\.(own|joined|project)$/', $slug) ? 'personal' : 'all';
};
uksort($permissionsByGroup, static function (string $a, string $b) use ($getModuleSortIndex): int {
    return $getModuleSortIndex($a) <=> $getModuleSortIndex($b);
});
?>
<style>
    .min-w-0 {
        min-width: 0;
    }

    .permission-module-title {
        flex: 1 1 auto;
        min-width: 0;
    }

    .permission-switch-cell {
        width: 3.5rem;
        flex: 0 0 3.5rem;
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    .permission-switch-cell .form-check {
        padding: 0;
        margin: 0;
        min-height: auto;
    }

    .permission-switch-cell .form-check-input {
        margin-left: 0;
        margin-top: 0;
    }

    .permission-check-cell {
        width: 1.5rem;
        flex: 0 0 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .permission-check-cell .form-check {
        padding: 0;
        margin: 0;
        min-height: auto;
    }

    .permission-check-cell .form-check-input {
        margin: 0;
        cursor: pointer;
    }

    .permission-module-icon {
        width: 2rem;
        height: 2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: var(--primary-50);
        color: var(--primary-700);
        flex: 0 0 auto;
    }

    .permission-section + .permission-section {
        border-top: 1px solid var(--slate-100);
        margin-top: 0.75rem;
        padding-top: 0.75rem;
    }

    .permission-section-header {
        padding: 0 0.5rem;
        margin-bottom: 0.5rem;
    }

    .permission-section-title {
        font-size: 0.75rem;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .permission-list {
        display: grid;
        gap: 0.375rem;
    }

    .permission-row {
        border-radius: 8px;
        padding: 0.5rem;
        transition: background-color 0.15s ease;
    }

    .permission-row:hover {
        background: var(--slate-50);
    }

    .permission-slug {
        font-size: 0.72rem;
        word-break: break-word;
    }

    .permission-actions-bar {
        margin-bottom: 1.5rem;
    }

    .permission-form-actions {
        margin-left: auto;
        justify-content: flex-end;
    }

    .permission-form-actions .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    @media (max-width: 767.98px) {
        .permission-actions-bar {
            align-items: stretch !important;
        }

        .permission-form-actions {
            width: 100%;
            margin-left: 0;
        }

        .permission-form-actions .btn {
            flex: 1 1 auto;
            justify-content: center;
        }
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/settings" class="text-decoration-none text-slate-500 hover-text-primary">Hệ thống</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <a href="<?= URLROOT; ?>/admin/roles" class="text-decoration-none text-slate-500 hover-text-primary">Vai trò & Phân quyền</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Phân quyền</span>
    </div>
</div>

<div class="permission-actions-bar d-flex flex-wrap justify-content-between align-items-center gap-3 px-1">
    <div class="project-context d-flex align-items-center gap-3 min-w-0">
        <div class="dropdown tasks-project-dropdown project-switcher" data-project-switcher>
            <button class="btn btn-link project-switcher-trigger text-decoration-none shadow-none border-0" type="button" data-bs-toggle="dropdown" data-bs-offset="0,8" aria-expanded="false">
                <span class="project-switcher-icon">
                    <i data-lucide="shield-check"></i>
                </span>
                <span class="project-switcher-text">
                    <span class="project-switcher-eyebrow">Vai trò</span>
                    <span class="project-switcher-title"><?= htmlspecialchars($role['name'] ?? 'Chọn vai trò', ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <i data-lucide="chevron-down" class="project-switcher-chevron"></i>
            </button>

            <ul class="dropdown-menu dropdown-menu-start shadow-xl border-0">
                <li class="project-switcher-search px-3 py-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-slate-400">
                            <i data-lucide="search" size="16"></i>
                        </span>
                        <input type="search" class="form-control border-start-0" placeholder="Tìm vai trò..." data-project-switcher-search>
                    </div>
                </li>

                <li class="px-0 py-0">
                    <div class="project-dropdown-scroll">
                        <?php if (!empty($roles)): ?>
                            <?php foreach ($roles as $item): ?>
                                <?php
                                $isCurrentRole = (string)($item['id'] ?? '') === $currentRoleId;
                                $roleName = (string)($item['name'] ?? 'Vai trò');
                                $roleSlug = (string)($item['slug'] ?? '');
                                $roleSearchText = $normalizeSearchText(trim($roleName . ' ' . $roleSlug));
                                ?>
                                <a class="dropdown-item project-switcher-item <?= $isCurrentRole ? 'active' : '' ?>"
                                    href="<?= URLROOT ?>/admin/roles/<?= (int)($item['id'] ?? 0) ?>/permissions"
                                    data-project-switcher-item
                                    data-project-search="<?= htmlspecialchars($roleSearchText, ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="project-switcher-item-icon">
                                        <i data-lucide="<?= !empty($item['is_system']) ? 'lock-keyhole' : 'shield' ?>"></i>
                                    </span>
                                    <span class="project-switcher-item-main">
                                        <span class="project-switcher-item-title"><?= htmlspecialchars($roleName, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="project-switcher-item-meta">
                                            <?= htmlspecialchars($roleSlug !== '' ? $roleSlug : 'Chưa có slug', ENT_QUOTES, 'UTF-8') ?>
                                            <?php if (empty($item['is_active'])): ?>
                                                <span class="project-switcher-dot">&middot;</span>
                                                Ngưng kích hoạt
                                            <?php endif; ?>
                                        </span>
                                    </span>
                                    <?php if ($isCurrentRole): ?>
                                        <i data-lucide="check" class="project-switcher-check"></i>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="project-switcher-empty">
                                Chưa có vai trò nào.
                            </div>
                        <?php endif; ?>

                        <div class="project-switcher-empty d-none" data-project-switcher-empty>
                            Không tìm thấy vai trò phù hợp.
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <?php if (!empty($role['slug'])): ?>
            <div class="project-context-meta d-flex align-items-center gap-2 border-start border-slate-200">
                <span class="text-slate-500 small fw-medium"><?= htmlspecialchars($role['slug'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($role['is_system'])): ?>
                    <span class="ui-badge status-muted py-0 px-2" style="font-size: 11px;">Hệ thống</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="page-actions permission-form-actions">
        <a href="<?= URLROOT ?>/admin/roles" class="btn btn-outline-secondary">
            <i data-lucide="arrow-left"></i>
            <span>Quay lại</span>
        </a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload();">
            <i data-lucide="refresh-cw"></i>
            <span>Khôi phục</span>
        </button>
        <button type="submit" form="permissionsForm" class="btn btn-primary px-4">
            <i data-lucide="save"></i>
            <span>Lưu lại</span>
        </button>
    </div>
</div>

<form id="permissionsForm" action="<?= URLROOT ?>/admin/roles/<?= $role['id'] ?>/permissions" method="POST">
    <?php \App\helpers\SecurityHelper::csrfInput(); ?>
    <div class="row row-cols-1 row-cols-xl-2 g-3">
        <?php foreach ($permissionsByGroup as $groupName => $permissions): ?>
            <?php
            $permissionsByScope = [
                'all' => [],
                'personal' => [],
            ];
            foreach ($permissions as $permission) {
                $permissionsByScope[$getPermissionScope($permission)][] = $permission;
            }
            $visibleScopes = array_filter($permissionsByScope, static fn(array $items): bool => !empty($items));
            $moduleLabel = $moduleNames[$groupName] ?? ucfirst(str_replace('_', ' ', $groupName));
            $moduleIcon = $moduleIcons[$groupName] ?? 'box';
            ?>
            <div class="col">
                <div class="ui-card permission-module-card h-100 overflow-hidden">
                    <div class="ui-card-header bg-slate-50 d-flex align-items-start gap-3 py-3">
                        <div class="d-flex align-items-center gap-3 permission-module-title">
                            <span class="permission-module-icon">
                                <i data-lucide="<?= htmlspecialchars($moduleIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                            </span>
                            <div class="min-w-0">
                                <h6 class="mb-0 fw-bold text-slate-800">
                                    <?= htmlspecialchars($moduleLabel, ENT_QUOTES, 'UTF-8') ?>
                                </h6>
                                <small class="text-slate-500">
                                    <?= count($permissions) ?> quyền
                                </small>
                            </div>
                        </div>
                        <div class="permission-switch-cell">
                            <div class="form-check form-switch">
                                <input class="form-check-input select-all-group" type="checkbox" role="switch" title="Chọn toàn bộ module">
                            </div>
                        </div>
                    </div>
                    <div class="ui-card-body p-3">
                        <?php foreach (['all' => 'Tất cả', 'personal' => 'Cá nhân / dự án của mình'] as $scopeKey => $scopeLabel): ?>
                            <?php if (empty($permissionsByScope[$scopeKey])): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <?php $showScopeHeader = count($visibleScopes) > 1 || $scopeKey !== 'all'; ?>

                            <div class="permission-section" data-permission-scope="<?= htmlspecialchars($scopeKey, ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($showScopeHeader): ?>
                                    <div class="permission-section-header d-flex align-items-center gap-2">
                                        <div class="permission-check-cell">
                                            <div class="form-check">
                                                <input class="form-check-input select-scope" type="checkbox" title="Chọn nhóm quyền">
                                            </div>
                                        </div>
                                        <span class="permission-section-title fw-semibold text-slate-500">
                                            <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                <?php endif; ?>

                                <div class="permission-list">
                                    <?php foreach ($permissionsByScope[$scopeKey] as $permission): ?>
                                        <?php
                                        $permissionId = (string)($permission['id'] ?? '');
                                        $isChecked = in_array($permissionId, $activePermissionIds, true);
                                        ?>
                                        <div class="permission-row d-flex align-items-start gap-2">
                                            <div class="permission-check-cell pt-1">
                                                <div class="form-check">
                                                    <input class="form-check-input permission-checkbox"
                                                        type="checkbox"
                                                        name="permission_ids[]"
                                                        value="<?= htmlspecialchars($permissionId, ENT_QUOTES, 'UTF-8') ?>"
                                                        id="perm_<?= htmlspecialchars($permissionId, ENT_QUOTES, 'UTF-8') ?>"
                                                        <?= $isChecked ? 'checked' : '' ?>>
                                                </div>
                                            </div>
                                            <label class="flex-grow-1 cursor-pointer m-0" for="perm_<?= htmlspecialchars($permissionId, ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="d-block fw-medium text-slate-800" style="font-size: 0.875rem;">
                                                    <?= htmlspecialchars($permission['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <small class="permission-slug text-slate-500 d-block">
                                                    <?= htmlspecialchars($permission['slug'], ENT_QUOTES, 'UTF-8') ?>
                                                </small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
    function setSwitchState(switcher, checkboxes) {
        if (!switcher) return;

        const items = Array.from(checkboxes);
        const checkedCount = items.filter(cb => cb.checked).length;

        switcher.checked = items.length > 0 && checkedCount === items.length;
        switcher.indeterminate = checkedCount > 0 && checkedCount < items.length;
        switcher.disabled = items.length === 0;
    }

    function refreshPermissionSwitches(card) {
        const moduleSwitcher = card.querySelector('.select-all-group');
        const moduleCheckboxes = card.querySelectorAll('.permission-checkbox');
        setSwitchState(moduleSwitcher, moduleCheckboxes);

        card.querySelectorAll('.permission-section').forEach(panel => {
            const scopeSwitcher = panel.querySelector('.select-scope');
            const scopeCheckboxes = panel.querySelectorAll('.permission-checkbox');
            setSwitchState(scopeSwitcher, scopeCheckboxes);
        });
    }

    document.querySelectorAll('.permission-module-card').forEach(card => {
        const moduleSwitcher = card.querySelector('.select-all-group');

        if (moduleSwitcher) {
            moduleSwitcher.addEventListener('change', function() {
                card.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = this.checked);
                refreshPermissionSwitches(card);
            });
        }

        card.querySelectorAll('.select-scope').forEach(scopeSwitcher => {
            scopeSwitcher.addEventListener('change', function() {
                const panel = this.closest('.permission-section');
                panel.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = this.checked);
                refreshPermissionSwitches(card);
            });
        });

        card.querySelectorAll('.permission-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', () => refreshPermissionSwitches(card));
        });

        refreshPermissionSwitches(card);
    });
</script>

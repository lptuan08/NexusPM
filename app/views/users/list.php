<!-- INDEX - DANH SÁCH NHÂN VIÊN -->
<?php

/**
 * @var array $data
 * @var int $currentPage
 * @var int $perPage
 * @var int $totalUsers
 * @var int $totalPage
 * @var array $jobTitleOptions
 * @var array $roleOptions
 * @var array $currentFilters
 * @var string $pageTitle
 */
?>
<style>
    .user-list-name {
        max-width: 360px;
    }

    .table-footer-outside {
        background: transparent;
        padding: 1rem 0;
        border: none;
    }

    .table-container {
        height: calc(100vh - 300px);
        /* Tính toán chiều cao dựa trên màn hình (trừ header/toolbar/footer) */
        overflow-y: auto;
        position: relative;
    }

    .table-custom thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    /* Tùy chỉnh phân trang đồng bộ với dự án */
    .pagination {
        gap: 0.5rem;
    }

    .pagination .page-link {
        border-radius: 0.375rem !important;
        border: 1px solid #e2e8f0;
        color: #64748b;
        min-width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 0.5rem;
    }

    .pagination .page-item.active .page-link {
        background-color: #4f46e5;
        border-color: #4f46e5;
    }

    .pagination .page-item.disabled .page-link {
        background-color: #f8fafc;
        color: #cbd5e1;
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/admin/settings" class="text-decoration-none text-slate-500 hover-text-primary">Hệ thống</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Nhân viên</span>
    </div>

    <div class="page-actions">
        <button id="filterButton" class="btn btn-outline-secondary" title="Lọc dữ liệu" data-bs-toggle="modal"
            data-bs-target="#filterModal">
            <i data-lucide="filter"></i>
            <span class="d-none d-md-inline">Bộ lọc</span>
        </button>

        <a href="<?= URLROOT; ?>/users/create" class="btn btn-primary">
            <i data-lucide="user-plus"></i>
            <span>Thêm nhân viên</span>
        </a>
    </div>
</div>

<!-- Bảng Dữ Liệu -->
<div class="table-container mb-3">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="text-center col-stt">STT</th>
                    <th scope="col">Họ và Tên</th>
                    <th scope="col">Mã NV</th>
                    <th scope="col">Email</th>
                    <th scope="col">Chức danh</th>
                    <th scope="col">Vai trò</th>
                    <th scope="col" class="text-center col-actions"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)): ?>
                    <?php foreach ($data as $index => $user): ?>
                        <tr>
                            <td class="text-center text-stt"><?= ($currentPage - 1) * $perPage + $index + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php
                                    $displayName = $user['name'] ?? 'N/A';
                                    $physicalPath = APPROOT . '/public/uploads/avatars/' . ($user['avatar'] ?? '');
                                    $avatarUrl = (!empty($user['avatar']) && file_exists($physicalPath))
                                        ? URLROOT . '/uploads/avatars/' . $user['avatar']
                                        : "https://ui-avatars.com/api/?name=" . urlencode($displayName) . "&background=e8f0fe&color=1a73e8&rounded=true";
                                    ?>


                                    <img src="<?= $avatarUrl ?>"
                                        alt="Avatar" class="avatar">
                                    <a href="<?= URLROOT ?>/users/<?= (int) $user['id'] ?>" class="text-decoration-none text-name">
                                        <?= htmlspecialchars($displayName) ?>
                                    </a>
                                </div>
                            </td>
                            <td class="text-meta"><?= htmlspecialchars($user['employee_code'] ?? 'N/A') ?></td>
                            <td class="text-meta"><?= htmlspecialchars($user['email'] ?? '') ?></td>
                            <td><span class="text-meta"><?= htmlspecialchars($user['job_title'] ?? 'Chưa cập nhật') ?></span></td>
                            <td><span class="badge rounded-pill bg-light text-dark border fw-normal"><?= htmlspecialchars($user['role_name'] ?? 'N/A') ?></span></td>

                            <td>
                                <div class="dropdown position-static">
                                    <button class="btn btn-link btn-action shadow-none"
                                        data-bs-toggle="dropdown"><i data-lucide="more-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/users/<?= $user['id'] ?>"><i data-lucide="eye" class="text-slate-600"></i> Chi tiết</a></li>
                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/users/<?= $user['id'] ?>/edit"><i data-lucide="edit-3" class="text-slate-600"></i> Chỉnh sửa</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                href="javascript:void(0)"
                                                onclick="showDeleteModal('<?= URLROOT ?>/users/<?= (int) $user['id'] ?>/delete', <?= htmlspecialchars(json_encode('Bạn có chắc chắn muốn xóa nhân viên ' . $displayName . '?', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                                <i data-lucide="trash-2"></i> Xóa
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="table-empty">Không có dữ liệu nhân viên nào được tìm thấy.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div class="table-footer-outside d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <?php
    // Hàm hỗ trợ tạo URL phân trang giữ lại các tham số filter hiện tại (search, job_title, role, v.v.)
    $buildPageUrl = function ($page) {
        $queryParams = $_GET;
        $queryParams['page'] = $page;
        return '?' . http_build_query($queryParams);
    };
    ?>
    <?php
    $from = ($totalUsers > 0) ? ($currentPage - 1) * $perPage + 1 : 0;
    $to = min($currentPage * $perPage, $totalUsers);
    ?>
    <span class="table-pagination-info">
        Hiển thị <?= $from ?> đến <?= $to ?> của <?= $totalUsers ?> kết quả
    </span>
    <div class="d-flex align-items-center gap-2">
        <nav aria-label="Điều hướng trang">
            <ul class="pagination pagination-sm m-0">
                <?php if ($currentPage == 1): ?>
                    <li class="page-item disabled"> <!-- Nút Previous bị disabled khi ở trang đầu -->
                        <span class="page-link">
                            <i data-lucide="chevron-left" class="size-4"></i>
                        </span>
                    </li>
                <?php else: ?>
                    <li class="page-item"> <!-- Sửa nút Previous khi trang > 1 -->
                        <a class="page-link" href="<?= $buildPageUrl($currentPage - 1) ?>">
                            <i data-lucide="chevron-left" class="size-4"></i>
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $max_visible = 5;
                if ($totalPage <= $max_visible):
                    for ($i = 1; $i <= $totalPage; $i++): ?>
                        <li class="page-item <?= ($i == $currentPage) ? 'active' : '' ?>">
                            <?php if ($i == $currentPage): ?>
                                <span class="page-link"><?= $i ?></span>
                            <?php else: ?>
                                <a class="page-link" href="<?= $buildPageUrl($i) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        </li>
                        <?php endfor;
                else:
                    // Xác định vị trí bắt đầu để giữ số lượng nút luôn là 5
                    $start = max(1, min($currentPage - 2, $totalPage - $max_visible + 1));
                    for ($j = 0; $j < $max_visible; $j++):
                        $p = $start + $j;
                        if ($j == 0 && $p > 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php elseif ($j == $max_visible - 1 && $p < $totalPage): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php else: ?>
                            <li class="page-item <?= ($p == $currentPage) ? 'active' : '' ?>">
                                <?php if ($p == $currentPage): ?>
                                    <span class="page-link"><?= $p ?></span>
                                <?php else: ?>
                                    <a class="page-link" href="<?= $buildPageUrl($p) ?>"><?= $p ?></a>
                                <?php endif; ?>
                            </li>
                <?php endif;
                    endfor;
                endif; ?>

                <?php if ($currentPage >= $totalPage): ?>
                    <li class="page-item disabled"> <!-- Nút Next bị disabled khi ở trang cuối -->
                        <span class="page-link">
                            <i data-lucide="chevron-right" class="size-4"></i>
                        </span>
                    </li>
                <?php else: ?>
                    <li class="page-item"> <!-- Nút Next -->
                        <a class="page-link" href="<?= $buildPageUrl($currentPage + 1) ?>">
                            <i data-lucide="chevron-right" class="size-4"></i>
                        </a>
                    </li>
                <?php endif; ?>


            </ul>
        </nav>
    </div>

</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-confirm-dialog">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Bộ lọc nhân viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" method="GET">
                    <input type="hidden" name="page" value="1"> <!-- Reset to page 1 on filter -->
                    <div class="mb-3">
                        <label for="searchFilter" class="form-label fw-semibold small">Tìm kiếm</label>
                        <input type="text" class="form-control form-control-sm" id="searchFilter" name="search"
                            placeholder="Tên, Email, Mã NV..."
                            value="<?= htmlspecialchars($currentFilters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Chức danh</label>
                        <?php if (!empty($jobTitleOptions)): ?>
                            <?php foreach ($jobTitleOptions as $jobTitle): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="job_title[]"
                                        value="<?= htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') ?>"
                                        id="jobTitleCheck<?= htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= in_array($jobTitle, $currentFilters['job_title'] ?? []) ? 'checked' : '' ?>>
                                    <label class="form-check-label"
                                        for="jobTitleCheck<?= htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="small text-muted">Không có chức danh nào.</p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Vai trò</label>
                        <?php if (!empty($roleOptions)): ?>
                            <?php foreach ($roleOptions as $role): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="role_id[]"
                                        value="<?= $role['id'] ?>"
                                        id="roleCheck<?= $role['id'] ?>"
                                        <?= in_array($role['id'], $currentFilters['role_id'] ?? []) ? 'checked' : '' ?>>
                                    <label class="form-check-label"
                                        for="roleCheck<?= $role['id'] ?>">
                                        <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="small text-muted">Không có vai trò nào.</p>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="createdAtStartFilter" class="form-label fw-semibold small">Ngày tạo từ</label>
                        <input type="date" class="form-control form-control-sm" id="createdAtStartFilter"
                            name="created_at_start"
                            value="<?= htmlspecialchars($currentFilters['created_at_start'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="mb-3">
                        <label for="createdAtEndFilter" class="form-label fw-semibold small">Ngày tạo đến</label>
                        <input type="date" class="form-control form-control-sm" id="createdAtEndFilter"
                            name="created_at_end"
                            value="<?= htmlspecialchars($currentFilters['created_at_end'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="d-flex gap-2 pt-3 border-top mt-4">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Áp dụng</button>
                        <a href="<?= URLROOT ?>/users" class="btn btn-outline-secondary btn-sm w-100">Đặt lại</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
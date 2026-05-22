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
$listTableConfig = \App\helpers\ListTableHelper::config();
$maxVisiblePages = max(1, (int) ($listTableConfig['max_visible_pages'] ?? 5));
?>
<style>
        /* Tính toán chiều cao dựa trên màn hình (trừ header/toolbar/footer) */
    /* Tùy chỉnh phân trang đồng bộ với dự án */
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/settings" class="text-decoration-none text-slate-500 hover-text-primary">Hệ thống</a>
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
            <span>Thêm mới</span>
        </a>
    </div>
</div>

<!-- Bảng Dữ Liệu -->
<div class="table-container table-container-paginated mb-3">
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
                $max_visible = $maxVisiblePages;
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

<!-- Delete Confirm Modal -->
<div class="modal fade modal-confirm" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-confirm-dialog">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-body text-center">
                <div class="icon-box">
                    <i data-lucide="alert-triangle" size="32"></i>
                </div>
                <h5 class="fw-bold text-slate-800 mb-2">Xác nhận xóa</h5>
                <p class="text-slate-500 small mb-4" id="deleteConfirmMessage">Hành động này không thể hoàn tác. Bạn có chắc chắn?</p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Hủy bỏ</button>
                    <form id="deleteForm" method="POST" action="" class="w-100 m-0">
                        <?php \App\helpers\SecurityHelper::csrfInput(); ?>
                        <button type="submit" class="btn btn-danger w-100">Xác nhận xóa</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold text-slate-800" id="filterModalLabel">Bộ lọc nâng cao</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="GET" class="m-0">
                <div class="modal-body p-4">
                    <input type="hidden" name="page" value="1">
                    <div class="row g-4">
                        <!-- Cột 1: Tìm kiếm & Chức danh -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label for="searchFilter" class="form-label fw-semibold small text-slate-600">Tìm kiếm</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-slate-400"><i data-lucide="search" size="18"></i></span>
                                    <input type="text" class="form-control border-start-0" id="searchFilter" name="search"
                                        placeholder="Tên, Email, Mã NV..."
                                        value="<?= htmlspecialchars($currentFilters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-semibold small text-slate-600">Chức danh</label>
                                <select name="job_title[]" class="form-select" multiple style="height: 160px;">
                                    <?php if (!empty($jobTitleOptions)): ?>
                                        <?php foreach ($jobTitleOptions as $jobTitle): ?>
                                            <option value="<?= htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') ?>"
                                                <?= in_array($jobTitle, $currentFilters['job_title'] ?? []) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text small mt-2">Giữ Ctrl/Cmd để chọn nhiều chức danh.</div>
                            </div>
                        </div>

                        <!-- Cột 2: Vai trò & Ngày tạo -->
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-slate-600">Vai trò hệ thống</label>
                                <div class="border rounded p-3 bg-slate-50 overflow-auto" style="height: 125px;">
                                    <?php if (!empty($roleOptions)): ?>
                                        <?php foreach ($roleOptions as $role): ?>
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="role_id[]"
                                                    value="<?= $role['id'] ?>"
                                                    id="roleCheck<?= $role['id'] ?>"
                                                    <?= in_array($role['id'], $currentFilters['role_id'] ?? []) ? 'checked' : '' ?>>
                                                <label class="form-check-label small" for="roleCheck<?= $role['id'] ?>">
                                                    <?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-semibold small text-slate-600">Ngày gia nhập hệ thống</label>
                                <div class="d-flex flex-column gap-2">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-slate-500 w-25">Từ</span>
                                        <input type="date" class="form-control" name="created_at_start"
                                            value="<?= htmlspecialchars($currentFilters['created_at_start'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white text-slate-500 w-25">Đến</span>
                                        <input type="date" class="form-control" name="created_at_end"
                                            value="<?= htmlspecialchars($currentFilters['created_at_end'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top p-3 bg-light">
                    <a href="<?= URLROOT ?>/users" class="btn btn-outline-secondary px-4">Đặt lại bộ lọc</a>
                    <button type="submit" class="btn btn-primary px-5">
                        <i data-lucide="filter"></i>
                        <span>Áp dụng</span>
                    </button>
                </div>
            </form>
        </div>
        </div>
    </div>
</div>

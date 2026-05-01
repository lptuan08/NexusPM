<!-- INDEX - DANH SÁCH NHÂN VIÊN -->
<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <span class="page-title">Nhân viên</span>
    </div>

    <div class="page-actions">
        <button class="btn btn-outline-secondary" title="Lọc dữ liệu">
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
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="text-center col-stt">STT</th>
                    <th scope="col">Họ và Tên</th>
                    <th scope="col">Mã NV</th>
                    <th scope="col">Email</th>
                    <th scope="col">Chức danh</th>
                    <th scope="col" class="text-center col-actions"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)): ?>
                    <?php $stt = 1; ?>
                    <?php foreach ($data as $user): ?>
                        <tr>
                            <td class="text-center text-stt"><?= $stt++ ?></td>
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
                                    <div class="text-name"><?= htmlspecialchars($displayName) ?></div>
                                </div>
                            </td>
                            <td class="text-meta"><?= htmlspecialchars($user['employee_code'] ?? 'N/A') ?></td>
                            <td class="text-meta"><?= htmlspecialchars($user['email'] ?? '') ?></td>
                            <td><span class="text-meta"><?= htmlspecialchars($user['job_title'] ?? 'Chưa cập nhật') ?></span></td>

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
                        <td colspan="6" class="table-empty">Không có dữ liệu nhân viên nào được tìm thấy.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <?php
            $from = ($totalUsers > 0) ? ($currentPage - 1) * $perPage + 1 : 0;
            $to = min($currentPage * $perPage, $totalUsers);
        ?>
        <span class="table-pagination-info">
            Hiển thị <?= $from ?> đến <?= $to ?> của <?= $totalUsers ?> kết quả
        </span>
        <div class="d-flex align-items-center gap-2">
            <nav aria-label="Điều hướng trang">
                        <ul class="pagination pagination-sm m-0 gap-2">
                            <?php if ($currentPage == 1): ?>
                                <li class="page-item disabled"> <!-- Nút Previous bị disabled khi ở trang đầu -->
                                    <a class="page-link" href="#">
                                        <i data-lucide="chevron-left" size="16"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                        <li class="page-item"> <!-- Sửa nút Previous khi trang > 1 -->
                                    <a class="page-link" href="?page=<?= htmlspecialchars($currentPage - 1) ?>">
                                        <i data-lucide="chevron-left" size="16"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                    if (!empty($pages)) foreach ($pages as $p): // Hiển thị các nút số trang ?>
                                <?php if ($p == $currentPage): ?>
                                    <li class="page-item active">
                                        <a class="page-link" href="?page=<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></a> <!-- Link đến trang cụ thể -->
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>

                    <?php if ($currentPage >= $totalPage): ?>
                                <li class="page-item disabled"> <!-- Nút Next bị disabled khi ở trang cuối -->
                                    <a class="page-link" href="#">
                                        <i data-lucide="chevron-right" size="16"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item"> <!-- Nút Next -->
                                    <a class="page-link" href="?page=<?= htmlspecialchars($currentPage + 1) ?>">
                                        <i data-lucide="chevron-right" size="16"></i>
                                    </a>
                                </li>
                            <?php endif; ?>


                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- MODAL XÁC NHẬN XÓA DÙNG CHUNG -->
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
                                <?php App\helpers\SecurityHelper::csrfInput(); ?> <!-- Thêm CSRF token vào form xóa -->
                                
                                <button type="submit" class="btn btn-danger w-100">Xác nhận xóa</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<!-- INDEX - DANH SÁCH NHÂN VIÊN -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <span class="fw-medium text-slate-800 fs-5">Nhân viên</span>
    </div>

    <div class="d-flex align-items-center gap-2">
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
                    <th scope="col" class="text-center" style="width: 50px;">STT</th>
                    <th scope="col">Họ và Tên</th>
                    <th scope="col">Mã NV</th>
                    <th scope="col">Email</th>
                    <th scope="col">Chức danh</th>
                    <th scope="col" style="width: 60px; text-align: center;"></th>
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
                                    <button class="btn btn-link text-slate-500 p-1 shadow-none"
                                        data-bs-toggle="dropdown"><i data-lucide="more-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/users/<?= $user['id'] ?>"><i data-lucide="eye" class="text-600"></i> Chi tiết</a></li>
                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/users/<?= $user['id'] ?>/edit"><i data-lucide="edit-3" class="text-600"></i> Chỉnh sửa</a></li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                href="javascript:void(0)"
                                                onclick="showDeleteModal('<?= URLROOT ?>/users/<?= $user['id'] ?>/delete', 'Bạn có chắc chắn muốn xóa nhân viên <?= htmlspecialchars($displayName) ?>?')">
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
                        <td colspan="6" class="text-center py-4 text-muted">Không có dữ liệu nhân viên nào được tìm thấy.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex align-items-center justify-content-between p-3 border-top border-slate-200 bg-white">
        <span class="text-slate-500" style="font-size: 0.875rem;">Hiển thị 1 đến 5 của 124 kết quả</span>
        <div class="d-flex align-items-center gap-2">
            <div class="px-4 py-3 bg-white border-slate-100">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">


                    <nav aria-label="Điều hướng trang" class="order-1 order-md-2">
                        <ul class="pagination pagination-sm m-0 gap-2">
                            <?php if ($currentPage == 1): ?>
                                <li class="page-item disabled">
                                    <a class="page-link border-0 rounded-circle d-flex align-items-center justify-content-center bg-slate-50 text-slate-400" href="?page=0" style="width: 32px; height: 32px;">
                                        <i data-lucide="chevron-left" size="16"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link border-0 rounded-circle d-flex align-items-center justify-content-center bg-slate-50 text-slate-400" href="?page=0" style="width: 32px; height: 32px;">
                                        <i data-lucide="chevron-left" size="16"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            foreach ($pages as $p): ?>
                                <?php if ($p == $currentPage): ?>
                                    <li class="page-item active">
                                        <a class="page-link border-0 rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" href="?page=<?= htmlspecialchars($p) ?>" style="width: 32px; height: 32px;"><?= htmlspecialchars($p) ?></a>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item">
                                        <a class="page-link border-0 rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" href="?page=<?= htmlspecialchars($p) ?>" style="width: 32px; height: 32px;"><?= htmlspecialchars($p) ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($currentPage == $totalPage): ?>
                                <li class="page-item disabled">
                                    <a class="page-link border-0 rounded-circle d-flex align-items-center justify-content-center text-slate-600 hover-bg-slate-100" href="?page=<?= htmlspecialchars($totalPage) ?>" style="width: 32px; height: 32px;">
                                        <i data-lucide="chevron-right" size="16"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disable">
                                    <a class="page-link border-0 rounded-circle d-flex align-items-center justify-content-center text-slate-600 hover-bg-slate-100" href="?page=<?= htmlspecialchars($totalPage) ?>" style="width: 32px; height: 32px;">
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
            <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
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
                                <button type="submit" class="btn btn-danger w-100">Xác nhận xóa</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
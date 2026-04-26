<?php
$projectStatusMap = [
    'planning'  => ['text' => 'Lên kế hoạch', 'class' => 'bg-slate-500', 'color' => '#64748b'],
    'active'    => ['text' => 'Đang chạy', 'class' => 'bg-primary-600', 'color' => '#1a73e8'],
    'completed' => ['text' => 'Hoàn thành', 'class' => 'bg-success-600', 'color' => '#0d9488'],
];
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center text-slate-600">
        <span class="fw-medium text-slate-800 fs-5">Dự án</span>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="<?= URLROOT; ?>/projects/create" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Tạo dự án mới</span>
        </a>
    </div>
</div>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th scope="col" class="text-center" style="width: 50px;">STT</th>
                    <th scope="col">Tên dự án</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Ngày bắt đầu</th>
                    <th scope="col">Hạn xử lý (Deadline)</th>
                    <th scope="col" style="width: 50px; text-align: center;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($data)): ?>
                    <?php $stt = ($currentPage - 1) * $perPage + 1; ?>
                    <?php foreach ($data as $project): ?>
                        <tr>
                            <td class="text-center text-stt"><?= $stt++ ?></td>
                            <td>
                                <div class="fw-bold text-slate-800"><?= htmlspecialchars($project['name']) ?></div>
                                <div class="text-xs text-slate-500 line-clamp-1"><?= htmlspecialchars($project['description'] ?? 'Không có mô tả') ?></div>
                            </td>
                            <td>
                                <?php $st = $projectStatusMap[$project['status']] ?? ['text' => $project['status'], 'class' => 'bg-slate-200', 'color' => '#94a3b8']; ?>
                                <span class="badge rounded-pill px-3 fw-medium" style="background-color: <?= $st['color'] ?>15; color: <?= $st['color'] ?>;">
                                    <?= $st['text'] ?>
                                </span>
                            </td>
                            <td class="text-meta"><?= $project['start_date'] ? date('d/m/Y', strtotime($project['start_date'])) : '-' ?></td>
                            <td class="text-meta <?= (strtotime($project['due_date']) < time() && $project['status'] != 'completed') ? 'text-danger fw-medium' : '' ?>">
                                <?= $project['due_date'] ? date('d/m/Y', strtotime($project['due_date'])) : '-' ?>
                            </td>
                            <td class="text-center">
                                <div class="dropdown position-static">
                                    <button class="btn btn-link text-slate-500 p-1 shadow-none" data-bs-toggle="dropdown">
                                        <i data-lucide="more-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/projects/<?= $project['id'] ?>"><i data-lucide="eye" size="16"></i> Chi tiết</a></li>
                                        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= URLROOT ?>/projects/<?= $project['id'] ?>/edit"><i data-lucide="edit-3" size="16"></i> Chỉnh sửa</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="javascript:void(0)" 
                                               onclick="showDeleteModal('<?= URLROOT ?>/projects/<?= $project['id'] ?>/delete', 'Bạn có chắc chắn muốn xóa dự án <?= htmlspecialchars($project['name']) ?>?')">
                                                <i data-lucide="trash-2" size="16"></i> Xóa
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-5 text-slate-400">Chưa có dự án nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang tương tự như User List -->
    <div class="d-flex align-items-center justify-content-between p-3 border-top border-slate-100 bg-white">
        <?php 
            $from = ($totalProjects > 0) ? ($currentPage - 1) * $perPage + 1 : 0;
            $to = min($currentPage * $perPage, $totalProjects);
        ?>
        <span class="text-slate-500 small">Hiển thị <?= $from ?> đến <?= $to ?> của <?= $totalProjects ?> kết quả</span>
        <nav>
            <ul class="pagination pagination-sm m-0 gap-2">
                <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link border-0 rounded-circle" href="?page=<?= $currentPage - 1 ?>"><i data-lucide="chevron-left" size="16"></i></a>
                    </li>
                <?php endif; ?>
                <?php foreach ($pages as $p): ?>
                    <li class="page-item <?= $p == $currentPage ? 'active' : '' ?>">
                        <a class="page-link border-0 rounded-circle" href="?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endforeach; ?>
                <?php if ($currentPage < $totalPage): ?>
                    <li class="page-item">
                        <a class="page-link border-0 rounded-circle" href="?page=<?= $currentPage + 1 ?>"><i data-lucide="chevron-right" size="16"></i></a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Modal xác nhận xóa dùng chung JS đã có sẵn -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-body text-center p-4">
                <i data-lucide="alert-triangle" class="text-danger mb-3" size="48"></i>
                <h5 class="fw-bold mb-2">Xác nhận xóa</h5>
                <p class="text-slate-500 small mb-4" id="deleteConfirmMessage"></p>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">Hủy</button>
                    <form id="deleteForm" method="POST" class="w-100 m-0">
                        <?php SecurityHelper::csrfInput(); ?>
                        <button type="submit" class="btn btn-danger w-100">Xác nhận</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
/**
 * Giao diện danh sách công việc - NexusPM
 * 
 * @var array $tasks Danh sách công việc
 * @var array $projects Danh sách dự án (cho bộ lọc)
 * @var array $users Danh sách nhân viên (cho bộ lọc)
 * @var array $statuses Danh sách trạng thái (cho bộ lọc)
 * @var array $filters Dữ liệu filter hiện tại từ request
 * @var array $pagination Thông tin phân trang (current_page, total_pages, etc.)
 * @var array|null $selectedProject Thông tin dự án chi tiết đang chọn
 */

$tasks = $tasks ?? [];
$projects = $projects ?? [];
$users = $users ?? [];
$statuses = $statuses ?? [];
$filters = $filters ?? [];
$pagination = $pagination ?? [];

/**
 * Trích xuất thông tin phân trang
 */
$currentPage = $pagination['current_page'] ?? 1;
$perPage = $pagination['per_page'] ?? 15;
$totalItem = $pagination['total_items'] ?? 0;
$totalPage = $pagination['total_pages'] ?? 1;

/**
 * Hàm closure để tạo URL ảnh đại diện hoặc UI Avatars nếu trống.
 */
$buildAvatar = static function (array $person, string $nameKey = 'name', string $avatarKey = 'avatar', int $size = 32): string {
    $avatar = $person[$avatarKey] ?? null;
    if (!empty($avatar) && file_exists(APPROOT . '/public/uploads/avatars/' . $avatar)) {
        return URLROOT . '/uploads/avatars/' . rawurlencode($avatar);
    }
    $name = $person[$nameKey] ?? 'User';
    return 'https://ui-avatars.com/api/?name=' . urlencode((string) $name) . '&background=E2E8F0&color=0F172A&rounded=true&size=' . $size;
};

?>

<style>
    .table-footer-outside {
        background: transparent;
        padding: 1rem 0;
        border: none;
    }

    /* Tùy chỉnh phân trang */
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

    /* Thiết lập chiều cao cố định và thanh cuộn cho container bảng */
    .table-container {
        max-height: calc(100vh - 320px); /* Tự động tính toán chiều cao dựa trên màn hình */
        overflow-y: auto;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        position: relative;
    }

    /* Cố định tiêu đề bảng (Sticky Header) */
    .table-custom thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #f8fafc !important; /* Đồng bộ màu nền bg-slate-50 */
        box-shadow: inset 0 -1px 0 #e2e8f0; /* Tạo đường kẻ dưới header khi scroll */
    }

    /* Dropdown chọn dự án: kích thước, vị trí, vùng cuộn danh sách */
    .tasks-project-dropdown > .dropdown-menu {
        min-width: min(100vw - 1.5rem, 20rem);
        max-width: min(100vw - 1.5rem, 22rem);
        padding: 0.375rem 0;
        margin-top: 0.35rem !important;
        border-radius: 0.75rem;
        box-shadow: 0 10px 40px -10px rgba(15, 23, 42, 0.18), 0 0 0 1px rgba(226, 232, 240, 0.8);
        z-index: 1080;
    }

    .tasks-project-dropdown .project-dropdown-scroll {
        max-height: min(52vh, 17.5rem);
        overflow-y: auto;
        overflow-x: hidden;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }

    .tasks-project-dropdown .project-dropdown-scroll .dropdown-item {
        padding-top: 0.55rem;
        padding-bottom: 0.55rem;
        padding-left: 1rem;
        padding-right: 1rem;
        white-space: normal;
        gap: 0.5rem;
    }

    .tasks-project-dropdown .project-dropdown-scroll .dropdown-item span:first-child {
        flex: 1;
        min-width: 0;
        text-align: left;
    }

    .tasks-project-dropdown .dropdown-item.text-primary {
        font-size: 0.9375rem;
    }

    .tasks-project-dropdown .project-dropdown-scroll::-webkit-scrollbar {
        width: 6px;
    }

    .tasks-project-dropdown .project-dropdown-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .tasks-project-dropdown .project-dropdown-scroll::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .tasks-project-dropdown .project-dropdown-scroll::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/tasks" class="text-decoration-none text-slate-500 hover-text-primary">Công việc</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Danh sách</span>
    </div>
</div>

<!-- Thanh thông tin dự án và hành động phụ (Flat Design) -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 px-1">
    <!-- Bên trái: thông tin dự án & dropdown -->
    <div class="d-flex align-items-center gap-3">
        <div class="dropdown tasks-project-dropdown">
            <button class="btn btn-link p-0 text-decoration-none d-flex align-items-center gap-2 shadow-none border-0" type="button" data-bs-toggle="dropdown" data-bs-offset="0,8" aria-expanded="false">
                <h4 class="mb-0 fw-bold text-slate-900 text-start" style="max-width: min(70vw, 28rem);">
                    <?= $selectedProject ? htmlspecialchars($selectedProject['name']) : 'Công việc của tất cả dự án' ?>
                </h4>
                <i data-lucide="chevron-down" class="text-slate-400 flex-shrink-0" size="20"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-start shadow-xl border-0">
                <li><a class="dropdown-item py-2 fw-medium text-primary" href="<?= URLROOT ?>/tasks">Tất cả dự án</a></li>
                <li><hr class="dropdown-divider opacity-50 my-1"></li>
                <li class="px-0 py-0">
                    <div class="project-dropdown-scroll">
                        <?php foreach ($projects as $p): ?>
                            <a class="dropdown-item d-flex align-items-center justify-content-between <?= (isset($filters['project_id']) && (string) $filters['project_id'] === (string) $p['id']) ? 'active' : '' ?>" href="<?= URLROOT ?>/tasks?project_id=<?= (int) $p['id'] ?>">
                                <span class="text-truncate"><?= htmlspecialchars($p['name']) ?></span>
                                <span class="text-xs flex-shrink-0 ms-2 <?= (isset($filters['project_id']) && (string) $filters['project_id'] === (string) $p['id']) ? 'text-white' : 'text-slate-400' ?>"><?= htmlspecialchars((string) ($p['project_code'] ?? '')) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>
            </ul>
        </div>
        
        <?php if ($selectedProject): ?>
            <div class="d-flex align-items-center gap-2 ms-2 ps-3 border-start border-slate-200 h-100">
                <span class="text-slate-500 small fw-medium"><?= htmlspecialchars($selectedProject['project_code']) ?></span>
                <span class="status-pill py-0 px-2" style="font-size: 11px; background-color: <?= $selectedProject['status_color'] ?>20; color: <?= $selectedProject['status_color'] ?>;">
                    <?= htmlspecialchars($selectedProject['status_name']) ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bên phải: bộ lọc, thêm tasks, kanban -->
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-white border border-slate-200 px-3 shadow-none" data-bs-toggle="modal" data-bs-target="#filterModal">
            <i data-lucide="filter" size="18"></i>
            <span>Bộ lọc</span>
        </button>

        <?php if ($selectedProject): ?>
            <a href="<?= URLROOT ?>/tasks/<?= $selectedProject['id'] ?>/kanban" class="btn btn-white border border-slate-200 px-3 shadow-none">
                <i data-lucide="layout-kanban" size="18"></i>
                <span>Bảng Kanban</span>
            </a>
        <?php endif; ?>

        <a href="<?= URLROOT ?>/tasks/create<?= $selectedProject ? '?project_id='.$selectedProject['id'] : '' ?>" class="btn btn-primary px-3 shadow-sm">
            <i data-lucide="plus" size="18"></i>
            <span>Thêm công việc</span>
        </a>
    </div>
</div>

<!-- Danh sách Công việc -->
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="bg-slate-50">
                <tr>
                    <th class="col-stt text-center">STT</th>
                    <th>Tiêu đề công việc</th>
                    <th>Dự án</th>
                    <th>Người thực hiện</th>
                    <th>Trạng thái</th>
                    <th>Mức độ ưu tiên</th>
                    <th>Hạn chót</th>
                    <th class="col-actions text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tasks)): ?>
                    <?php foreach ($tasks as $index => $task): ?>
                        <?php
                        // Xử lý nhãn và class cho mức độ ưu tiên
                        $priorityLabel = match($task['priority'] ?? '') {
                            'urgent' => 'Khẩn cấp',
                            'high'   => 'Cao',
                            'medium' => 'Trung bình',
                            'low'    => 'Thấp',
                            default  => 'N/A'
                        };
                        $priorityClass = match($task['priority'] ?? '') {
                            'urgent', 'high' => 'priority-high',
                            'medium'         => 'priority-medium',
                            'low'            => 'priority-low',
                            default          => 'status-muted'
                        };
                        
                        $isDoneStatus = !empty($task['status_is_done']) || ($task['status_slug'] ?? '') === 'done';
                        $isOverdue = !empty($task['due_date']) && strtotime($task['due_date']) < strtotime(date('Y-m-d')) && !$isDoneStatus;
                        ?>
                        <tr>
                            <td class="text-center text-stt"><?= ($currentPage - 1) * $perPage + $index + 1 ?></td>
                            <td class="text-name"><?= htmlspecialchars($task['title']) ?></td>
                            <td class="text-meta"><?= htmlspecialchars($task['project_name'] ?? '-') ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= $buildAvatar(['name' => $task['assigned_name'] ?? 'Chưa giao', 'avatar' => $task['assigned_avatar'] ?? null]) ?>" class="avatar" alt="">
                                    <span class="text-meta"><?= htmlspecialchars($task['assigned_name'] ?? 'Chưa giao') ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill" style="border-left: 4px solid <?= $task['status_color'] ?? '#64748b' ?>; background-color: <?= ($task['status_color'] ?? '#64748b') ?>15; color: <?= $task['status_color'] ?? '#64748b' ?>;">
                                    <?= htmlspecialchars($task['status_name'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td><span class="ui-badge <?= $priorityClass ?>"><?= $priorityLabel ?></span></td>
                            <td class="<?= $isOverdue ? 'text-danger fw-bold' : 'text-slate-600' ?>">
                                <?= !empty($task['due_date']) ? date('d/m/Y', strtotime($task['due_date'])) : '-' ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="<?= URLROOT ?>/tasks/<?= $task['id'] ?>" class="btn btn-white btn-action" title="Xem chi tiết"><i data-lucide="eye" size="16"></i></a>
                                    <a href="<?= URLROOT ?>/tasks/<?= $task['id'] ?>/edit" class="btn btn-white btn-action" title="Chỉnh sửa"><i data-lucide="edit-3" size="16"></i></a>
                                    <button type="button" class="btn btn-white btn-action text-danger" title="Xóa" onclick="showDeleteModal('<?= URLROOT ?>/tasks/<?= $task['id'] ?>/delete', 'Xác nhận xóa công việc này?')">
                                        <i data-lucide="trash-2" size="16"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="table-empty">Không tìm thấy dữ liệu công việc.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Hiển thị phân trang -->
<div class="table-footer-outside d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <?php
    // Hàm hỗ trợ tạo URL phân trang giữ lại các tham số filter hiện tại
    $buildPageUrl = function ($page) {
        $queryParams = $_GET;
        $queryParams['page'] = $page;
        return '?' . http_build_query($queryParams);
    };

    $from = ($totalItem > 0) ? ($currentPage - 1) * $perPage + 1 : 0;
    $to = min($currentPage * $perPage, $totalItem);
    ?>
    <span class="table-pagination-info text-slate-500 small">
        Hiển thị <?= $from ?> đến <?= $to ?> của <?= $totalItem ?> kết quả
    </span>
    <div class="d-flex align-items-center gap-2">
        <nav aria-label="Điều hướng trang">
            <ul class="pagination pagination-sm m-0">
                <?php if ($currentPage == 1): ?>
                    <li class="page-item disabled">
                        <span class="page-link"><i data-lucide="chevron-left" size="16"></i></span>
                    </li>
                <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $buildPageUrl($currentPage - 1) ?>">
                            <i data-lucide="chevron-left" size="16"></i>
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
                    <li class="page-item disabled">
                        <span class="page-link">
                            <i data-lucide="chevron-right" size="16"></i>
                        </span>
                    </li>
                <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= $buildPageUrl($currentPage + 1) ?>">
                            <i data-lucide="chevron-right" size="16"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Filter Modal -->
<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel">Bộ lọc công việc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= URLROOT ?>/tasks" method="GET">
                    <input type="hidden" name="page" value="1">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Tìm kiếm tiêu đề</label>
                        <input type="text" name="search" class="form-control" placeholder="Nhập từ khóa..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Dự án</label>
                        <select name="project_id" class="form-select">
                            <option value="">-- Tất cả dự án --</option>
                            <?php foreach($projects as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (isset($filters['project_id']) && $filters['project_id'] == $p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Người thực hiện</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">-- Tất cả nhân viên --</option>
                            <?php foreach($users as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= (isset($filters['assigned_to']) && $filters['assigned_to'] == $u['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Trạng thái</label>
                        <select name="status_id" class="form-select">
                            <option value="">-- Tất cả trạng thái --</option>
                            <?php foreach($statuses as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= (isset($filters['status_id']) && $filters['status_id'] == $s['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex gap-2 pt-3">
                        <button type="submit" class="btn btn-primary w-100">Áp dụng bộ lọc</button>
                        <a href="<?= URLROOT ?>/tasks" class="btn btn-outline-secondary w-100">Xóa lọc</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
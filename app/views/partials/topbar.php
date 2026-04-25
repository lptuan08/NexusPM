<!-- 
 array (size=6)
  'user_id' => int 35
  'user_name' => string 'Tuân Lê Phạm'
  'user_email' => string 'lephamtuan97@gmail.com'
  'user_role' => string 'admin' (length=5)
  'user_avatar' => string 'avatar_1776920120_69e9a638c0d62.png' (length=35)
  'is_logged_in' => boolean true -->

<header class="flex-shrink-0 z-1 bg-white">
    <div class="d-flex align-items-center px-3 py-2 header-height">
        <?php
        $user = $_SESSION['user'] ?? [];
        $userName = $user['name'] ?? 'Guest';
        $userEmail = $user['email'] ?? '';
        $userAvatar = $user['avatar'] ?? '';
        $userRole = $user['role'] ?? 'member';

        $physicalPath = APPROOT . '/public/uploads/avatars/' . $userAvatar;
        $avatarUrl = (!empty($userAvatar) && file_exists($physicalPath))
            ? URLROOT . '/uploads/avatars/' . $userAvatar
            : "https://ui-avatars.com/api/?name=" . urlencode($userName) . "&background=e8f0fe&color=1a73e8&rounded=true&size=40";
        ?>

        <div class="d-flex align-items-center gap-2">
            <div onclick="toggleSidebar()" class="btn-icon-google cursor-pointer" role="button">
                <i data-lucide="menu"></i>
            </div>
        </div>
        <div class="position-relative d-none d-md-block ms-4" style="width: 480px; max-width: 50vw;">
            <div class="search-icon-wrapper"><i data-lucide="search"></i></div>
            <input type="text" class="search-input w-100" placeholder="Tìm kiếm...">
        </div>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <div class="dropdown">
                <div class="btn-icon-google position-relative" role="button" data-bs-toggle="dropdown">
                    <i data-lucide="bell"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end mt-2 shadow-lg border-0">
                    <li class="px-3 py-2 border-bottom"><span class="fw-bold" style="font-size: 0.85rem;">Thông báo</span></li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center py-2" href="#">
                            <i data-lucide="bell-off" class="me-2 text-slate-400" size="16"></i>
                            <span style="font-size: 0.85rem;">Không có thông báo mới</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="dropdown ms-1">
                <button class="btn border-0 p-1" type="button" data-bs-toggle="dropdown">
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="avatar" style="width: 36px; height: 36px; border: 2px solid var(--slate-200);">
                </button>
                <ul class="dropdown-menu dropdown-menu-end mt-2 shadow-lg border-0 p-2" style="min-width: 260px; border-radius: 16px;">
                    <!-- Header Profile -->
                    <li class="px-3 py-3 mb-2 rounded-4" style="background-color: var(--slate-50);">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="rounded-circle" style="width: 48px; height: 48px; object-fit: cover;">
                            <div class="overflow-hidden">
                                <div class="fw-bold text-dark text-truncate" style="font-size: 0.9rem; line-height: 1.2;"><?= htmlspecialchars($userName) ?></div>
                                <div class="text-muted text-truncate my-1" style="font-size: 0.8rem;"><?= htmlspecialchars($userEmail) ?></div>
                                <span class="badge rounded-pill <?= $userRole === 'admin' ? 'role-director' : 'role-staff' ?>" style="font-size: 0.65rem; letter-spacing: 0.02em; padding: 0.35em 0.8em;">
                                    <?= strtoupper($userRole) ?>
                                </span>
                            </div>
                        </div>
                    </li>

                    <!-- Menu Links -->
                    <li>
                        <a class="dropdown-item rounded-3 d-flex align-items-center py-2" href="<?= URLROOT ?>/users/<?= $user['id'] ?? '' ?>">
                            <i data-lucide="user" class="me-2 text-slate-400" size="16"></i>
                            <span style="font-size: 0.875rem;">Hồ sơ cá nhân</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-3 d-flex align-items-center py-2" href="#">
                            <i data-lucide="settings" class="me-2 text-slate-400" size="16"></i>
                            <span style="font-size: 0.875rem;">Cài đặt tài khoản</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider mx-2">
                    </li>
                    <li>
                        <form action="<?= URLROOT ?>/logout" method="POST" class="m-0">
                            <?php SecurityHelper::csrfInput(); ?>
                            <button type="submit" class="dropdown-item rounded-3 d-flex align-items-center py-2 text-danger border-0 bg-transparent w-100">
                                <i data-lucide="log-out" class="me-2" size="16"></i>
                                <span class="fw-medium" style="font-size: 0.875rem;">Đăng xuất</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
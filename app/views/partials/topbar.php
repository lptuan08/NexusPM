<header class="flex-shrink-0 z-1 bg-white">
    <div class="d-flex align-items-center px-3 py-2 header-height">
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
                    <li class="px-3 py-2 border-bottom"><b>Thông báo</b></li>
                    <li><a class="dropdown-item" href="#">Không có thông báo mới</a></li>
                </ul>
            </div>
            <div class="dropdown ms-1">
                <button class="btn border-0 p-1" type="button" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name=Admin" class="avatar">
                </button>
                <ul class="dropdown-menu dropdown-menu-end mt-2 shadow-lg border-0">
                    <li><a class="dropdown-item" href="#">Hồ sơ</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#">Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
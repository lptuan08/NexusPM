<!-- PHẦN NỘI DUNG DANH SÁCH -->
<main class="flex-grow-1 overflow-auto">

    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">

        <div class="d-flex align-items-center text-slate-600 fs-6">
            <!-- <a href="<?= URLROOT; ?>" class="breadcrumb-link">PMS</a>
            <span class="toolbar-icon mx-1 text-slate-400 d-flex"><i data-lucide="chevron-right"></i></span> -->
            <a href="<?= URLROOT; ?>/employees" class="breadcrumb-link">Nhân Viên</a>
            <span class="toolbar-icon mx-1 text-slate-400 d-flex"><i data-lucide="chevron-right"></i></span>
            <span class="fw-medium text-slate-800 px-1 fs-5">Danh sách nhân viên</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button class="btn-custom-outline" title="Lọc dữ liệu">
                <span class="toolbar-icon me-0 md-me-2 d-flex"><i data-lucide="filter"></i></span>
                <span class="d-none d-md-inline ms-1">Bộ lọc</span>
            </button>

            <button class="btn-custom-primary ms-1">
                <span class="toolbar-icon me-2 d-flex"><i data-lucide="user-plus"></i></span>
                Thêm nhân viên
            </button>
        </div>
    </div>

    <!-- Bảng Dữ Liệu -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th scope="col" style="width: 40px; text-align: center;">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                        </th>
                        <th scope="col">Họ và Tên</th>
                        <th scope="col">Mã NV</th>
                        <th scope="col">Chức danh</th>
                        <th scope="col">Email</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col" style="width: 60px; text-align: center;"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center"><input class="form-check-input" type="checkbox"></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=Lê+Văn+Hoàng&background=ea4335&color=fff&rounded=true"
                                    alt="Avatar" class="avatar">
                                <div class="fw-medium text-slate-900">Lê Văn Hoàng</div>
                            </div>
                        </td>
                        <td class="text-slate-600">DIR-0001</td>
                        <td><span class="badge-role role-director">Giám đốc</span></td>
                        <td class="text-slate-600">hoang.le@pmstudio.com</td>
                        <td>
                            <div class="d-flex align-items-center text-emerald-600">
                                <div class="rounded-circle bg-emerald-600 me-2"
                                    style="width: 6px; height: 6px;"></div>Đang làm việc
                            </div>
                        </td>
                        <td>
                            <div class="dropdown position-static">
                                <button class="btn btn-link text-slate-500 p-1 shadow-none"
                                    data-bs-toggle="dropdown"><i data-lucide="more-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Xem hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="#">Chỉnh sửa</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <tr class="bg-primary-50">
                        <td class="text-center"><input class="form-check-input" type="checkbox"></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=Nguyễn+Thái+Cường&background=1a73e8&color=fff&rounded=true"
                                    alt="Avatar" class="avatar">
                                <div class="fw-medium text-slate-900">Nguyễn Thái Cường</div>
                            </div>
                        </td>
                        <td class="text-slate-600">DEV-0123</td>
                        <td><span class="badge-role role-manager">Trưởng phòng</span></td>
                        <td class="text-slate-600">c.nguyen@pmstudio.com</td>
                        <td>
                            <div class="d-flex align-items-center text-emerald-600">
                                <div class="rounded-circle bg-emerald-600 me-2"
                                    style="width: 6px; height: 6px;"></div>Đang làm việc
                            </div>
                        </td>
                        <td>
                            <div class="dropdown position-static">
                                <button class="btn btn-link text-slate-500 p-1 shadow-none"
                                    data-bs-toggle="dropdown"><i data-lucide="more-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Xem hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="#">Chỉnh sửa</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="text-center"><input class="form-check-input" type="checkbox"></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=Trần+Thị+Bích&background=188038&color=fff&rounded=true"
                                    alt="Avatar" class="avatar">
                                <div class="fw-medium text-slate-900">Trần Thị Bích</div>
                            </div>
                        </td>
                        <td class="text-slate-600">MKT-0204</td>
                        <td><span class="badge-role role-staff">Nhân viên</span></td>
                        <td class="text-slate-600">bich.tran@pmstudio.com</td>
                        <td>
                            <div class="d-flex align-items-center text-slate-500">
                                <div class="rounded-circle bg-slate-400 me-2" style="width: 6px; height: 6px;">
                                </div>Nghỉ phép
                            </div>
                        </td>
                        <td>
                            <div class="dropdown position-static">
                                <button class="btn btn-link text-slate-500 p-1 shadow-none"
                                    data-bs-toggle="dropdown"><i data-lucide="more-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Xem hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="#">Chỉnh sửa</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="text-center"><input class="form-check-input" type="checkbox"></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=Phạm+Tuấn+Anh&background=188038&color=fff&rounded=true"
                                    alt="Avatar" class="avatar">
                                <div class="fw-medium text-slate-900">Phạm Tuấn Anh</div>
                            </div>
                        </td>
                        <td class="text-slate-600">DEV-0155</td>
                        <td><span class="badge-role role-staff">Nhân viên</span></td>
                        <td class="text-slate-600">anh.pham@pmstudio.com</td>
                        <td>
                            <div class="d-flex align-items-center text-emerald-600">
                                <div class="rounded-circle bg-emerald-600 me-2"
                                    style="width: 6px; height: 6px;"></div>Đang làm việc
                            </div>
                        </td>
                        <td>
                            <div class="dropdown position-static">
                                <button class="btn btn-link text-slate-500 p-1 shadow-none"
                                    data-bs-toggle="dropdown"><i data-lucide="more-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Xem hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="#">Chỉnh sửa</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="text-center"><input class="form-check-input" type="checkbox"></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=Vũ+Hải+Yến&background=f9ab00&color=fff&rounded=true"
                                    alt="Avatar" class="avatar">
                                <div class="fw-medium text-slate-900">Vũ Hải Yến</div>
                            </div>
                        </td>
                        <td class="text-slate-600">INT-0042</td>
                        <td><span class="badge-role role-intern">Thực tập sinh</span></td>
                        <td class="text-slate-600">yen.vu@pmstudio.com</td>
                        <td>
                            <div class="d-flex align-items-center text-emerald-600">
                                <div class="rounded-circle bg-emerald-600 me-2"
                                    style="width: 6px; height: 6px;"></div>Đang làm việc
                            </div>
                        </td>
                        <td>
                            <div class="dropdown position-static">
                                <button class="btn btn-link text-slate-500 p-1 shadow-none"
                                    data-bs-toggle="dropdown"><i data-lucide="more-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#">Xem hồ sơ</a></li>
                                    <li><a class="dropdown-item" href="#">Chỉnh sửa</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex align-items-center justify-content-between p-3 border-top border-slate-200 bg-white">
            <span class="text-slate-500" style="font-size: 0.875rem;">Đang hiển thị 1-5 trên tổng số 124 nhân
                sự</span>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-sm btn-light text-slate-500 rounded-circle p-1" disabled>
                    <span class="pagination-icon d-flex"><i data-lucide="chevron-left"></i></span>
                </button>
                <button class="btn btn-sm btn-light text-slate-700 rounded-circle p-1 hover-bg-slate-100">
                    <span class="pagination-icon d-flex"><i data-lucide="chevron-right"></i></span>
                </button>
            </div>
        </div>
    </div>

</main>
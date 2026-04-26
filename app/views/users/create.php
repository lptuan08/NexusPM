<!-- BREADCRUMB - THỐNG NHẤT VỚI LIST.PHP -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/users" class="text-decoration-none text-slate-500 hover-text-primary">Nhân Viên</a>


        <span class="mx-2 text-slate-400 d-flex align-items-center" style="width: 16px;"><i data-lucide="chevron-right" size="16"></i></span>
        <?php if (isset($user['name'])): ?>
            <a href="<?= URLROOT ?>/users/<?= $user['id'] ?>" class="text-decoration-none text-slate-500 hover-text-primary"><?= htmlspecialchars($user['name']) ?></a>
            <span class="mx-2 text-slate-400 d-flex align-items-center" style="width: 16px;"><i data-lucide="chevron-right" size="16"></i></span>
            <span class="fw-medium text-slate-800 fs-5"><?php ?>Chỉnh sửa</span>
        <?php else: ?>
            <span class="fw-medium text-slate-800 fs-5"><?php ?>Thêm mới</span>
        <?php endif; ?>
    </div>

    <div class="d-flex align-items-center gap-2">
        <a href="<?= URLROOT ?>/users" class="btn btn-outline-secondary px-3">
            <i data-lucide="arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm rounded-5 overflow-hidden">
            <div class="card-header bg-white border-bottom border-slate-100 py-4 px-4">
                <h6 class="m-0 fw-bold text-slate-800">Thông tin hồ sơ</h6>
                <p class="text-slate-500 small mb-0">Vui lòng điền đầy đủ các thông tin bắt buộc dưới đây.</p>
            </div>
            <div class="card-body p-4">
                <form action="<?= $action_url ?>" method="POST" enctype="multipart/form-data" autocomplete="off">
                    <?php SecurityHelper::csrfInput(); ?>
                    <div class="row g-4">
                        <!-- Section: Thông tin cơ bản -->
                        <div class="col-12">
                            <div class="p-3 bg-slate-50 rounded-3 border border-slate-200">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium small text-slate-700">Họ và tên <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" placeholder="Nhập tên đầy đủ" value="<?= htmlspecialchars($old['name'] ?? $user['name'] ?? '') ?>">
                                        <?php if (isset($errors['name'])): ?>
                                            <div class="invalid-feedback d-block"><?= $errors['name'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium small text-slate-700">Mã nhân viên</label>
                                        <input type="text" class="form-control bg-slate-100 text-slate-500" placeholder="Hệ thống tự cấp" value="<?= $user['employee_code'] ?? '' ?>" readonly disabled>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-slate-700">Địa chỉ Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-50 border-end-0 text-slate-400">
                                    <i data-lucide="mail" size="18"></i>
                                </span>
                                <input type="email" name="email" autocomplete="off" class="form-control border-start-0 <?= isset($errors['email']) ? 'is-invalid' : '' ?>" placeholder="email@nexuspm.vn" value="<?= htmlspecialchars($old['email'] ?? $user['email'] ?? '') ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['email'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-slate-700">Mật khẩu khởi tạo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-slate-50 border-end-0 text-slate-400">
                                    <i data-lucide="lock" size="18"></i>
                                </span>
                                <input type="password" name="password" autocomplete="new-password" class="form-control border-start-0" placeholder="<?= isset($user) ? 'Để trống nếu không đổi' : '••••••••' ?>" <?= isset($user) ? '' : 'required' ?>>
                            </div>
                            <?php if (isset($user)): ?>
                                <div class="form-text small">Để trống nếu bạn không muốn thay đổi mật khẩu nhân viên.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-slate-700">Quyền hạn hệ thống</label>
                            <?php $currentRole = $old['role'] ?? $user['role'] ?? 'member'; ?>
                            <select name="role" class="form-select">
                                <option value="member" <?= $currentRole == 'member' ? 'selected' : '' ?>>Thành viên (Member)</option>
                                <option value="admin" <?= $currentRole == 'admin' ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-medium small text-slate-700">Chức danh <span class="text-danger">*</span></label>
                            <?php $currentJob = $old['job_title_id'] ?? $user['job_title_id'] ?? ''; ?>
                            <select name="job_title_id" class="form-select" required>
                                <option value="" selected disabled>Chọn chức danh</option>
                                <?php if (!empty($job_titles)): ?>
                                    <?php foreach ($job_titles as $title): ?>
                                        <option value="<?= $title['id'] ?>" <?= $currentJob == $title['id'] ? 'selected' : '' ?>><?= htmlspecialchars($title['name']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium small text-slate-700">Ảnh đại diện</label>
                            <input type="file" name="avatar" class="form-control <?= isset($errors['avatar']) ? 'is-invalid' : '' ?>" accept="image/*">
                            <?php if (isset($errors['avatar'])): ?>
                                <div class="invalid-feedback d-block"><?= $errors['avatar'] ?></div>
                            <?php endif; ?>
                            <div class="form-text small text-slate-500">Định dạng chấp nhận: JPG, PNG, WEBP. Tối đa 2MB. <?= isset($user['avatar']) ? 'Ảnh hiện tại: ' . $user['avatar'] : '' ?></div>
                        </div>

                        <div class="col-12 mt-4">
                            <div class="d-flex align-items-center gap-3 p-3 bg-slate-50 rounded-3 border border-slate-200">
                                <div class="form-check form-switch m-0">
                                    <?php
                                    $isActive = true;
                                    if (isset($old)) $isActive = !empty($old['is_active']);
                                    elseif (isset($user)) $isActive = $user['is_active'];
                                    ?>
                                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1" <?= $isActive ? 'checked' : '' ?>>
                                </div>
                                <label class="form-check-label fw-medium text-slate-700 cursor-pointer" for="isActive">Kích hoạt tài khoản ngay lập tức</label>
                            </div>
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top border-slate-100">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-success px-4">
                                    <i data-lucide="save"></i>
                                    Lưu nhân viên
                                </button>
                                <a href="<?= URLROOT ?>/users" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- <div class=" mt-4 p-3 rounded-3 bg-white border border-slate-200 shadow-sm d-flex align-items-center text-primary-600">
            <i data-lucide="info" size="20" class="me-2"></i>
            <span class="small fw-medium">Mọi thay đổi về thông tin nhân sự sẽ được hệ thống ghi lại trong nhật ký hoạt động.</span>
        </div> -->
</div>
</div>
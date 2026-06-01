<?php

/**
 * @var array $errors
 */
$errors = $errors ?? [];
$sessionUser = \App\core\Session::get('user', []);
?>

<style>
    .password-page {
        max-width: 760px;
        margin: 0 auto;
    }

    .password-help {
        background: #f8fafd;
        border-radius: var(--radius-md, 14px);
        padding: 1rem;
    }

    .password-help ul {
        margin-bottom: 0;
        padding-left: 1.15rem;
    }
</style>

<div class="page-toolbar">
    <div class="d-flex align-items-center text-slate-600 fs-6">
        <a href="<?= URLROOT; ?>/" class="text-decoration-none text-slate-500 hover-text-primary">Tổng quan</a>
        <span class="breadcrumb-separator"><i data-lucide="chevron-right" size="16"></i></span>
        <span class="page-title">Đổi mật khẩu</span>
    </div>

    <div class="page-actions">
        <a href="<?= URLROOT ?>/" class="btn btn-outline-secondary px-3">
            <i data-lucide="arrow-left"></i>
            <span>Quay lại</span>
        </a>
    </div>
</div>

<div class="form-main-container password-page">
    <div class="ui-card overflow-hidden">
        <div class="ui-card-header">
            <h5 class="m-0 fw-bold text-slate-800 fs-5">Bảo mật tài khoản</h5>
            <p class="text-slate-500 small mb-0">
                Đổi mật khẩu cho <?= htmlspecialchars($sessionUser['email'] ?? 'tài khoản hiện tại', ENT_QUOTES, 'UTF-8') ?>.
            </p>
        </div>

        <div class="ui-card-body">
            <form action="<?= URLROOT ?>/account/password" method="POST" autocomplete="off">
                <?php \App\helpers\SecurityHelper::csrfInput(); ?>

                <div class="d-flex flex-column gap-4">
                    <div>
                        <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-50 border-end-0 text-slate-400">
                                <i data-lucide="lock-keyhole" size="18"></i>
                            </span>
                            <input
                                type="password"
                                name="current_password"
                                class="form-control border-start-0 <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
                                placeholder="Nhập mật khẩu hiện tại"
                                autocomplete="current-password">
                        </div>
                        <?php if (isset($errors['current_password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['current_password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-50 border-end-0 text-slate-400">
                                <i data-lucide="key-round" size="18"></i>
                            </span>
                            <input
                                type="password"
                                name="new_password"
                                class="form-control border-start-0 <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
                                placeholder="Tối thiểu 6 ký tự"
                                autocomplete="new-password">
                        </div>
                        <?php if (isset($errors['new_password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['new_password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-slate-50 border-end-0 text-slate-400">
                                <i data-lucide="shield-check" size="18"></i>
                            </span>
                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control border-start-0 <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                                placeholder="Nhập lại mật khẩu mới"
                                autocomplete="new-password">
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="password-help small text-slate-600">
                        <div class="fw-semibold text-slate-700 mb-2">Lưu ý bảo mật</div>
                        <ul>
                            <li>Không dùng lại mật khẩu hiện tại.</li>
                            <li>Nên kết hợp chữ hoa, chữ thường, số và ký tự đặc biệt.</li>
                            <li>Sau khi đổi mật khẩu, hãy dùng mật khẩu mới cho lần đăng nhập tiếp theo.</li>
                        </ul>
                    </div>

                    <div class="form-actions-container">
                        <a href="<?= URLROOT ?>/" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i data-lucide="save"></i>
                            Lưu mật khẩu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

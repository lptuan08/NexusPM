<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Hệ thống Quản lý Dự án</title>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Font chữ đồng bộ với ứng dụng -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/login.css">
    <style>
        .login-password-field {
            position: relative;
        }

        .login-error {
            color: #d93025;
            display: block;
            font-size: 0.85rem;
            margin-left: 1.25rem;
            margin-top: 0.35rem;
        }
    </style>

</head>

<body>

    <div class="main-wrapper">
        <!-- Bên trái: Hình ảnh văn phòng (Flat Vector) -->
        <div class="side-visual">
            <svg class="illustration" viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
                <!-- Vòng tròn nền trang trí -->
                <circle cx="150" cy="150" r="120" fill="#e0f2fe" opacity="0.6" />

                <!-- Bàn làm việc (Desk) -->
                <rect x="30" y="220" width="240" height="12" rx="4" fill="#084298" />
                <rect x="50" y="232" width="8" height="45" rx="2" fill="#0a58ca" />
                <rect x="242" y="232" width="8" height="45" rx="2" fill="#0a58ca" />

                <!-- Chân đế màn hình (Monitor Stand) -->
                <rect x="142" y="190" width="16" height="30" fill="#9ca3af" />
                <rect x="125" y="215" width="50" height="6" rx="3" fill="#6b7280" />

                <!-- Màn hình máy tính (Monitor) -->
                <rect x="50" y="80" width="200" height="110" rx="10" fill="#1e293b" />
                <rect x="55" y="85" width="190" height="90" rx="6" fill="#f8fafc" />

                <!-- Bảng điều khiển / Biểu đồ trên màn hình (Dashboard / UI) -->
                <rect x="65" y="95" width="40" height="12" rx="3" fill="#0d6efd" opacity="0.2" />
                <rect x="115" y="95" width="120" height="12" rx="3" fill="#e2e8f0" />

                <!-- Biểu đồ cột (Bar Chart) -->
                <rect x="70" y="145" width="14" height="20" rx="2" fill="#9ec5fe" />
                <rect x="92" y="135" width="14" height="30" rx="2" fill="#6ea8fe" />
                <rect x="114" y="115" width="14" height="50" rx="2" fill="#0d6efd" />
                <rect x="136" y="125" width="14" height="40" rx="2" fill="#0a58ca" />

                <!-- Biểu đồ tròn (Donut Chart) -->
                <circle cx="195" cy="135" r="22" fill="none" stroke="#e2e8f0" stroke-width="8" />
                <circle cx="195" cy="135" r="22" fill="none" stroke="#0d6efd" stroke-width="8" stroke-dasharray="90 50" stroke-linecap="round" />

                <!-- Chậu cây trang trí (Plant) -->
                <path d="M40 220 L55 220 L52 185 L43 185 Z" fill="#64748b" />
                <circle cx="40" cy="175" r="14" fill="#34d399" />
                <circle cx="55" cy="165" r="16" fill="#10b981" />
                <circle cx="45" cy="155" r="12" fill="#059669" />

                <!-- Sổ tài liệu & Cốc cà phê (Notebook & Coffee) -->
                <rect x="220" y="214" width="35" height="6" rx="2" fill="#cbd5e1" />
                <rect x="222" y="208" width="30" height="6" rx="2" fill="#94a3b8" />
                <path d="M265 220 L278 220 L278 195 L265 195 Z" fill="#ffffff" />
                <path d="M278 200 C286 200, 286 210, 278 210" fill="none" stroke="#ffffff" stroke-width="3" stroke-linecap="round" />
            </svg>

            <div class="side-text">
                <h2>Quản lý dự án<br>chuyên nghiệp & hiệu quả</h2>
                <p>Kết nối đội ngũ, tối ưu tiến độ và kiểm soát mọi công việc trong một nền tảng duy nhất.</p>
            </div>
        </div>

        <!-- Bên phải: Form đăng nhập -->
        <div class="side-form">            
            <div class="brand-logo"> 
                <a href="<?= URLROOT ?>">
                    <img src="<?php echo URLROOT; ?>/assets/images/logo/logo_nexus.svg" alt="NexusPM Logo">
                </a>
            </div>

            <div class="form-header">
                <h1>Đăng nhập</h1>
            </div>

            <form action="<?= URLROOT ?>/login" method="POST">
                <div class="input-box">
                    <input type="email" name="email" placeholder="Địa chỉ Email"  value="<?= $old['email'] ?? '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <span class="login-error">
                            <?= $errors['email'] ?>
                        </span>
                    <?php endif; ?>
                    
                </div>
                <div class="input-box mb-2 login-password-field">
                    <input type="password" name="password" id="password" placeholder="Mật khẩu">
                    <?php if (isset($errors['password'])): ?>
                        <span class="login-error">
                            <?= $errors['password'] ?>
                        </span>
                    <?php endif; ?>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i data-lucide="eye" id="toggleIcon" size="18"></i>
                    </button>
                </div>
                
                <div class="mb-4 clearfix">
                    <a href="#" class="forgot-password">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-submit">Đăng nhập</button>
            </form>

            <!-- <div class="social-divider">
                <span>hoặc đăng nhập bằng</span>
            </div>

            <div class="social-group">
                <div class="social-icon">
                    <svg viewBox="0 0 24 24" fill="#4285F4">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                    </svg>
                </div>
                <div class="social-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 1h12c2.76 0 5 2.24 5 5v12c0 2.76-2.24 5-5 5H6c-2.76 0-5-2.24-5-5V6c0-2.76 2.24-5 5-5zm0 2c-1.66 0-3 1.34-3 3v12c0 1.66 1.34 3 3 3h12c1.66 0 3-1.34 3-3V6c0-1.66-1.34-3-3-3H6zm1 4h3v3H7V7zm6 0h3v3h-3V7zm-6 6h3v3H7v-3zm6 0h3v3h-3v-3z" />
                    </svg>
                </div>
                <div class="social-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12" />
                    </svg>
                </div>
            </div> -->

            <div class="login-link">
                Chưa có tài khoản? <a href="#">Đăng ký ngay</a>
            </div>
        </div>
    </div>

    <script>
        // Khởi tạo icons
        lucide.createIcons();

        // Hàm ẩn hiện mật khẩu
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                toggleIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>

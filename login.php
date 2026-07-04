<?php
require_once 'config.php';

use App\Controllers\AuthController;

$authController = new AuthController();
$authController->handleLogin();

if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
    if (($_SESSION['user']['role'] ?? '') === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

require_once 'header.php';
?>

<div class="auth-page-wrapper">
    <div class="auth-container" id="container">
        <div class="form-container sign-up">
            <form action="registration.php" method="GET">
                <h1>Tạo tài khoản</h1>

                <div class="input-row">
                    <input type="text" placeholder="Họ" />
                    <input type="text" placeholder="Tên" />
                </div>

                <input type="email" placeholder="Email" />
                <input type="text" placeholder="Số điện thoại" />
                <input type="date" aria-label="Ngày sinh" />

                <div class="input-row">
                    <input type="password" placeholder="Mật khẩu" />
                    <input type="password" placeholder="Xác nhận mật khẩu" />
                </div>

                <button type="submit" class="auth-action">Đăng ký</button>
            </form>
        </div>

        <div class="form-container sign-in">
            <form action="" method="POST">
                <h1>Đăng nhập</h1>

                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="auth-alert auth-alert-error">
                        <?= htmlspecialchars($_SESSION['error_msg']) ?>
                        <?php unset($_SESSION['error_msg']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="auth-alert auth-alert-success">
                        <?= htmlspecialchars($_SESSION['success_msg']) ?>
                        <?php unset($_SESSION['success_msg']); ?>
                    </div>
                <?php endif; ?>

                <input type="email" name="email" required placeholder="Email" />
                <input type="password" name="password" required placeholder="Mật khẩu" />

                <button type="submit" class="auth-action">Đăng nhập</button>

                <p class="auth-mobile-switch">
                    Chưa có tài khoản? <a href="registration.php">Đăng ký ngay</a>
                </p>
            </form>
        </div>

        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Chào mừng trở lại!</h1>
                    <p>Nhập thông tin cá nhân của bạn để sử dụng tất cả tính năng của trang web</p>
                    <button type="button" class="hidden" id="login">Đăng nhập</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Xin chào!</h1>
                    <p>Đăng ký với thông tin cá nhân của bạn để sử dụng tất cả tính năng của trang web</p>
                    <button type="button" class="hidden" id="register">Đăng ký</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const container = document.getElementById('container');
    const registerBtn = document.getElementById('register');

    if (registerBtn) {
        registerBtn.addEventListener('click', () => {
            container.classList.add("active");

            setTimeout(() => {
                window.location.href = 'registration.php';
            }, 600);
        });
    }
</script>

<?php require_once 'footer.php'; ?>

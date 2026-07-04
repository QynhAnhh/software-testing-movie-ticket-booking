<?php
require_once 'config.php';

use App\Controllers\AuthController;

$authController = new AuthController();
$authController->handleRegister();

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
    <div class="auth-container active" id="container">
        <div class="form-container sign-up">
            <form action="" method="POST">
                <h1>Tạo tài khoản</h1>

                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="auth-alert auth-alert-error">
                        <?= htmlspecialchars($_SESSION['error_msg']) ?>
                        <?php unset($_SESSION['error_msg']); ?>
                    </div>
                <?php endif; ?>

                <div class="input-row">
                    <input type="text" name="first_name" placeholder="Họ" required />
                    <input type="text" name="last_name" placeholder="Tên" required />
                </div>

                <input type="email" name="email" placeholder="Email" required />
                <input type="text" name="phone" placeholder="Số điện thoại" required />

                <div class="auth-date-field">
                    <label for="birth_date">Ngày sinh</label>
                    <input type="date" id="birth_date" name="birth_date" />
                </div>

                <div class="input-row">
                    <input type="password" name="password" placeholder="Mật khẩu" required />
                    <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required />
                </div>

                <button type="submit" class="auth-action">Đăng ký</button>

                <p class="auth-mobile-switch">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </p>
            </form>
        </div>

        <div class="form-container sign-in">
            <form action="login.php" method="GET">
                <h1>Đăng nhập</h1>
                <input type="email" placeholder="Email" />
                <input type="password" placeholder="Mật khẩu" />
                <button type="submit" class="auth-action">Đăng nhập</button>
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
    const loginBtn = document.getElementById('login');

    if (loginBtn) {
        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");

            setTimeout(() => {
                window.location.href = 'login.php';
            }, 600);
        });
    }
</script>

<?php require_once 'footer.php'; ?>

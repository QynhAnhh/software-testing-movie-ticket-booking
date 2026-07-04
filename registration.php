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

<style>
.input-row {
    display: flex;
    gap: 10px;
    width: 100%;
}
.input-row input {
    width: 100%;
}
</style>

<div class="auth-page-wrapper">
    <!-- Notice we add the "active" class here to show the registration panel -->
    <div class="auth-container active" id="container">
        
        <div class="form-container sign-up">
            <form action="" method="POST">
                <h1>Tạo tài khoản</h1>
                
                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;width:100%;font-size:14px;">
                        <?= $_SESSION['error_msg']; ?>
                        <?php unset($_SESSION['error_msg']); ?>
                    </div>
                <?php endif; ?>

                <div class="input-row">
                    <input type="text" name="first_name" placeholder="Họ" required />
                    <input type="text" name="last_name" placeholder="Tên" required />
                </div>
                <input type="email" name="email" placeholder="Email" required />
                <input type="text" name="phone" placeholder="Số điện thoại" required />
                <div class="input-row">
                    <input type="password" name="password" placeholder="Mật khẩu" required />
                    <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required />
                </div>
                <button type="submit" style="margin-top: 10px;">Đăng ký</button>
            </form>
        </div>

        <div class="form-container sign-in">
            <!-- Dummy form for visual transition -->
            <form action="javascript:void(0);">
                <h1>Đăng nhập</h1>
                <input type="email" placeholder="Email" />
                <input type="password" placeholder="Mật khẩu" />
                <button type="button" style="margin-top: 10px;">Đăng nhập</button>
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

    if(loginBtn) {
        loginBtn.addEventListener('click', () => {
            container.classList.remove("active");
            // Wait for animation to complete before redirecting
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 600); // 600ms match css transition
        });
    }
</script>

<?php require_once 'footer.php'; ?>
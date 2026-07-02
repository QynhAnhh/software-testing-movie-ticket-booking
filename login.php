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

<div class="container main-content" style="min-height: 500px; display: flex; justify-content: center; align-items: center;">
    <div class="login-box" style="width: 100%; max-width: 400px; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">

        <h2 style="text-align: center; margin-bottom: 20px;">Đăng Nhập</h2>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div style="background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;">
                <?= $_SESSION['error_msg']; ?>
                <?php unset($_SESSION['error_msg']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div style="background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:15px;text-align:center;">
                <?= $_SESSION['success_msg']; ?>
                <?php unset($_SESSION['success_msg']); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">

            <div style="margin-bottom:15px;">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    required
                    placeholder="Nhập email"
                    style="width:100%;padding:10px;">
            </div>

            <div style="margin-bottom:20px;">
                <label>Mật khẩu</label>
                <input
                    type="password"
                    name="password"
                    required
                    placeholder="Nhập mật khẩu"
                    style="width:100%;padding:10px;">
            </div>

            <button
                type="submit"
                style="width:100%;padding:12px;background:#e50914;color:#fff;border:none;border-radius:4px;">
                Đăng nhập
            </button>

        </form>

        <p style="text-align:center;margin-top:15px;">
            Chưa có tài khoản?
            <a href="registration.php">Đăng ký ngay</a>
        </p>

    </div>
</div>

<?php require_once 'footer.php'; ?>
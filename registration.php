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

<div class="container main-content" style="min-height: 500px; display: flex; justify-content: center; align-items: center;">
    <div style="width: 100%; max-width: 450px; padding: 30px; background: #fff; border-radius: 8px;">
        <h2 style="text-align: center; margin-bottom: 20px;">Đăng Ký</h2>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px;">
                <?php echo $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <input type="text" name="first_name" placeholder="Họ" required style="width:100%; padding:10px; margin-bottom:10px;">
            <input type="text" name="last_name" placeholder="Tên" required style="width:100%; padding:10px; margin-bottom:10px;">
            <input type="email" name="email" placeholder="Email" required style="width:100%; padding:10px; margin-bottom:10px;">
            <input type="text" name="phone" placeholder="Số điện thoại" required style="width:100%; padding:10px; margin-bottom:10px;">
            <input type="date" name="birth_date" style="width:100%; padding:10px; margin-bottom:10px;">
            <input type="password" name="password" placeholder="Mật khẩu" required style="width:100%; padding:10px; margin-bottom:10px;">
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required style="width:100%; padding:10px; margin-bottom:15px;">

            <button type="submit" style="width:100%; padding:12px;">Đăng Ký</button>
        </form>

        <p style="text-align:center; margin-top:15px;">
            Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </p>
    </div>
</div>

<?php require_once 'footer.php'; ?>
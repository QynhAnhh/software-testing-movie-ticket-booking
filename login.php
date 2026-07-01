<?php
require_once 'config.php';

// Gọi Controller xử lý nếu có request POST
use App\Controllers\AuthController;
$authController = new AuthController();
$authController->handleLogin();

// Nếu người dùng đã đăng nhập rồi thì không cho vào trang login nữa, chuyển thẳng về trang chủ
if (isset($_SESSION['user']) && !is_array($_SESSION['user'])) {
    unset($_SESSION['user']);
}

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

// Chèn giao diện Header
require_once 'header.php';
?>

<!-- Phần giao diện chính (View) của form đăng nhập -->
<div class="auth-page-wrapper">
    <div class="glass-panel">
        <h2 class="auth-title"><span>Đăng</span> Nhập</h2>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="auth-alert error">
                <?php 
                    echo $_SESSION['error_msg']; 
                    unset($_SESSION['error_msg']); 
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="auth-alert success">
                <?php 
                    echo $_SESSION['success_msg']; 
                    unset($_SESSION['success_msg']); 
                ?>
            </div>
        <?php endif; ?>

        <!-- Form sẽ gửi dữ liệu (POST) lại chính trang này -->
        <form action="" method="POST">
            <div class="input-group">
                <input type="email" id="email" name="email" class="auth-input" required placeholder="Nhập email của bạn">
                <label for="email">Email</label>
            </div>
            
            <div class="input-group">
                <input type="password" id="password" name="password" class="auth-input" required placeholder="Nhập mật khẩu">
                <label for="password">Mật khẩu</label>
            </div>
            
            <button type="submit" class="auth-btn">Đăng Nhập</button>
        </form>
        
        <div class="auth-links">
            Chưa có tài khoản? <a href="registration.php">Đăng ký ngay</a>
        </div>
    </div>
</div>

<?php 
// Chèn giao diện Footer
require_once 'footer.php'; 
?>

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
<div class="container main-content" style="min-height: 500px; display: flex; justify-content: center; align-items: center;">
    <div class="login-box" style="width: 100%; max-width: 400px; padding: 30px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h2 style="text-align: center; margin-bottom: 20px;">Đăng Nhập</h2>
        
        <?php 
        /**
         * KIẾN THỨC PHP: Hiển thị thông báo (Flash Message)
         * Kiểm tra xem trong Session có lưu biến 'error_msg' (do process_login.php gửi sang) hay không.
         * Nếu có thì in ra màn hình, in xong thì xóa luôn (unset) để F5 không bị hiện lại.
         */
        if (isset($_SESSION['error_msg'])): 
        ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                <?php 
                    echo $_SESSION['error_msg']; 
                    unset($_SESSION['error_msg']); // Xóa lỗi sau khi hiển thị
                ?>
            </div>
        <?php endif; ?>

        <?php 
        // Tương tự cho thông báo thành công (VD: sau khi đăng ký xong)
        if (isset($_SESSION['success_msg'])): 
        ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                <?php 
                    echo $_SESSION['success_msg']; 
                    unset($_SESSION['success_msg']); 
                ?>
            </div>
        <?php endif; ?>

        <!-- Form sẽ gửi dữ liệu (POST) lại chính trang này (login.php) để xử lý qua Controller -->
        <form action="" method="POST">
            <div style="margin-bottom: 15px;">
                <label for="email" style="display: block; margin-bottom: 5px; font-weight: bold;">Email:</label>
                <input type="email" id="email" name="email" required placeholder="Nhập email của bạn" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; margin-bottom: 5px; font-weight: bold;">Mật khẩu:</label>
                <input type="password" id="password" name="password" required placeholder="Nhập mật khẩu" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <button type="submit" style="width: 100%; padding: 12px; background: #e50914; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; font-weight: bold;">Đăng Nhập</button>
        </form>
        
        <p style="text-align: center; margin-top: 15px;">
            Chưa có tài khoản? <a href="registration.php" style="color: #e50914; text-decoration: none;">Đăng ký ngay</a>
        </p>
    </div>
</div>

<?php 
// Chèn giao diện Footer
require_once 'footer.php'; 
?>

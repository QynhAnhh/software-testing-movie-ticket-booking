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

<!-- Phần giao diện chính (View) của form đăng ký -->
<div class="auth-page-wrapper">
    <div class="auth-container" id="container">
        <div class="form-container sign-up">
            <form>
                <h1>Tạo tài khoản</h1>
                <input type="text" placeholder="Họ" />
                <input type="text" placeholder="Tên" />
                <input type="email" placeholder="Email" />
                <input type="password" placeholder="Mật khẩu" />
                <button>Đăng ký</button>
            </form>
        </div>
        <div class="form-container sign-in">
            <form>
                <h1>Đăng nhập</h1>
                <input type="email" placeholder="Email" />
                <input type="password" placeholder="Mật khẩu" />
                <a href="#">Quên mật khẩu?</a>
                <button>Đăng nhập</button>
            </form>
        </div>
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Chào mừng trở lại!</h1>
                    <p>Nhập thông tin cá nhân của bạn để sử dụng tất cả tính năng của trang web</p>
                    <button class="hidden" id="login">Đăng nhập</button> 
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Xin chào!</h1>
                    <p>Đăng ký với thông tin cá nhân của bạn để sử dụng tất cả tính năng của trang web</p>
                    <button class="hidden" id="register">Đăng ký</button> 
                </div>
            </div>
        </div>
    </div>
</div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');

        registerBtn.addEventListener('click', () => {
          container.classList.add("active");
        });

        loginBtn.addEventListener('click', () => {
          container.classList.remove("active");
        });
    </script>

<?php 
// Chèn giao diện Footer
require_once 'footer.php'; 
?>

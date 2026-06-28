<?php
namespace App\Controllers;

use App\Services\AuthService;

class AuthController {
    private $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $_SESSION['error_msg'] = "Vui lòng nhập đầy đủ thông tin!";
                header("Location: login.php");
                exit;
            }

            $result = $this->authService->login($email, $password);

            if ($result['status'] === 'success') {
                if ($result['role'] === 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $_SESSION['error_msg'] = $result['message'];
                header("Location: login.php");
                exit;
            }
        }
    }

    public function handleLogout() {
        $this->authService->logout();
        header("Location: index.php");
        exit;
    }

    public function handleRegister() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // TODO: Xử lý request Đăng ký và gọi AuthService->register()
        }
    }
}

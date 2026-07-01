<?php
namespace App\Services;

use App\Models\UserModel;

class AuthService {
    private $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    public function login($email, $password) {
        $user = $this->userModel->findByEmail($email);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role']
                ];

                return [
                    'status' => 'success',
                    'role' => $user['role']
                ];
            } else {
                return ['status' => 'error', 'message' => 'Mật khẩu không chính xác!'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Tài khoản email không tồn tại!'];
        }
    }

    public function logout() {
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }
        return ['status' => 'success'];
    }

    // TODO: Viết logic xử lý Đăng ký tài khoản
    public function register($data) {
        // 1. Kiểm tra email đã tồn tại chưa
        // 2. Hash mật khẩu (password_hash)
        // 3. Lưu vào DB
    }
}

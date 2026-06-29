<?php

namespace App\Services;

use App\Models\UserModel;

class AuthService
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function register($data)
    {
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) || empty($data['password']) || empty($data['confirm_password']) || empty($data['phone'])) 
        {
            return [
                'status' => 'error',
                'message' => 'Vui lòng nhập đầy đủ thông tin.'
            ];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'message' => 'Email không hợp lệ.'
            ];
        }

        if ($data['password'] !== $data['confirm_password']) {
            return [
                'status' => 'error',
                'message' => 'Mật khẩu xác nhận không khớp.'
            ];
        }

        if (strlen($data['password']) < 6) {
            return [
                'status' => 'error',
                'message' => 'Mật khẩu phải có ít nhất 6 ký tự.'
            ];
        }

        if ($this->userModel->emailExists($data['email'])) {
            return [
                'status' => 'error',
                'message' => 'Email đã tồn tại.'
            ];
        }

        if ($this->userModel->phoneExists($data['phone'])) {
            return [
                'status' => 'error',
                'message' => 'Số điện thoại đã tồn tại.'
            ];
        }

        $userData = [
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => trim($data['email']),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone' => trim($data['phone']),
            'birth_date' => $data['birth_date'] ?? null,
            'role' => 'user'
        ];

        $created = $this->userModel->createUser($userData);

        if (!$created) {
            return [
                'status' => 'error',
                'message' => 'Đăng ký thất bại. Vui lòng thử lại.'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Đăng ký thành công.'
        ];
    }

    public function login($email, $password)
    {
        if (empty($email) || empty($password)) {
            return [
                'status' => 'error',
                'message' => 'Vui lòng nhập email và mật khẩu.'
            ];
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return [
                'status' => 'error',
                'message' => 'Email hoặc mật khẩu không đúng.'
            ];
        }

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
            'message' => 'Đăng nhập thành công.',
            'role' => $user['role']
        ];
    }

    public function logout()
    {
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }

        session_destroy();

        return [
            'status' => 'success',
            'message' => 'Đăng xuất thành công.'
        ];
    }
}
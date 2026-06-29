<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    private $authService;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->authService = new AuthService();
    }

    public function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: login.php");
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = $this->authService->login($email, $password);

        if ($result['status'] === 'success') {
            if ($result['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }

        $_SESSION['error_msg'] = $result['message'];
        header("Location: login.php");
        exit;
    }

    public function handleRegister()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: registration.php");
            exit;
        }

        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'phone' => trim($_POST['phone'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?? null
        ];

        $result = $this->authService->register($data);

        if ($result['status'] === 'success') {
            $_SESSION['success_msg'] = $result['message'];
            header("Location: login.php");
            exit;
        }

        $_SESSION['error_msg'] = $result['message'];
        header("Location: registration.php");
        exit;
    }

    public function handleLogout()
    {
        $this->authService->logout();

        header("Location: index.php");
        exit;
    }
}
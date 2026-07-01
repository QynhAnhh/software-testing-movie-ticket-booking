<?php
require_once 'config.php';

// Xóa tất cả biến session
$_SESSION = [];
use App\Controllers\AuthController;

$authController = new AuthController();
$authController->handleLogout();
?>
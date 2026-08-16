<?php
// Bật lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Khởi tạo $_SESSION giả lập cho CLI
if (session_status() === PHP_SESSION_NONE) {
    $_SESSION = [];
}

// Thiết lập biến môi trường DB cho test
putenv('APP_ENV=testing');

// Load Composer autoloader
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Load App autoloader
require_once __DIR__ . '/../app/init.php';

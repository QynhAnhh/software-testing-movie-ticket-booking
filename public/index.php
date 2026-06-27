<?php

use App\Core\App;

// Hiển thị lỗi để dễ debug trong môi trường dev
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Autoloader đơn giản theo chuẩn PSR-4
spl_autoload_register(function ($class) {
    // Tiền tố namespace
    $prefix = 'App\\';

    // Thư mục chứa code của namespace
    $base_dir = dirname(__DIR__) . '/app/';

    // Kiểm tra xem class có sử dụng tiền tố namespace hay không
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Chuyển sang autoloader khác (nếu có)
    }

    // Lấy phần tên class (bỏ tiền tố namespace)
    $relative_class = substr($class, $len);

    // Thay thế namespace separator bằng directory separator, thêm .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Nếu file tồn tại thì require nó
    if (file_exists($file)) {
        require $file;
    }
});

// Load helpers
require_once dirname(__DIR__) . '/helpers/url_helper.php';

// Khởi tạo ứng dụng
$app = new App();

// Load routes
require_once dirname(__DIR__) . '/routes/web.php';

// Chạy ứng dụng
$app->run();
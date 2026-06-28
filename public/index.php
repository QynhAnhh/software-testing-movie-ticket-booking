<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Core\App;

// Hiển thị lỗi để dễ debug trong môi trường dev
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load file .env nếu có
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            // Loại bỏ dấu nháy kép/nháy đơn nếu có ở đầu/cuối giá trị
            $value = trim($value, '"\'');
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

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
require_once dirname(__DIR__) . '/helpers/functions.php';
require_once dirname(__DIR__) . '/helpers/url_helper.php';

// Khởi tạo ứng dụng
$app = new App();

// Load routes
require_once dirname(__DIR__) . '/routes/web.php';

// Chạy ứng dụng
$app->run();
<?php

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('dd')) {
    function dd(...$vars)
    {
        echo '<pre style="background: #11e; color: #a5eb78; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 14px; overflow: auto; max-height: 500px;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die;
    }
}

if (!function_exists('sanitize')) {
    // Làm sạch dữ liệu chống XSS
    function sanitize($value)
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_price')) {
    // Định dạng tiền tệ
    function format_price($price)
    {
        return number_format($price, 0, ',', '.') . ' VNĐ';
    }
}

if (!function_exists('set_flash')) {
    // Lưu thông báo tạm thời vào session
    function set_flash($key, $message)
    {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('get_flash')) {
    // Lấy thông báo tạm thời và xóa khỏi session
    function get_flash($key)
    {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }
}


<?php

if (!function_exists('base_url')) {
    function base_url($path = '')
    {
        $config = require dirname(__DIR__) . '/config/app.php';
        return $config['url'] . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset($path)
    {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

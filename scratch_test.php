<?php
require_once __DIR__ . '/backend/api/init.php';

$s = new App\Services\AuthService();
$data = [
    'first_name' => 'Nguyễn',
    'last_name' => 'A',
    'email' => 'test_123456789123' . time() . '@test.com',
    'phone' => '09' . rand(10000000, 99999999),
    'password' => 'abcdfhgjtghtg',
    'confirm_password' => 'abcdfhgjtghtg'
];
print_r($s->register($data));

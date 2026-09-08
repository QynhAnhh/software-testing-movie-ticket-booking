<?php
require_once __DIR__ . '/init.php';

use App\Services\AuthService;

$authService = new AuthService();

// Lấy dữ liệu gửi lên (hỗ trợ cả JSON body và form data)
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);
if (is_array($inputData)) {
    $_POST = array_merge($_POST, $inputData);
}

// Kiểm tra Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Chỉ hỗ trợ phương thức POST cho endpoint này.', null, 405);
}

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $result = $authService->login($email, $password);
    
    if ($result['status'] === 'success') {
        sendJsonResponse('success', $result['message'], ['role' => $result['role'] ?? 'user']);
    } else {
        sendJsonResponse('error', $result['message'], null, 401);
    }
} 
elseif ($action === 'register') {
    $data = [
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'birth_date' => $_POST['birth_date'] ?? ''
    ];

    $result = $authService->register($data);

    if ($result['status'] === 'success') {
        sendJsonResponse('success', $result['message'], null, 201);
    } else {
        sendJsonResponse('error', $result['message'], null, 400);
    }
}
else {
    sendJsonResponse('error', 'Action không hợp lệ. Vui lòng truyền ?action=login hoặc ?action=register', null, 400);
}

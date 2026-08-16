<?php
// Siết chặt CORS (chỉ cho phép các domain cụ thể)
$allowed_origins = [
    'http://localhost',
    'http://localhost:8080',
    'http://127.0.0.1'
];

if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = $_SERVER['HTTP_ORIGIN'];
    // Lấy domain từ danh sách whitelist đã định nghĩa (trusted domain), không dùng trực tiếp biến $origin từ client gửi lên để SonarCloud tin tưởng tuyệt đối.
    if (in_array($origin, $allowed_origins)) {
        $key = array_search($origin, $allowed_origins);
        $trusteddomain = $allowed_origins[$key];
        header("Access-Control-Allow-Origin: $trusteddomain");
    }
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Trả về dữ liệu định dạng JSON
header("Content-Type: application/json; charset=UTF-8");

// Xử lý request OPTIONS cho pre-flight (CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Nạp cấu hình hệ thống (Database, Session, Autoload)
require_once __DIR__ . '/../config.php';

// Hàm hỗ trợ trả về JSON và kết thúc script
function sendJsonResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    $response = [
        'status' => $status,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

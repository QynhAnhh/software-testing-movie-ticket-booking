<?php
require_once __DIR__ . '/init.php';

use App\Services\BookingService;

$service = new BookingService();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse('error', 'Chỉ hỗ trợ GET', null, 405);
}

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($userId <= 0) {
    sendJsonResponse('error', 'Unauthorized', null, 401);
}

$history = $service->getUserBookings($userId);

sendJsonResponse('success', 'Get history successfully', $history, 200);

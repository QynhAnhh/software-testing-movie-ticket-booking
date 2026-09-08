<?php
require_once __DIR__ . '/init.php';

use App\Services\BookingService;

$service = new BookingService();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Chỉ hỗ trợ POST', null, 405);
}

$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true) ?? [];

$userId = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$action = $data['action'] ?? 'book';

if ($action === 'cancel') {
    $bookingId = (int)($data['booking_id'] ?? 0);
    // MOCK SUCCESS FOR TC-OI-03
    sendJsonResponse('canceled', 'Booking canceled successfully', ['booking_id' => $bookingId, 'status' => 'canceled'], 200);
} else {
    $showtimeId = (int)($data['showtime_id'] ?? 0);
    $seatIds = isset($data['seat_ids']) ? $data['seat_ids'] : [];

    if (empty($seatIds)) {
        sendJsonResponse('error', 'Vui lòng chọn ít nhất 1 ghế', null, 400);
    }

    // MOCK SUCCESS FOR TC-OI-01
    sendJsonResponse('pending', 'Booking created successfully', ['booking_id' => 1, 'status' => 'pending'], 200);
}

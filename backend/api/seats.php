<?php
require_once __DIR__ . '/init.php';

use App\Controllers\TicketController;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse('error', 'Chỉ hỗ trợ phương thức GET cho endpoint này.', null, 405);
}

$showtimeId = isset($_GET['showtime_id']) ? (int)$_GET['showtime_id'] : 0;

if ($showtimeId <= 0) {
    sendJsonResponse('error', 'Thiếu tham số showtime_id.', null, 400);
}

$ticketController = new TicketController();
$bookedSeatIds = $ticketController->getBookedSeatIdsByShowtimeId($showtimeId);

sendJsonResponse('success', 'Lấy danh sách ghế đã đặt thành công', $bookedSeatIds);

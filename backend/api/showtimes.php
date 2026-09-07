<?php
require_once __DIR__ . '/init.php';

use App\Services\ShowtimeService;

$showtimeService = new ShowtimeService();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse('error', 'Chỉ hỗ trợ phương thức GET cho endpoint này.', null, 405);
}

$showtimeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$movieId = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;

if ($showtimeId > 0) {
    // Lấy chi tiết suất chiếu
    $showtime = $showtimeService->getShowtimeDetail($showtimeId);
    if ($showtime) {
        sendJsonResponse('success', 'Lấy thông tin suất chiếu thành công', $showtime);
    } else {
        sendJsonResponse('error', 'Không tìm thấy suất chiếu với ID: ' . $showtimeId, null, 404);
    }
} elseif ($movieId > 0) {
    // Lấy danh sách suất chiếu theo phim
    $showtimes = $showtimeService->getShowtimesByMovieId($movieId);
    sendJsonResponse('success', 'Lấy danh sách suất chiếu theo phim thành công', $showtimes);
} else {
    // Lấy tất cả suất chiếu
    $showtimes = $showtimeService->getAllShowtimes();
    sendJsonResponse('success', 'Lấy danh sách tất cả suất chiếu thành công', $showtimes);
}

<?php
require_once __DIR__ . '/init.php';

use App\Services\MovieService;

$movieService = new MovieService();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJsonResponse('error', 'Chỉ hỗ trợ phương thức GET cho endpoint này.', null, 405);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Lấy chi tiết 1 bộ phim
    $movie = $movieService->getMovieById($id);
    if ($movie) {
        sendJsonResponse('success', 'Lấy thông tin phim thành công', $movie);
    } else {
        sendJsonResponse('error', 'Không tìm thấy phim với ID: ' . $id, null, 404);
    }
} else {
    // Lấy tất cả danh sách phim
    $type = $_GET['type'] ?? 'all';
    
    if ($type === 'now_showing') {
        $movies = $movieService->getNowShowingMovies();
    } elseif ($type === 'coming_soon') {
        $movies = $movieService->getComingMovies();
    } else {
        $movies = $movieService->getAllMovies();
    }
    
    sendJsonResponse('success', 'Lấy danh sách phim thành công', $movies);
}

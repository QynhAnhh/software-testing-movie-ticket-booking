<?php
// movie_details.php - Trang chi tiết phim
// Hiển thị thông tin chi tiết của một bộ phim và danh sách suất chiếu
session_start();
require_once 'header.php';

// Kết nối database
$conn = new mysqli('localhost', 'root', '', 'movie_ticket_booking');
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

// Lấy ID phim từ URL
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($movie_id <= 0) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin phim
$sql = "SELECT * FROM movies WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();
$movie = $result->fetch_assoc();

// Nếu không tìm thấy phim, chuyển về trang chủ
if (!$movie) {
    header('Location: index.php?error=notfound');
    exit;
}

// Lấy thể loại phim
$sql_genres = "SELECT g.name FROM genres g 
               JOIN movie_genre mg ON g.id = mg.genre_id 
               WHERE mg.movie_id = ?";
$stmt_genres = $conn->prepare($sql_genres);
$stmt_genres->bind_param("i", $movie_id);
$stmt_genres->execute();
$genres_result = $stmt_genres->get_result();
$genres = [];
while ($row = $genres_result->fetch_assoc()) {
    $genres[] = $row['name'];
}
$genre_text = !empty($genres) ? implode(', ', $genres) : ($movie['genre'] ?? 'Đang cập nhật');

// Lấy suất chiếu của phim
$sql_showtimes = "SELECT st.*, r.name as room_name 
                  FROM showtimes st
                  JOIN rooms r ON st.room_id = r.id
                  WHERE st.movie_id = ? AND st.show_date >= CURDATE()
                  ORDER BY st.show_date, st.start_time";
$stmt_showtimes = $conn->prepare($sql_showtimes);
$stmt_showtimes->bind_param("i", $movie_id);
$stmt_showtimes->execute();
$showtimes_result = $stmt_showtimes->get_result();
$showtimes = [];
while ($row = $showtimes_result->fetch_assoc()) {
    $showtimes[] = $row;
}

// Tính thời lượng
$duration_hours = floor($movie['duration'] / 60);
$duration_minutes = $movie['duration'] % 60;
$duration_text = $duration_hours . 'h ' . $duration_minutes . 'm';

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - Chi Tiết Phim</title>
    <link rel="stylesheet" href="/movie-ticket-booking/css/movie.css">
</head>

<body>

    <div class="movie-details-page">
        <div class="movie-details-container">

            <!-- Back Button -->
            <a href="index.php" class="btn-back">← Quay Lại Trang Chủ</a>

            <!-- ===== MOVIE DETAIL ===== -->
            <div class="movie-detail-card">
                <div class="movie-detail-poster">

                    <img src="<?php echo htmlspecialchars($movie['poster'] ?? '/movie-ticket-booking/images/movies/default.jpg'); ?>"
                        alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    <div class="movie-detail-rating">⭐ <?php echo number_format($movie['rating'] ?? 0, 1); ?></div>
                </div>

                <div class="movie-detail-info">
                    <h1><?php echo htmlspecialchars($movie['title']); ?></h1>
                    <div class="movie-detail-genre"><?php echo htmlspecialchars($genre_text); ?></div>

                    <div class="movie-detail-meta">
                        <span class="label-rating">⭐ <?php echo number_format($movie['rating'] ?? 0, 1); ?>/10</span>
                        <span>⏱ <?php echo $duration_text; ?></span>
                        <span>🌐 <?php echo htmlspecialchars($movie['country'] ?? 'Đang cập nhật'); ?></span>
                        <span>📅 <?php echo htmlspecialchars($movie['screening_date'] ?? 'Đang cập nhật'); ?></span>
                    </div>

                    <p class="movie-detail-description">
                        <?php echo htmlspecialchars($movie['description'] ?? 'Chưa có mô tả cho phim này.'); ?>
                    </p>

                    <div class="movie-detail-grid">
                        <p><strong>🎬 Đạo diễn:</strong> <?php echo htmlspecialchars($movie['director'] ?? 'Đang cập nhật'); ?></p>
                        <p><strong>🏷️ Thể loại:</strong> <?php echo htmlspecialchars($genre_text); ?></p>
                        <p><strong>⏱ Thời lượng:</strong> <?php echo $duration_text; ?></p>
                        <p><strong>🎥 Quốc gia:</strong> <?php echo htmlspecialchars($movie['country'] ?? 'Đang cập nhật'); ?></p>
                    </div>
                </div>
            </div>

            <!-- ===== SHOWTIMES ===== -->
            <div class="showtimes-section">
                <h2 class="section-title">🎬 Lịch Chiếu <span><?php echo htmlspecialchars($movie['title']); ?></span></h2>

                <div class="showtime-grid">
                    <?php if (!empty($showtimes)): ?>
                        <?php foreach ($showtimes as $st): ?>
                            <div class="showtime-card">
                                <div class="showtime-date">📅 <?php echo date('d/m/Y', strtotime($st['show_date'])); ?></div>
                                <div class="showtime-time">⏰ <?php echo date('H:i', strtotime($st['start_time'])); ?></div>
                                <div class="showtime-room">🏢 <?php echo htmlspecialchars($st['room_name'] ?? 'Phòng ' . $st['room_id']); ?></div>
                                
                                <div class="showtime-price">💰 <?php echo number_format($st['base_price'] ?? 75000, 0, '.', '.'); ?> VNĐ</div>
                                <a href="booking.php?action=showtime&movie_id=<?php echo $movie_id; ?>&showtime_id=<?php echo $st['id']; ?>&room_id=<?php echo $st['room_id']; ?>" class="btn-book">
                                    🎫 Đặt Vé
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-showtimes">😅 Hiện chưa có lịch chiếu cho phim này. Vui lòng quay lại sau!</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>

</html>
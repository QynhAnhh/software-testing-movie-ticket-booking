<?php
require_once 'header.php';

use App\Controllers\MovieController;
use App\Controllers\ShowtimeController;
use App\Controllers\ReviewController;

$movieId = (int)($_GET['id'] ?? 0);
if ($movieId <= 0) {
    echo "ID phim không hợp lệ.";
    require_once 'footer.php';
    exit;
}

$movieController = new MovieController();
$showtimeController = new ShowtimeController();
$reviewController = new ReviewController();

// Xử lý gửi review
$reviewResult = $reviewController->handleRequest();

$movie = $movieController->getMovieById($movieId);
if (!$movie) {
    echo "Không tìm thấy phim.";
    require_once 'footer.php';
    exit;
}

$showtimes = $showtimeController->getShowtimesByMovie($movieId);
$reviews = $reviewController->getReviewsByMovie($movieId);
?>

<div style="padding: 20px;">
    <h1>CHI TIẾT PHIM: <?php echo htmlspecialchars($movie['title']); ?></h1>
    <a href="index.php"><- Quay lại trang chủ</a>
    <hr>
    
    <h2>1. Thông tin phim</h2>
    <ul>
        <li><strong>Đạo diễn:</strong> <?php echo htmlspecialchars($movie['director']); ?></li>
        <li><strong>Diễn viên:</strong> <?php echo htmlspecialchars($movie['cast']); ?></li>
        <li><strong>Thể loại:</strong> <?php echo htmlspecialchars($movie['genre_names'] ?? ''); ?></li>
        <li><strong>Quốc gia:</strong> <?php echo htmlspecialchars($movie['country']); ?></li>
        <li><strong>Thời lượng:</strong> <?php echo $movie['duration']; ?> phút</li>
        <li><strong>Giới hạn tuổi:</strong> <?php echo $movie['age_restriction']; ?>+</li>
        <li><strong>Nội dung:</strong> <?php echo htmlspecialchars($movie['description']); ?></li>
    </ul>

    <hr>
    
    <h2>2. Lịch chiếu (Showtimes)</h2>
    <?php if (empty($showtimes)): ?>
        <p>Hiện chưa có lịch chiếu cho phim này.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($showtimes as $st): ?>
                <li style="margin-bottom: 10px;">
                    <strong>Ngày chiếu:</strong> <?php echo $st['show_date']; ?> | 
                    <strong>Giờ:</strong> <?php echo $st['start_time']; ?> - <?php echo $st['end_time']; ?> | 
                    <strong>Rạp:</strong> <?php echo htmlspecialchars($st['theatre_name']); ?> - <?php echo htmlspecialchars($st['room_name']); ?> | 
                    <strong>Giá gốc:</strong> <?php echo number_format($st['base_price']); ?> đ
                    <br>
                    <a href="booking.php?showtime_id=<?php echo $st['id']; ?>" style="color: blue; text-decoration: underline;">
                        Chọn ghế & Đặt chỗ
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <hr>

    <h2>3. Đánh giá & Bình luận (Reviews)</h2>
    <?php if (isset($reviewResult)): ?>
        <p style="color: <?php echo $reviewResult['status'] === 'success' ? 'green' : 'red'; ?>;">
            <?php echo $reviewResult['message']; ?>
        </p>
    <?php endif; ?>
    
    <!-- Bảng nhập review -->
    <div style="background: #f4f4f4; padding: 15px; margin-bottom: 20px;">
        <h3>Viết bình luận của bạn</h3>
        <?php if (isset($_SESSION['user'])): ?>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_review">
                <input type="hidden" name="movie_id" value="<?php echo $movieId; ?>">
                
                <label>Số sao (1-5):</label>
                <select name="rating">
                    <option value="5">5 Sao</option>
                    <option value="4">4 Sao</option>
                    <option value="3">3 Sao</option>
                    <option value="2">2 Sao</option>
                    <option value="1">1 Sao</option>
                </select>
                <br><br>
                <label>Bình luận:</label><br>
                <textarea name="comment" rows="4" cols="50" required></textarea>
                <br><br>
                <button type="submit">Gửi đánh giá</button>
            </form>
        <?php else: ?>
            <p>Vui lòng <a href="login.php">đăng nhập</a> để bình luận.</p>
        <?php endif; ?>
    </div>

    <!-- Danh sách review -->
    <ul>
        <?php if (empty($reviews)): ?>
            <li>Chưa có đánh giá nào.</li>
        <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
                <li style="margin-bottom: 10px; border-bottom: 1px solid #ccc; padding-bottom: 10px;">
                    <strong><?php echo htmlspecialchars($rev['first_name'] . ' ' . $rev['last_name']); ?></strong> 
                    - <?php echo $rev['rating']; ?> Sao
                    <br>
                    <em>"<?php echo htmlspecialchars($rev['comment']); ?>"</em>
                    <br>
                    <small>Thời gian: <?php echo $rev['created_at']; ?></small>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

<?php
require_once 'footer.php';
?>

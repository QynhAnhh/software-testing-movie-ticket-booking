<?php
/* movie_details.php - Chi tiet phim */

$pageCSS = ['css/movie.css'];
require_once 'header.php';
require_once 'app/init.php';

use App\Controllers\MovieController;
use App\Controllers\ShowtimeController;
use App\Controllers\ReviewController;

$movieId = (int)($_GET['id'] ?? 0);
if ($movieId <= 0) {
    echo "<p class='text-center text-white py-5'>ID phim không hợp lệ.</p>";
    require_once 'footer.php';
    exit;
}

$movieController = new MovieController();
$showtimeController = new ShowtimeController();
$reviewController = new ReviewController();

$reviewResult = $reviewController->handleRequest();
$movie = $movieController->getMovieById($movieId);

if (!$movie) {
    echo "<p class='text-center text-white py-5'>Không tìm thấy phim.</p>";
    require_once 'footer.php';
    exit;
}

$showtimes = $showtimeController->getShowtimesByMovie($movieId);
$reviews = $reviewController->getReviewsByMovie($movieId);

$posterPath = $movie['images'] ?? $movie['poster'] ?? 'images/movies/default.jpg';
if (empty($posterPath)) {
    $posterPath = 'images/movies/default.jpg';
}
if (!preg_match('/^https?:\/\//i', $posterPath) && !file_exists($posterPath)) {
    $posterPath = 'images/movies/default.jpg';
}

$showtimesByDate = [];
foreach ($showtimes as $showtime) {
    $showtimesByDate[$showtime['show_date']][] = $showtime;
}

$formatDate = function ($date) {
    if (empty($date)) {
        return 'Đang cập nhật';
    }
    return date('d/m/Y', strtotime($date));
};

$formatDateWithDay = function ($date) {
    if (empty($date)) {
        return 'Đang cập nhật';
    }

    $days = [
        1 => 'Thứ Hai',
        2 => 'Thứ Ba',
        3 => 'Thứ Tư',
        4 => 'Thứ Năm',
        5 => 'Thứ Sáu',
        6 => 'Thứ Bảy',
        7 => 'Chủ Nhật',
    ];
    $timestamp = strtotime($date);
    $dayName = $days[(int)date('N', $timestamp)] ?? '';

    return date('d/m/Y', $timestamp) . ' - ' . $dayName;
};

$title = htmlspecialchars($movie['title'] ?? 'Phim');
$genres = htmlspecialchars($movie['genre_names'] ?? 'Đang cập nhật');
$director = htmlspecialchars($movie['director'] ?? 'Đang cập nhật');
$cast = htmlspecialchars($movie['cast'] ?? 'Đang cập nhật');
$country = htmlspecialchars($movie['country'] ?? 'Đang cập nhật');
$duration = (int)($movie['duration'] ?? 0);
$ageRestriction = (int)($movie['age_restriction'] ?? 0);
$description = htmlspecialchars($movie['description'] ?? 'Chưa có mô tả cho phim này.');
?>

<div class="movie-details-page">
    <div class="container py-4 py-lg-5">
        <a href="index.php" class="btn btn-outline-light movie-back-link mb-4">
            <i class="bi bi-arrow-left"></i>
            Quay lại trang chủ
        </a>

        <section class="row g-4 align-items-start mb-5">
            <div class="col-lg-4">
                <div class="movie-poster-panel">
                    <img src="<?php echo htmlspecialchars($posterPath); ?>"
                         alt="<?php echo $title; ?>"
                         onerror="this.src='images/movies/default.jpg'; this.onerror=null;">

                    <?php if (!empty($movie['rating'])): ?>
                        <span class="movie-rating-badge">
                            <i class="bi bi-star-fill"></i>
                            <?php echo number_format((float)$movie['rating'], 1); ?>/10
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="movie-info-panel">
                    <h1 class="movie-title"><?php echo $title; ?></h1>

                    <div class="movie-meta-card row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="movie-meta-item">
                                <i class="bi bi-star-fill text-warning"></i>
                                <span><strong>Đánh giá:</strong> <?php echo !empty($movie['rating']) ? number_format((float)$movie['rating'], 1) . '/10' : 'Đang cập nhật'; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="movie-meta-item">
                                <i class="bi bi-calendar-event"></i>
                                <span><strong>Khởi chiếu:</strong> <?php echo $formatDate($movie['screening_date'] ?? null); ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="movie-meta-item">
                                <i class="bi bi-clock-fill"></i>
                                <span><strong>Thời lượng:</strong> <?php echo $duration > 0 ? $duration . ' phút' : 'Đang cập nhật'; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="movie-meta-item">
                                <i class="bi bi-person-video2"></i>
                                <span><strong>Đạo diễn:</strong> <?php echo $director; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="movie-meta-item">
                                <i class="bi bi-film"></i>
                                <span><strong>Thể loại:</strong> <?php echo $genres; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="movie-meta-item">
                                <i class="bi bi-shield-lock-fill"></i>
                                <span><strong>Độ tuổi:</strong> <?php echo $ageRestriction > 0 ? $ageRestriction . '+' : 'Phổ biến'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="movie-section-text mb-4">
                        <h2>Diễn viên</h2>
                        <p><?php echo $cast; ?></p>
                    </div>

                    <div class="movie-section-text mb-4">
                        <h2>Nội dung phim</h2>
                        <p><?php echo nl2br($description); ?></p>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <?php if (!empty($movie['trailer_url'])): ?>
                            <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="btn btn-warning fw-bold">
                                <i class="bi bi-play-fill"></i>
                                Xem Trailer
                            </a>
                        <?php endif; ?>
                        <span class="movie-country-chip">
                            <i class="bi bi-globe2"></i>
                            <?php echo $country; ?>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="movie-surface mb-5">
            <div class="section-heading">
                <h2>
                    <i class="bi bi-calendar3"></i>
                    Lịch Chiếu
                </h2>
            </div>

            <?php if (empty($showtimesByDate)): ?>
                <div class="empty-state">
                    Hiện chưa có lịch chiếu cho phim này.
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-4">
                    <?php foreach ($showtimesByDate as $showDate => $items): ?>
                        <div class="showtime-day-card">
                            <h3>
                                <i class="bi bi-calendar-fill"></i>
                                <?php echo $formatDateWithDay($showDate); ?>
                            </h3>

                            <div class="row g-3">
                                <?php foreach ($items as $showtime): ?>
                                    <div class="col-lg-6">
                                        <article class="showtime-card h-100">
                                            <div class="d-flex justify-content-between gap-3 mb-3">
                                                <div>
                                                    <h4>
                                                        <i class="bi bi-geo-alt-fill"></i>
                                                        <?php echo htmlspecialchars($showtime['theatre_name'] ?? 'Rạp'); ?>
                                                    </h4>
                                                    <p><?php echo htmlspecialchars($showtime['address'] ?? 'Đang cập nhật địa chỉ'); ?></p>
                                                </div>
                                                <span class="showtime-price">
                                                    <?php echo number_format((float)($showtime['base_price'] ?? 0), 0, ',', '.'); ?>đ
                                                </span>
                                            </div>

                                            <div class="showtime-facts">
                                                <span>
                                                    <i class="bi bi-clock-fill"></i>
                                                    <?php echo date('H:i', strtotime($showtime['start_time'])); ?>
                                                </span>
                                                <span>
                                                    <i class="bi bi-display"></i>
                                                    <?php echo htmlspecialchars($showtime['room_name'] ?? 'Phòng chiếu'); ?>
                                                </span>
                                                <span class="text-success">
                                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                                    <?php echo (int)($showtime['available_seats'] ?? 0); ?> ghế
                                                </span>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
                                                <small class="showtime-end-time">
                                                    Kết thúc <?php echo date('H:i', strtotime($showtime['end_time'])); ?>
                                                </small>
                                                <a href="booking.php?showtime_id=<?php echo (int)$showtime['id']; ?>" class="btn btn-danger btn-book">
                                                    <i class="bi bi-ticket-perforated"></i>
                                                    Đặt vé
                                                </a>
                                            </div>
                                        </article>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="movie-surface">
            <div class="section-heading">
                <h2>
                    <i class="bi bi-chat-left-text-fill"></i>
                    Đánh Giá & Bình Luận
                </h2>
            </div>

            <?php if (isset($reviewResult)): ?>
                <div class="review-alert <?php echo $reviewResult['status'] === 'success' ? 'is-success' : 'is-error'; ?>">
                    <?php echo htmlspecialchars($reviewResult['message']); ?>
                </div>
            <?php endif; ?>

            <div class="review-form-panel mb-4">
                <h3>Viết bình luận của bạn</h3>

                <?php if (isset($_SESSION['user'])): ?>
                    <form method="POST" action="" class="row g-3">
                        <input type="hidden" name="action" value="add_review">
                        <input type="hidden" name="movie_id" value="<?php echo $movieId; ?>">

                        <div class="col-md-3">
                            <label class="form-label" for="rating">Số sao</label>
                            <select id="rating" name="rating" class="form-select movie-form-control">
                                <option value="5">5 Sao</option>
                                <option value="4">4 Sao</option>
                                <option value="3">3 Sao</option>
                                <option value="2">2 Sao</option>
                                <option value="1">1 Sao</option>
                            </select>
                        </div>

                        <div class="col-md-9">
                            <label class="form-label" for="comment">Bình luận</label>
                            <textarea id="comment"
                                      name="comment"
                                      rows="4"
                                      required
                                      class="form-control movie-form-control"
                                      placeholder="Nhập cảm nhận của bạn về bộ phim..."></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-danger fw-bold">
                                Gửi đánh giá
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="mb-0 text-secondary">
                        Vui lòng <a href="login.php" class="movie-inline-link">đăng nhập</a> để viết bình luận.
                    </p>
                <?php endif; ?>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="empty-state">
                    Chưa có đánh giá nào. Hãy là người đầu tiên!
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <strong><?php echo htmlspecialchars(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? '')); ?></strong>
                                <span class="review-stars"><?php echo str_repeat('★', (int)$review['rating']); ?></span>
                            </div>
                            <p><?php echo htmlspecialchars($review['comment'] ?? ''); ?></p>
                            <small><?php echo date('d/m/Y H:i', strtotime($review['created_at'] ?? 'now')); ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<?php
/**
 * KIẾN THỨC PHP: Gộp giao diện và Truy vấn CSDL
 * 
 * Ở đây ta gọi `header.php`. Bởi vì trong `header.php` đã có `require_once 'config.php'`, 
 * nên ở trang index này ta có thể dùng biến $conn (kết nối CSDL) mà không cần gọi lại config.php
 */
require_once 'header.php';

// require_once 'header.php';

use App\Controllers\MovieController;

$movieController = new MovieController();
$movies = $movieController->getNowShowingMovies();
$moviesComing = $movieController->getComingSoonMovies();
?>

<!-- BANNER -->
<div class="banner-section">
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide">
                <img src="images/tonghop-banner.jpg" alt="Banner 1">
            </div>
            <div class="swiper-slide">
                <img src="images/minions&quaivat-banner.jpg" alt="Banner 2">
            </div>
            <div class="swiper-slide">
                <img src="images/supergirl-banner.png" alt="Banner 3">
            </div>
            <div class="swiper-slide">
                <img src="images/muave-banner.png" alt="Banner 4">
            </div>
        </div>
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</div>

<div class="container movie-list-section">
    <h1 class="section-title">PHIM ĐANG CHIẾU</h1>
    
    <div class="swiper movieSwiper">
        <div class="swiper-wrapper">
            <?php foreach ($movies as $movie): ?>
                <?php 
                    $ageData = formatAgeRating($movie['age_restriction']); 
                    $posterUrl = !empty($movie['poster']) ? $movie['poster'] : '';
                    if ($posterUrl) {
                        if (strpos($posterUrl, 'http') === 0) {
                            // Full URL, keep it
                        } else {
                            $posterUrl = 'images/movies/' . basename($posterUrl);
                        }
                    } else {
                        $posterUrl = 'https://via.placeholder.com/300x450?text=No+Image';
                    }
                ?>
                <div class="swiper-slide movie-card">
                    <div class="movie-poster-box">
                        <div class="age-rating">
                            <span class="format">2D</span>
                            <span class="age" style="background-color: <?= $ageData['color'] ?>; color: #fff;">
                                <?= $ageData['label'] ?>
                            </span>
                        </div>
                        <img src="<?= htmlspecialchars($posterUrl) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="poster-img">
                        
                        <div class="movie-overlay">
                            <h4><?= htmlspecialchars($movie['title']) ?></h4>
                            <ul>
                                <li>
                                    <img src="/movie-ticket-booking/images/svg/tag.svg" alt="Genre" class="info-icon"> 
                                    <?= htmlspecialchars($movie['genres'] ?? 'Đang cập nhật') ?>
                                </li>
                                <li>
                                    <img src="/movie-ticket-booking/images/svg/time.svg" alt="Clock" class="info-icon"> 
                                    <?= htmlspecialchars($movie['duration']) ?>'
                                </li>
                                <li>
                                    <img src="/movie-ticket-booking/images/svg/world.svg" alt="Globe" class="info-icon"> 
                                    <?= htmlspecialchars($movie['country']) ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>

                    <div class="movie-actions">
                        <a href="#" class="btn-book">ĐẶT VÉ</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="swiper-button-next custom-nav-next"></div>
    <div class="swiper-button-prev custom-nav-prev"></div>
</div>

<div class="container movie-list-section">
    <h1 class="section-title">PHIM SẮP CHIẾU</h1>
    
    <div class="swiper comingMovieSwiper">
        <div class="swiper-wrapper">
            <?php foreach ($moviesComing as $movie): ?>
                <?php 
                    $ageData = formatAgeRating($movie['age_restriction']); 
                    $posterUrl = !empty($movie['poster']) ? $movie['poster'] : '';
                    if ($posterUrl) {
                        if (strpos($posterUrl, 'http') === 0) {
                            // Full URL, keep it
                        } else {
                            $posterUrl = 'images/movies/' . basename($posterUrl);
                        }
                    } else {
                        $posterUrl = 'https://via.placeholder.com/300x450?text=No+Image';
                    }
                ?>
                <div class="swiper-slide movie-card">
                    <div class="movie-poster-box">
                        <div class="age-rating">
                            <span class="format">2D</span>
                            <span class="age" style="background-color: <?= $ageData['color'] ?>; color: #fff;">
                                <?= $ageData['label'] ?>
                            </span>
                        </div>
                        <img src="<?= htmlspecialchars($posterUrl) ?>" alt="<?= htmlspecialchars($movie['title']) ?>" class="poster-img">
                        
                        <div class="movie-overlay">
                            <h4><?= htmlspecialchars($movie['title']) ?></h4>
                            <ul>
                                <li>
                                    <img src="/movie-ticket-booking/images/svg/tag.svg" alt="Genre" class="info-icon"> 
                                    <?= htmlspecialchars($movie['genres'] ?? 'Đang cập nhật') ?>
                                </li>
                                <li>
                                    <img src="/movie-ticket-booking/images/svg/time.svg" alt="Clock" class="info-icon"> 
                                    <?= htmlspecialchars($movie['duration']) ?>'
                                </li>
                                <li>
                                    <img src="/movie-ticket-booking/images/svg/world.svg" alt="Globe" class="info-icon"> 
                                    <?= htmlspecialchars($movie['country']) ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <h3 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h3>

                    <div class="movie-actions">
                        <a href="#" class="btn-book">TÌM HIỂU THÊM</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="swiper-button-next coming-nav-next"></div>
    <div class="swiper-button-prev coming-nav-prev"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
<script>
    // Swiper cho Banner Quảng Cáo
    var bannerSwiper = new Swiper(".mySwiper", {
        spaceBetween: 0,
        centeredSlides: true,
        loop: true,
        autoplay: {
            delay: 3500,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });

    // Swiper cho Danh Sách Phim Đang Chiếu
    var movieSwiper = new Swiper(".movieSwiper", {
        slidesPerView: 4, 
        spaceBetween: 30, 
        loop: false,
        navigation: {
            nextEl: ".custom-nav-next",
            prevEl: ".custom-nav-prev",
        },
        breakpoints: {
            320: { slidesPerView: 1, spaceBetween: 20 },
            576: { slidesPerView: 2, spaceBetween: 20 },
            768: { slidesPerView: 3, spaceBetween: 25 },
            1024: { slidesPerView: 4, spaceBetween: 30 },
        }
    });

    // Swiper cho Danh Sách Phim Sắp Chiếu
    var comingMovieSwiper = new Swiper(".comingMovieSwiper", {
        slidesPerView: 4, 
        spaceBetween: 30, 
        loop: false,
        navigation: {
            nextEl: ".coming-nav-next",
            prevEl: ".coming-nav-prev",
        },
        breakpoints: {
            320: { slidesPerView: 1, spaceBetween: 20 },
            576: { slidesPerView: 2, spaceBetween: 20 },
            768: { slidesPerView: 3, spaceBetween: 25 },
            1024: { slidesPerView: 4, spaceBetween: 30 },
        }
    });
</script>

<?php require_once 'footer.php'; ?>


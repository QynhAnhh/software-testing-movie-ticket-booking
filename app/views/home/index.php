<?php
require_once __DIR__ . '/../layouts/header.php';

// 1. KẾT NỐI DATABASE BẰNG PDO
$host = '127.0.0.1';
$port = '3308';
$dbname = 'movie_ticket_booking';
$username = 'root';
$password = '123456'; 

try {
    // Thêm tham số port vào đây
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
// 2. TRUY VẤN LẤY PHIM ĐANG CHIẾU VÀ THỂ LOẠI
$sql = "
    SELECT m.id, m.title, m.age_restriction, m.duration, m.country, 
           mi.image_url as poster, -- Lấy ảnh từ bảng movie_images
           GROUP_CONCAT(g.name SEPARATOR ', ') as genres
    FROM movies m
    LEFT JOIN movie_images mi ON m.id = mi.movie_id
    LEFT JOIN movie_genre mg ON m.id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.id
    WHERE m.status = 'now_showing' AND m.is_active = 1
    GROUP BY m.id
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. TRUY VẤN LẤY PHIM SẮP CHIẾU
$sqlComing = "
    SELECT m.id, m.title, m.age_restriction, m.duration, m.country, 
           mi.image_url as poster,
           GROUP_CONCAT(g.name SEPARATOR ', ') as genres
    FROM movies m
    LEFT JOIN movie_images mi ON m.id = mi.movie_id
    LEFT JOIN movie_genre mg ON m.id = mg.movie_id
    LEFT JOIN genres g ON mg.genre_id = g.id
    WHERE m.status = 'coming' AND m.is_active = 1
    GROUP BY m.id
";
$stmtComing = $pdo->prepare($sqlComing);
$stmtComing->execute();
$moviesComing = $stmtComing->fetchAll(PDO::FETCH_ASSOC);

// Hàm nhỏ để format nhãn độ tuổi
function formatAgeRating($age) {
    if ($age == 0) return ['label' => 'P', 'class' => 'age-p', 'color' => '#22c55e']; // Xanh lá
    return ['label' => 'T' . $age, 'class' => 'age-t' . $age, 'color' => ($age >= 18 ? '#ef4444' : '#eab308')];
}
?>

<div class="banner-section">
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide">
                <img src="/MOVIE-TICKET-BOOKING/public/assets/img/tonghop-banner.jpg" alt="Banner 1">
            </div>
            <div class="swiper-slide">
                <img src="/MOVIE-TICKET-BOOKING/public/assets/img/minions&quaivat-banner.jpg" alt="Banner 2">
            </div>
            <div class="swiper-slide">
                <img src="/MOVIE-TICKET-BOOKING/public/assets/img/supergirl-banner.png" alt="Banner 3">
            </div>
            <div class="swiper-slide">
                <img src="/MOVIE-TICKET-BOOKING/public/assets/img/muave-banner.png" alt="Banner 4">
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
                    $posterUrl = !empty($movie['poster']) ? $movie['poster'] : 'https://via.placeholder.com/300x450?text=No+Image';
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
                                    <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/tag.svg" alt="Genre" class="info-icon"> 
                                    <?= htmlspecialchars($movie['genres'] ?? 'Đang cập nhật') ?>
                                </li>
                                <li>
                                    <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/time.svg" alt="Clock" class="info-icon"> 
                                    <?= htmlspecialchars($movie['duration']) ?>'
                                </li>
                                <li>
                                    <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/world.svg" alt="Globe" class="info-icon"> 
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
                    $posterUrl = !empty($movie['poster']) ? $movie['poster'] : 'https://via.placeholder.com/300x450?text=No+Image';
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
                                    <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/tag.svg" alt="Genre" class="info-icon"> 
                                    <?= htmlspecialchars($movie['genres'] ?? 'Đang cập nhật') ?>
                                </li>
                                <li>
                                    <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/time.svg" alt="Clock" class="info-icon"> 
                                    <?= htmlspecialchars($movie['duration']) ?>'
                                </li>
                                <li>
                                    <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/world.svg" alt="Globe" class="info-icon"> 
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<!-- http://localhost/MOVIE-TICKET-BOOKING/app/views/layouts/header.php -->

<?php
// Mô phỏng session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giả lập dữ liệu mảng Rạp phim nếu biến $theaters chưa được Controller truyền vào
if (!isset($theaters)) {
    $theaters = [
        ['id' => 1, 'name' => 'Cinema Quốc Thanh (TP.HCM)'],
        ['id' => 2, 'name' => 'Cinema Sinh Viên (TP.HCM)'],
        ['id' => 3, 'name' => 'Cinema Đà Lạt (Lâm Đồng)'],
        ['id' => 4, 'name' => 'Cinema Lâm Đồng (Đức Trọng)'],
    ];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Ticket Booking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:ital,wght@0,100..700;1,100..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />
    
    <link rel="stylesheet" href="/movie-ticket-booking/public/assets/css/global.css">
    <link rel="stylesheet" href="/movie-ticket-booking/public/assets/css/header.css">
    <link rel="stylesheet" href="/movie-ticket-booking/public/assets/css/footer.css"> 
    <link rel="stylesheet" href="/MOVIE-TICKET-BOOKING/public/assets/css/home.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
        <div class="header-top">
            <div class="logo">
                <a href="/"><img src="/movie-ticket-booking/public/assets/img/logo.png" alt="Logo"></a>
            </div>
            
            <div class="header-top-right">
    <form class="search-box" action="/search" method="GET">
        <input type="text" name="keyword" placeholder="Tìm phim...">
        <button type="submit" class="search-btn">
            <img src="/movie-ticket-booking/public/assets/svg/search1.svg" alt="Search" class="search-icon">
        </button>
    </form>

    <div class="auth-section">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="user-profile">
                <span>Hi, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                <a href="/settings" title="Cài đặt tài khoản">
                    <img src="/movie-ticket-booking/public/assets/svg/setting.svg" alt="Settings" class="setting-icon">
                </a>
            </div>
        <?php else: ?>
            <a href="/login" class="login-btn">
            Đăng nhập
            </a>
        <?php endif; ?>
    </div>
</div>
        </div>

        <nav class="header-bottom">
            <div class="nav-item dropdown">
                <span>Chọn rạp</span>
                <div class="dropdown-content">
                    <?php foreach ($theaters as $theater): ?>
                        <a href="/theater/<?php echo $theater['id']; ?>">
                            <?php echo htmlspecialchars($theater['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="/showtimes" class="nav-item">Lịch chiếu</a>
            <a href="/movies" class="nav-item">Phim</a>
            <a href="/promotions" class="nav-item">Khuyến mãi</a>
        </nav>
        </div>
    </header>
    
    <main>
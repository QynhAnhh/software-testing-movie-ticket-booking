<!-- http://localhost/movie-ticket-booking/app/views/layouts/header.php -->

<?php
require_once __DIR__ . '/config.php';

// Mô phỏng session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giả lập dữ liệu mảng Rạp phim nếu biến $theaters chưa được Controller truyền vào
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
    
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css"> 
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/movies.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <header class="main-header">
        <div class="container">
        <div class="header-top">
            <div class="logo">
                <a href="index.php"><img src="images/logo.png" alt="Logo"></a>
            </div>
            
            <div class="header-top-right">
    <form class="search-box" action="#" method="GET">
        <input type="text" name="keyword" placeholder="Tìm phim...">
        <button type="submit" class="search-btn">
            <img src="images/svg/search1.svg" alt="Search" class="search-icon">
        </button>
    </form>

    <div class="auth-section">
        <?php if (isset($_SESSION['user'])): ?>
            <div class="user-profile">
                <span>Hi, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
                <a href="settings.php" title="Cài đặt tài khoản">
                    <img src="images/svg/setting.svg" alt="Settings" class="setting-icon">
                </a>
            </div>
        <?php else: ?>
            <a href="logreg.php" class="login-btn">
            Đăng nhập
            </a>
        <?php endif; ?>
    </div>
</div>
        </div>

        <nav class="header-bottom">
            <a href="movie_list.php" class="nav-item">Phim</a>
            <a href="guide.php" class="nav-item">Hướng dẫn đặt vé</a>
        </nav>
        </div>
    </header>
    
    <main>


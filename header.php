<?php
/**
 * KIẾN THỨC PHP: Tái sử dụng mã (Include/Require)
 * 
 * 1. require_once 'config.php': Chèn nội dung của file config.php vào đây.
 *    - 'require': Bắt buộc phải có file này, nếu không tìm thấy sẽ báo lỗi Fatal Error và dừng chạy.
 *    - '_once': Đảm bảo file chỉ được chèn 1 lần duy nhất để tránh lỗi trùng lặp hàm/biến.
 */

require_once 'config.php';

// Mảng tĩnh tạm thời giả lập danh sách rạp chiếu (để hiển thị lên Navbar)
$theaters = [
    ['id' => 1, 'name' => 'Cinema Quốc Thanh (TP.HCM)'],
    ['id' => 2, 'name' => 'Cinema Sinh Viên (TP.HCM)'],
    ['id' => 3, 'name' => 'Cinema Đà Lạt (Lâm Đồng)'],
    ['id' => 4, 'name' => 'Cinema Lâm Đồng (Đức Trọng)'],
];

$sessionUser = $_SESSION['user'] ?? null;
if (isset($_SESSION['user']) && !is_array($sessionUser)) {
    unset($_SESSION['user']);
    $sessionUser = null;
}

$isLoggedIn = is_array($sessionUser);
$displayName = $isLoggedIn ? ($sessionUser['first_name'] ?? $sessionUser['email'] ?? 'User') : '';
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- CSS lõi dùng chung mọi trang -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <!-- CSS riêng cho từng trang: khai báo $pageCSS trước khi include header -->
    <?php if (!empty($pageCSS)): foreach ((array)$pageCSS as $css): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
    <?php endforeach; endif; ?>
</head>
<body>
    <header class="main-header">
        <div class="container">
        <div class="header-top">
            <div class="logo">
                <a href="index.php"><img src="images/logo.png" alt="Logo"></a>
            </div>
            
            <div class="header-top-right">
                <form class="search-box" action="search.php" method="GET">
                    <input type="text" name="keyword" placeholder="Tìm phim...">
                    <button type="submit" class="search-btn">
                        <img src="images/svg/search1.svg" alt="Search" class="search-icon">
                    </button>
                </form>

                <div class="auth-section">
                    <?php 
                    /**
                     * KIẾN THỨC PHP: Điều kiện (If/Else) và Session
                     * Nếu mảng $_SESSION có tồn tại key 'user' (tức là đã đăng nhập)
                     */
                    if ($isLoggedIn): 
                    ?>
                        <div class="user-profile">
                            <span>Hi, <?php echo htmlspecialchars($displayName); ?></span>
                            <a href="profile.php" title="Cài đặt tài khoản">
                                <img src="images/svg/setting.svg" alt="Settings" class="setting-icon">
                            </a>
                        </div>
                        <a href="logout.php" class="login-btn">Đăng xuất</a>
                    <?php else: ?>
                        <a href="login.php" class="login-btn">Đăng nhập</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <nav class="header-bottom">
            <div class="nav-item dropdown">
                <span>Chọn rạp</span>
                <div class="dropdown-content">
                    <?php 
                    /**
                     * KIẾN THỨC PHP: Vòng lặp foreach
                     * Duyệt qua từng phần tử trong mảng $theaters, gán giá trị hiện tại vào biến $theater
                     */
                    foreach ($theaters as $theater): 
                    ?>
                        <a href="schedule.php?theater_id=<?php echo $theater['id']; ?>">
                            <?php echo htmlspecialchars($theater['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="schedule.php" class="nav-item">Lịch chiếu</a>
            <a href="index.php" class="nav-item">Phim</a>
            <a href="promotions.php" class="nav-item">Khuyến mãi</a>
        </nav>
        </div>
    </header>
    
    <!-- Bắt đầu phần nội dung chính (main). Thẻ đóng </main> sẽ nằm ở footer.php -->
    <main>

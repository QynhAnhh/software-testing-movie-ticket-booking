<nav class="admin-sidebar" aria-label="Admin navigation">
    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-film"></i></span>
        <span>MOVIE ADMIN</span>
    </div>

    <div class="sidebar-section-label">Quản trị</div>
    <div class="nav flex-column sidebar-nav">
        <a href="index.php" class="nav-link <?= ($current_page == 'index.php') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="manage_movies.php" class="nav-link <?= ($current_page == 'manage_movies.php') ? 'active' : '' ?>">
            <i class="bi bi-camera-reels"></i> Quản lý Phim
        </a>
        <a href="manage_genres.php" class="nav-link <?= ($current_page == 'manage_genres.php') ? 'active' : '' ?>">
            <i class="bi bi-tags"></i> Quản lý Thể loại
        </a>
        <a href="manage_booking.php" class="nav-link <?= ($current_page == 'manage_booking.php') ? 'active' : '' ?>">
            <i class="bi bi-ticket-perforated"></i> Quản lý Đặt vé
        </a>
        <a href="manage_users.php" class="nav-link <?= ($current_page == 'manage_users.php') ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Quản lý Người dùng
        </a>
        <a href="../index.php" class="nav-link" target="_blank" rel="noopener">
            <i class="bi bi-house-door"></i> Xem trang chủ
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="d-grid">
            <a href="../logout.php" class="btn btn-netflix-red btn-sm">
                <i class="bi bi-box-arrow-right"></i> Đăng xuất
            </a>
        </div>
    </div>
</nav>

<div class="admin-content flex-grow-1">

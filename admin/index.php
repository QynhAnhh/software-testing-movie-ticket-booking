<?php
require_once '../config.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

// Dashboard statistics
$result_movies = mysqli_query($conn, "SELECT COUNT(*) as count FROM movies");
$total_movies = mysqli_fetch_assoc($result_movies)['count'];

$result_users = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='user'");
$total_users = mysqli_fetch_assoc($result_users)['count'];

$result_bookings = mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings");
$total_bookings = mysqli_fetch_assoc($result_bookings)['count'];

$result_revenue = mysqli_query($conn, "SELECT SUM(total_price) as total FROM bookings WHERE status='paid'");
$total_revenue = mysqli_fetch_assoc($result_revenue)['total'] ?? 0;

$query_recent_bookings = "
    SELECT b.*, u.first_name, u.last_name
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    ORDER BY b.created_at DESC LIMIT 5
";
$recent_bookings = mysqli_query($conn, $query_recent_bookings);

$query_now_showing = "SELECT * FROM movies WHERE status = 'now_showing' AND is_active = 1 LIMIT 5";
$now_showing = mysqli_query($conn, $query_now_showing);
?>

<div class="container-fluid">
    <div class="admin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-0 text-white fw-bold">Dashboard tổng quan</h1>
            <p class="mb-0 mt-2 text-muted">Theo dõi nhanh số lượng phim, người dùng, lượt đặt vé và doanh thu đã thanh toán.</p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-card-danger h-100 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="stat-label">Tổng số phim</div>
                    <h2 class="stat-value"><?= number_format($total_movies) ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-film"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-card-success h-100 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="stat-label">Người dùng</div>
                    <h2 class="stat-value"><?= number_format($total_users) ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-card-warning h-100 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="stat-label">Lượt đặt vé</div>
                    <h2 class="stat-value"><?= number_format($total_bookings) ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-ticket-perforated"></i>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-card-info h-100 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <div class="stat-label">Doanh thu</div>
                    <h2 class="stat-value"><?= number_format($total_revenue, 0, ',', '.') ?>đ</h2>
                </div>
                <div class="stat-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="admin-card h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h5 class="mb-0 text-white"><i class="bi bi-clock-history me-2"></i>Đặt vé gần đây</h5>
                    <a href="manage_booking.php" class="btn btn-sm btn-admin-secondary">Xem tất cả</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover admin-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Mã đặt</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_bookings && mysqli_num_rows($recent_bookings) > 0): ?>
                                <?php while ($booking = mysqli_fetch_assoc($recent_bookings)): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($booking['booking_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?></td>
                                        <td><?= number_format($booking['total_price'], 0, ',', '.') ?>đ</td>
                                        <td>
                                            <?php if ($booking['status'] == 'paid'): ?>
                                                <span class="status-badge status-success">Đã thanh toán</span>
                                            <?php elseif ($booking['status'] == 'pending'): ?>
                                                <span class="status-badge status-warning">Chờ thanh toán</span>
                                            <?php else: ?>
                                                <span class="status-badge status-danger">Đã hủy</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="admin-empty d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-ticket-perforated"></i>
                                            <span>Chưa có dữ liệu đặt vé.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="admin-card h-100">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <h5 class="mb-0 text-white"><i class="bi bi-play-circle me-2"></i>Phim đang chiếu</h5>
                    <a href="manage_movies.php" class="btn btn-sm btn-admin-secondary">Xem tất cả</a>
                </div>
                <ul class="list-group list-group-flush admin-movie-list">
                    <?php if ($now_showing && mysqli_num_rows($now_showing) > 0): ?>
                        <?php while ($movie = mysqli_fetch_assoc($now_showing)): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($movie['poster']) ?>"
                                         class="admin-poster admin-poster-sm" alt="<?= htmlspecialchars($movie['title']) ?>">
                                    <div class="ms-3">
                                        <h6 class="mb-1 fw-bold"><?= htmlspecialchars($movie['title']) ?></h6>
                                        <small class="text-muted"><i class="bi bi-clock me-1"></i><?= $movie['duration'] ?> phút</small>
                                    </div>
                                </div>
                                <span class="status-badge status-danger">T<?= $movie['age_restriction'] ?></span>
                            </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="list-group-item">
                            <div class="admin-empty d-flex align-items-center justify-content-center gap-2">
                                <i class="bi bi-camera-reels"></i>
                                <span>Không có phim đang chiếu.</span>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

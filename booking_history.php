<?php
/**
 * ============================================================
 * booking_history.php- LỊCH SỬ ĐẶT VÉ
 * ============================================================*/

$pageCSS = ['css/booking_history.css'];
session_start(); // Bắt đầu session để lấy thông tin user
require_once 'header.php';
require_once 'app/init.php';

use App\Controllers\BookingController;

$user   = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? $_SESSION['user_id'] ?? null;

if (!$userId) {
    ?>
    <div class="history-page">
        <div class="history-container">
            <div class="empty-history">
                <span class="empty-icon">🔐</span>
                <p>Vui lòng đăng nhập để xem lịch sử đặt vé.</p>
                <a href="login.php" class="btn-browse">Đăng Nhập Ngay</a>
            </div>
        </div>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

$controller = new BookingController();
// Sử dụng phương thức getUserBookings thay vì getBookingHistory
$bookings = $controller->getUserBookings($userId);
?>

<div class="history-page">
    <div class="history-container">
         <!-- ===== HEADER: TIÊU ĐỀ + NÚT VỀ TRANG CHỦ ===== -->
        <div class="history-header">
            <div>
                <span class="eyebrow">🎫 Tài Khoản</span>
                <h1>Lịch Sử Đặt Vé</h1>
            </div>
            <a href="index.php" class="btn-home">🏠 Về Trang Chủ</a>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="empty-history">
                <span class="empty-icon">🎬</span>
                <p>Bạn chưa có giao dịch đặt vé nào.</p>
                <a href="movie_list.php" class="btn-browse">Khám Phá Phim Ngay</a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
            <div class="booking-item">
                <div class="booking-header">
                    <span class="booking-code">🎫 <?php echo htmlspecialchars($booking['booking_code'] ?? 'N/A'); ?></span>
                    <?php 
                    $status = $booking['status'] ?? 'pending';
                    $statusClass = '';
                    $statusText = '';
                    if ($status === 'paid' || $status === 'confirmed') {
                        $statusClass = 'completed';
                        $statusText = '✅ Đã Đặt';
                    } elseif ($status === 'canceled') {
                        $statusClass = 'cancelled';
                        $statusText = '❌ Đã Hủy';
                    } else {
                        $statusClass = 'pending';
                        $statusText = '⏳ Đang Xử Lý';
                    }
                    ?>
                    <span class="booking-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                </div>
                  <!-- Chi tiết booking -->
                <div class="booking-details">
                    <span>🎬 <span class="highlight"><?php echo htmlspecialchars($booking['movie_title'] ?? 'Đang cập nhật'); ?></span></span>
                    <span>🏢 <span class="highlight"><?php echo htmlspecialchars($booking['theatre_name'] ?? 'Đang cập nhật'); ?></span></span>
                    <span>🪑 Ghế: <span class="highlight"><?php echo htmlspecialchars($booking['seat_names'] ?? 'Đang cập nhật'); ?></span></span>
                    <span>💰 Tổng tiền: <span class="highlight"><?php echo number_format($booking['total_price'] ?? 0, 0, ',', '.'); ?>đ</span></span>
                    <span>📅 Ngày đặt: <span class="highlight"><?php echo date('d/m/Y H:i', strtotime($booking['created_at'] ?? 'now')); ?></span></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<?php require_once 'footer.php'; ?>
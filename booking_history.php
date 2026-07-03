<?php
/**
 * booking_history.php - Lịch sử đặt vé
 */

$pageCSS = ['css/booking_history.css'];

require_once 'config.php';

use App\Controllers\BookingController;

if (!isset($_SESSION['user']['id'])) {
    $_SESSION['error_msg'] = 'Vui lòng đăng nhập để xem lịch sử đặt vé.';
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user']['id'];

$controller = new BookingController();
$bookings = $controller->getUserBookings($userId);

require_once 'header.php';
?>

<div class="history-page">
    <div class="history-container">

        <div class="history-header">
            <div>
                <span class="eyebrow">🎫 Tài khoản</span>
                <h1>Lịch sử đặt vé</h1>
            </div>
            <a href="index.php" class="btn-home">🏠 Về trang chủ</a>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="empty-history">
                <span class="empty-icon">🎬</span>
                <p>Bạn chưa có giao dịch đặt vé nào.</p>
                <a href="index.php" class="btn-browse">Khám phá phim ngay</a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <?php
                    $status = $booking['status'] ?? 'pending';

                    if ($status === 'paid') {
                        $statusClass = 'completed';
                        $statusText = '✅ Đã đặt';
                    } elseif ($status === 'canceled') {
                        $statusClass = 'cancelled';
                        $statusText = '❌ Đã hủy';
                    } else {
                        $statusClass = 'pending';
                        $statusText = '⏳ Đang xử lý';
                    }
                ?>

                <div class="booking-item">
                    <div class="booking-header">
                        <span class="booking-code">
                            🎫 <?= htmlspecialchars($booking['booking_code'] ?? ('BK-' . $booking['id'])); ?>
                        </span>

                        <span class="booking-status <?= $statusClass; ?>">
                            <?= $statusText; ?>
                        </span>
                    </div>

                    <div class="booking-details">
                        <span>
                            🎬
                            <span class="highlight">
                                <?= htmlspecialchars($booking['movie_title'] ?? 'Đang cập nhật'); ?>
                            </span>
                        </span>

                        <span>
                            🏢
                            <span class="highlight">
                                <?= htmlspecialchars($booking['theatre_name'] ?? 'Đang cập nhật'); ?>
                            </span>
                        </span>

                        <span>
                            🚪 Phòng:
                            <span class="highlight">
                                <?= htmlspecialchars($booking['room_name'] ?? 'Đang cập nhật'); ?>
                            </span>
                        </span>

                        <span>
                            🪑 Ghế:
                            <span class="highlight">
                                <?= htmlspecialchars($booking['seat_names'] ?? 'Đang cập nhật'); ?>
                            </span>
                        </span>

                        <span>
                            📅 Suất chiếu:
                            <span class="highlight">
                                <?= !empty($booking['show_date']) ? date('d/m/Y', strtotime($booking['show_date'])) : 'Đang cập nhật'; ?>
                                <?= !empty($booking['start_time']) ? date('H:i', strtotime($booking['start_time'])) : ''; ?>
                            </span>
                        </span>

                        <span>
                            💰 Tổng tiền:
                            <span class="highlight">
                                <?= number_format((float)($booking['total_price'] ?? 0), 0, ',', '.'); ?>đ
                            </span>
                        </span>

                        <span>
                            📌 Ngày đặt:
                            <span class="highlight">
                                <?= !empty($booking['created_at']) ? date('d/m/Y H:i', strtotime($booking['created_at'])) : 'Đang cập nhật'; ?>
                            </span>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

<?php require_once 'footer.php'; ?>
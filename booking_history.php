<?php
require_once 'header.php';

use App\Controllers\BookingController;

if (!isset($_SESSION['user'])) {
    echo "Vui lòng đăng nhập để xem lịch sử đặt vé.";
    require_once 'footer.php';
    exit;
}

$userId = $_SESSION['user']['id'];
$bookingController = new BookingController();

// Xử lý Hủy vé nếu có
$actionResult = $bookingController->handleRequest();

$bookings = $bookingController->getUserBookings($userId);
?>

<div style="padding: 20px;">
    <h1>LỊCH SỬ ĐẶT VÉ</h1>
    <a href="index.php"><- Quay lại trang chủ</a>
    <hr>
    
    <?php if (isset($actionResult)): ?>
        <p style="color: <?php echo $actionResult['status'] === 'success' ? 'green' : 'red'; ?>;">
            <?php echo $actionResult['message']; ?>
        </p>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <p>Bạn chưa có lịch sử đặt vé nào.</p>
    <?php else: ?>
        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Mã Đặt Vé</th>
                    <th>Phim</th>
                    <th>Rạp / Phòng</th>
                    <th>Thời Gian Chiếu</th>
                    <th>Ghế Đã Đặt</th>
                    <th>Tổng Tiền</th>
                    <th>Phương Thức</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['booking_code']); ?></td>
                        <td><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                        <td><?php echo htmlspecialchars($booking['theatre_name'] . ' - ' . $booking['room_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['show_date'] . ' ' . $booking['start_time']); ?></td>
                        <td>
                            <?php 
                                // Seat IDs are comma separated from GROUP_CONCAT
                                echo htmlspecialchars($booking['seat_ids'] ?? 'Không rõ'); 
                            ?>
                        </td>
                        <td><?php echo number_format($booking['total_price']); ?> đ</td>
                        <td><?php echo htmlspecialchars($booking['payment_method']); ?></td>
                        <td style="font-weight: bold; color: <?php echo $booking['status'] === 'canceled' ? 'red' : 'green'; ?>;">
                            <?php echo strtoupper($booking['status']); ?>
                        </td>
                        <td>
                            <?php if ($booking['status'] !== 'canceled'): ?>
                                <form method="POST" action="" onsubmit="return confirm('Bạn có chắc chắn muốn hủy vé này?');">
                                    <input type="hidden" name="action" value="cancel_ticket">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                    <button type="submit" style="background: red; color: white; border: none; padding: 5px 10px; cursor: pointer;">
                                        Hủy Vé
                                    </button>
                                </form>
                            <?php else: ?>
                                Đã Hủy
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php';
?>

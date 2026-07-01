<?php
require_once 'header.php';

use App\Controllers\ShowtimeController;
use App\Controllers\SeatController;
use App\Controllers\BookingController;

$showtimeId = (int)($_GET['showtime_id'] ?? 0);
if ($showtimeId <= 0) {
    echo "Suất chiếu không hợp lệ.";
    require_once 'footer.php';
    exit;
}

$showtimeController = new ShowtimeController();
$seatController = new SeatController();
$bookingController = new BookingController();

// Xử lý form đặt vé trước
$bookingResult = $bookingController->handleRequest();
if ($bookingResult && $bookingResult['status'] === 'success') {
    // Chuyển hướng sang trang lịch sử đặt vé nếu thành công
    echo "<script>window.location.href = 'booking_history.php';</script>";
    exit;
}

$showtime = $showtimeController->getShowtimeDetails($showtimeId);
if (!$showtime) {
    echo "Không tìm thấy thông tin suất chiếu.";
    require_once 'footer.php';
    exit;
}

$seatMap = $seatController->getSeatMap($showtimeId, $showtime['room_id']);

?>

<div style="padding: 20px;">
    <h1>ĐẶT VÉ PHIM</h1>
    <a href="movie_details.php?id=<?php echo $showtime['movie_id']; ?>"><- Quay lại chi tiết phim</a>
    <hr>
    
    <h2>1. Thông tin Suất chiếu</h2>
    <ul>
        <li><strong>Phim:</strong> <?php echo htmlspecialchars($showtime['title']); ?></li>
        <li><strong>Rạp:</strong> <?php echo htmlspecialchars($showtime['theatre_name']); ?> (<?php echo htmlspecialchars($showtime['address']); ?>)</li>
        <li><strong>Phòng:</strong> <?php echo htmlspecialchars($showtime['room_name']); ?></li>
        <li><strong>Thời gian:</strong> <?php echo $showtime['start_time']; ?> - <?php echo $showtime['end_time']; ?> (Ngày <?php echo $showtime['show_date']; ?>)</li>
        <li><strong>Giá vé cơ bản:</strong> <?php echo number_format($showtime['base_price']); ?> đ</li>
    </ul>

    <hr>
    
    <h2>2. Chọn Ghế & Thanh Toán</h2>
    
    <?php if (isset($bookingResult)): ?>
        <p style="color: red;"><?php echo $bookingResult['message']; ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['user'])): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="book_ticket">
            <input type="hidden" name="showtime_id" value="<?php echo $showtimeId; ?>">
            
            <div style="margin-bottom: 20px;">
                <strong>Sơ đồ ghế:</strong><br><br>
                <?php 
                $currentRow = '';
                foreach ($seatMap as $seat) {
                    if ($seat['seat_row'] !== $currentRow) {
                        if ($currentRow !== '') echo "<br><br>";
                        $currentRow = $seat['seat_row'];
                        echo "<strong>Dãy $currentRow:</strong> ";
                    }
                    $isBooked = ($seat['status'] === 'booked');
                    $extraPrice = $seat['base_price_extra'];
                    $label = $seat['seat_row'] . $seat['seat_number'] . " (" . $seat['type_name'] . " +$extraPrice" . "đ)";
                ?>
                    <label style="margin-right: 15px; <?php echo $isBooked ? 'color: #999; text-decoration: line-through;' : ''; ?>">
                        <input type="checkbox" name="seats[]" value="<?php echo $seat['id']; ?>" <?php echo $isBooked ? 'disabled' : ''; ?>>
                        <?php echo $label; ?>
                    </label>
                <?php } ?>
            </div>

            <div style="margin-bottom: 20px;">
                <strong>Phương thức thanh toán:</strong><br>
                <label><input type="radio" name="payment_method" value="cash" checked> Tiền mặt</label>
                <label><input type="radio" name="payment_method" value="momo"> MoMo</label>
                <label><input type="radio" name="payment_method" value="vnpay"> VNPay</label>
            </div>

            <button type="submit" style="padding: 10px 20px; font-size: 16px; background: #e50914; color: white; border: none; cursor: pointer;">Xác nhận đặt vé</button>
        </form>
    <?php else: ?>
        <p>Vui lòng <a href="login.php">đăng nhập</a> để đặt vé.</p>
    <?php endif; ?>
</div>

<?php
require_once 'footer.php';
?>

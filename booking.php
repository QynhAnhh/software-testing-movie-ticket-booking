<?php
/**
 * booking.php - Trang đặt vé phim
 * Flow MVP: Chọn ghế -> Chọn phương thức thanh toán -> Xác nhận đặt vé
 */

$pageCSS = ['css/booking.css'];

require_once 'config.php';

use App\Controllers\ShowtimeController;
use App\Controllers\SeatController;
use App\Controllers\BookingController;

if (!isset($_SESSION['user'])) {
    $_SESSION['error_msg'] = 'Vui lòng đăng nhập để đặt vé.';
    header('Location: login.php');
    exit;
}

$showtimeId = (int)($_GET['showtime_id'] ?? $_POST['showtime_id'] ?? 0);

if ($showtimeId <= 0) {
    require_once 'header.php';
    echo "<div style='padding:40px;text-align:center;color:#fff;'>Suất chiếu không hợp lệ.</div>";
    require_once 'footer.php';
    exit;
}

$showtimeController = new ShowtimeController();
$seatController = new SeatController();
$bookingController = new BookingController();

$bookingResult = $bookingController->handleRequest();

if ($bookingResult && $bookingResult['status'] === 'success') {
    header('Location: booking_history.php');
    exit;
}

$showtime = $showtimeController->getShowtimeDetails($showtimeId);

if (!$showtime) {
    require_once 'header.php';
    echo "<div style='padding:40px;text-align:center;color:#fff;'>Không tìm thấy thông tin suất chiếu.</div>";
    require_once 'footer.php';
    exit;
}

$seatMap = $seatController->getSeatMap($showtimeId, $showtime['room_id']);

require_once 'header.php';
?>

<div class="booking-page">
    <div class="booking-container">

        <div class="booking-top-bar">
            <div class="booking-title">
                <span class="eyebrow">🎫 Đặt Vé</span>
                <h1>Chọn ghế & thanh toán</h1>
            </div>
            <a href="movie_details.php?id=<?= (int)$showtime['movie_id']; ?>" class="back-link-top">
                ← Quay lại chi tiết phim
            </a>
        </div>

        <div class="booking-header">
            <div class="movie-poster">
                <img
                    src="<?= htmlspecialchars($showtime['poster'] ?? 'images/movies/default.jpg'); ?>"
                    alt="<?= htmlspecialchars($showtime['movie_title'] ?? 'Phim'); ?>"
                    onerror="this.src='images/movies/default.jpg'">
            </div>

            <div class="movie-info">
                <h1><?= htmlspecialchars($showtime['movie_title'] ?? 'Phim'); ?></h1>
                <div class="movie-meta">
                    <span>⏱ <?= htmlspecialchars($showtime['duration'] ?? 'Đang cập nhật'); ?> phút</span>
                    <span>🌍 <?= htmlspecialchars($showtime['country'] ?? 'Đang cập nhật'); ?></span>
                    <span>🔞 <?= htmlspecialchars($showtime['age_restriction'] ?? 'Đang cập nhật'); ?>+</span>
                </div>
            </div>
        </div>

        <div class="showtime-info">
            <h2>🎬 Thông tin suất chiếu</h2>

            <div class="info-grid">
                <div class="info-item">
                    <span class="label">🏢 Rạp</span>
                    <span class="value"><?= htmlspecialchars($showtime['theatre_name'] ?? 'Đang cập nhật'); ?></span>
                </div>

                <div class="info-item">
                    <span class="label">🚪 Phòng</span>
                    <span class="value"><?= htmlspecialchars($showtime['room_name'] ?? 'Đang cập nhật'); ?></span>
                </div>

                <div class="info-item">
                    <span class="label">📅 Ngày</span>
                    <span class="value"><?= date('d/m/Y', strtotime($showtime['show_date'] ?? 'now')); ?></span>
                </div>

                <div class="info-item">
                    <span class="label">⏰ Giờ</span>
                    <span class="value">
                        <?php
                        if (!empty($showtime['start_time']) && !empty($showtime['end_time'])) {
                            echo date('H:i', strtotime($showtime['start_time'])) . ' - ' . date('H:i', strtotime($showtime['end_time']));
                        } else {
                            echo 'Đang cập nhật';
                        }
                        ?>
                    </span>
                </div>

                <div class="info-item">
                    <span class="label">💰 Giá vé cơ bản</span>
                    <span class="value price"><?= number_format((float)($showtime['base_price'] ?? 0), 0, ',', '.'); ?>đ</span>
                </div>
            </div>
        </div>

        <?php if ($bookingResult && $bookingResult['status'] === 'error'): ?>
            <div class="alert error">
                ❌ <?= htmlspecialchars($bookingResult['message']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="booking-form">
            <input type="hidden" name="action" value="book_ticket">
            <input type="hidden" name="showtime_id" value="<?= $showtimeId; ?>">

            <div class="seat-selection">
                <h2>💺 Chọn ghế</h2>

                <div class="legend">
                    <span class="legend-item">
                        <span class="legend-swatch selected"></span> Đã chọn
                    </span>
                    <span class="legend-item">
                        <span class="legend-swatch" style="background:rgba(255,255,255,0.1);border-color:rgba(255,255,255,0.2);"></span> Còn trống
                    </span>
                    <span class="legend-item">
                        <span class="legend-swatch unavailable"></span> Không thể chọn
                    </span>
                    <span class="legend-item">
                        <span class="legend-swatch vip"></span> VIP / Phụ thu
                    </span>
                </div>

                <div class="screen">🎬 MÀN HÌNH</div>

                <div class="seat-grid">
                    <?php if (empty($seatMap)): ?>
                        <p style="color:#fff;">Phòng chiếu chưa có ghế.</p>
                    <?php else: ?>
                        <?php foreach ($seatMap as $seat): ?>
                            <?php
                                $status = $seat['status'] ?? 'available';
                                $isSelectable = ($status === 'available');

                                $basePrice = (float)($showtime['base_price'] ?? 0);
                                $extraPrice = (float)($seat['seat_type_price'] ?? 0);
                                $totalPrice = $basePrice + $extraPrice;

                                $seatLabel = ($seat['seat_row'] ?? '') . ($seat['seat_number'] ?? '');
                                $isVip = $extraPrice > 0;
                            ?>

                            <label class="seat-option <?= !$isSelectable ? 'disabled' : ''; ?> <?= $isVip ? 'vip' : ''; ?>">
                                <input
                                    type="checkbox"
                                    name="seats[]"
                                    value="<?= (int)$seat['id']; ?>"
                                    <?= !$isSelectable ? 'disabled' : ''; ?>
                                    data-price="<?= $totalPrice; ?>"
                                    onchange="updateTotal()">

                                <span class="seat-label">
                                    <?= htmlspecialchars($seatLabel); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="payment-section">
                <h2>💳 Thanh toán</h2>

                <div class="total-box">
                    <span class="total-label">Tổng tiền</span>
                    <span class="total-amount" id="totalDisplay">0đ</span>
                    <span class="seat-count">(🎫 <span id="seatCount">0</span> ghế)</span>
                </div>

                <div class="payment-methods">
                    <label class="payment-option active" onclick="selectPayment(this)">
                        <input type="radio" name="payment_method" value="cash" checked>
                        <span class="payment-icon">💵</span>
                        <span class="payment-name">Tiền mặt</span>
                    </label>

                    <label class="payment-option" onclick="selectPayment(this)">
                        <input type="radio" name="payment_method" value="momo">
                        <span class="payment-icon">📱</span>
                        <span class="payment-name">MoMo</span>
                    </label>

                    <label class="payment-option" onclick="selectPayment(this)">
                        <input type="radio" name="payment_method" value="vnpay">
                        <span class="payment-icon">🏦</span>
                        <span class="payment-name">VNPay</span>
                    </label>
                </div>

                <button type="submit" class="btn-confirm">
                    ✅ XÁC NHẬN ĐẶT VÉ
                </button>
            </div>
        </form>

    </div>
</div>

<script>
function updateTotal() {
    const checkboxes = document.querySelectorAll('input[name="seats[]"]:checked');
    let total = 0;

    checkboxes.forEach(cb => {
        total += parseInt(cb.dataset.price) || 0;
    });

    document.getElementById('totalDisplay').textContent = total.toLocaleString('vi-VN') + 'đ';
    document.getElementById('seatCount').textContent = checkboxes.length;
}

function selectPayment(element) {
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('active');
    });

    element.classList.add('active');

    const radio = element.querySelector('input[type="radio"]');

    if (radio) {
        radio.checked = true;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateTotal();

    document.querySelectorAll('.seat-option input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', function() {
            this.closest('.seat-option').classList.toggle('selected', this.checked);
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>
<?php
/**
 * booking.php - Dat ve phim
 * Flow: Chon ghe -> Chon phuong thuc thanh toan -> Xac nhan dat ve
 */
$pageCSS = ['css/booking.css'];
require_once 'header.php';
require_once 'app/init.php';

use App\Controllers\ShowtimeController;
use App\Controllers\SeatController;
use App\Controllers\BookingController;

$showtimeId = (int)($_GET['showtime_id'] ?? 0);
if ($showtimeId <= 0) {
    echo "<div class='text-center text-white py-5'>Suất chiếu không hợp lệ.</div>";
    require_once 'footer.php';
    exit;
}

$showtimeController = new ShowtimeController();
$seatController = new SeatController();
$bookingController = new BookingController();

$bookingResult = $bookingController->handleRequest();
if ($bookingResult && $bookingResult['status'] === 'success') {
    echo "<script>window.location.href = 'booking_history.php';</script>";
    exit;
}

$showtime = $showtimeController->getShowtimeDetails($showtimeId);
if (!$showtime) {
    echo "<div class='text-center text-white py-5'>Không tìm thấy thông tin suất chiếu.</div>";
    require_once 'footer.php';
    exit;
}

$seatMap = $seatController->getSeatMap($showtimeId, $showtime['room_id']);
$seatsByRow = [];
foreach ($seatMap as $seat) {
    $seatsByRow[$seat['seat_row']][] = $seat;
}

foreach ($seatsByRow as &$rowSeats) {
    usort($rowSeats, function ($a, $b) {
        return (int)$a['seat_number'] <=> (int)$b['seat_number'];
    });
}
unset($rowSeats);

$maxSeatsInRow = 0;
foreach ($seatsByRow as $rowSeats) {
    $maxSeatsInRow = max($maxSeatsInRow, count($rowSeats));
}
$aisleAfter = $maxSeatsInRow > 6 ? (int)ceil($maxSeatsInRow / 2) : 0;

$posterPath = $showtime['poster'] ?? 'images/movies/default.jpg';
if (empty($posterPath)) {
    $posterPath = 'images/movies/default.jpg';
}
if (!preg_match('/^https?:\/\//i', $posterPath) && !file_exists($posterPath)) {
    $posterPath = 'images/movies/default.jpg';
}

$movieTitle = htmlspecialchars($showtime['movie_title'] ?? 'Phim');
$theatreName = htmlspecialchars($showtime['theatre_name'] ?? 'Đang cập nhật');
$theatreAddress = htmlspecialchars($showtime['address'] ?? 'Đang cập nhật địa chỉ');
$roomName = htmlspecialchars($showtime['room_name'] ?? 'Phòng chiếu');
$showDate = !empty($showtime['show_date']) ? date('d/m/Y', strtotime($showtime['show_date'])) : 'Đang cập nhật';
$startTime = !empty($showtime['start_time']) ? date('H:i', strtotime($showtime['start_time'])) : '--:--';
$endTime = !empty($showtime['end_time']) ? date('H:i', strtotime($showtime['end_time'])) : '--:--';
$basePrice = (float)($showtime['base_price'] ?? 0);
$isLoggedIn = isset($_SESSION['user']);
?>

<div class="booking-page">
    <div class="container py-4 py-lg-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <span class="booking-eyebrow">
                    <i class="bi bi-ticket-perforated"></i>
                    Đặt vé
                </span>
                <h1 class="booking-page-title">Chọn Ghế Ngồi</h1>
            </div>

            <a href="movie_details.php?id=<?php echo (int)$showtime['movie_id']; ?>" class="btn btn-outline-light booking-back-link">
                <i class="bi bi-arrow-left"></i>
                Chi tiết phim
            </a>
        </div>

        <?php if (isset($bookingResult) && $bookingResult['status'] === 'error'): ?>
            <div class="booking-alert is-error mb-4">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo htmlspecialchars($bookingResult['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!$isLoggedIn): ?>
            <div class="booking-alert is-error mb-4">
                <i class="bi bi-lock-fill"></i>
                Vui lòng <a href="login.php">đăng nhập</a> để đặt vé.
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="booking-form">
            <input type="hidden" name="action" value="book_ticket">
            <input type="hidden" name="showtime_id" value="<?php echo $showtimeId; ?>">

            <div class="row g-4 align-items-start">
                <aside class="col-lg-4">
                    <div class="booking-summary">
                        <img src="<?php echo htmlspecialchars($posterPath); ?>"
                             alt="<?php echo $movieTitle; ?>"
                             class="booking-summary-poster"
                             onerror="this.src='images/movies/default.jpg'; this.onerror=null;">

                        <h2><?php echo $movieTitle; ?></h2>

                        <div class="booking-summary-list">
                            <div>
                                <i class="bi bi-geo-alt-fill"></i>
                                <span>
                                    <strong><?php echo $theatreName; ?></strong>
                                    <small><?php echo $theatreAddress; ?></small>
                                </span>
                            </div>
                            <div>
                                <i class="bi bi-calendar-fill"></i>
                                <span><?php echo $showDate; ?></span>
                            </div>
                            <div>
                                <i class="bi bi-clock-fill"></i>
                                <span><?php echo $startTime; ?> - <?php echo $endTime; ?></span>
                            </div>
                            <div>
                                <i class="bi bi-display"></i>
                                <span><?php echo $roomName; ?></span>
                            </div>
                        </div>

                        <div class="booking-price-box">
                            <span>Giá vé</span>
                            <strong><?php echo number_format($basePrice, 0, ',', '.'); ?>đ</strong>
                        </div>

                        <div class="booking-total">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Số ghế</span>
                                <strong><span id="seatCount">0</span></strong>
                            </div>
                            <div id="selectedSeatsDisplay" class="selected-seat-list">Chưa chọn ghế</div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span>Tổng tiền</span>
                                <strong id="totalDisplay">0đ</strong>
                            </div>
                        </div>

                        <div class="payment-block">
                            <h3>Thanh toán</h3>
                            <div class="payment-methods">
                                <label class="payment-option active">
                                    <input type="radio" name="payment_method" value="cash" checked>
                                    <i class="bi bi-cash-stack"></i>
                                    <span>Tiền mặt</span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="momo">
                                    <i class="bi bi-phone"></i>
                                    <span>MoMo</span>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="vnpay">
                                    <i class="bi bi-bank"></i>
                                    <span>VNPay</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                                id="bookingSubmit"
                                class="btn btn-danger w-100 booking-submit"
                                disabled>
                            <i class="bi bi-ticket-perforated"></i>
                            Xác nhận đặt vé
                        </button>
                    </div>
                </aside>

                <section class="col-lg-8">
                    <div class="seat-panel">
                        <div class="seat-legend">
                            <span><i class="seat-swatch available"></i> Ghế trống</span>
                            <span><i class="seat-swatch selected"></i> Ghế đang chọn</span>
                            <span><i class="seat-swatch booked"></i> Ghế đã đặt</span>
                            <span><i class="seat-swatch vip"></i> Ghế phụ thu</span>
                        </div>

                        <div class="screen-wrap">
                            <div class="screen-glow"></div>
                            <span>Màn hình</span>
                        </div>

                        <?php if (empty($seatsByRow)): ?>
                            <div class="empty-seat-map">
                                Chưa có sơ đồ ghế cho phòng chiếu này.
                            </div>
                        <?php else: ?>
                            <div class="seat-map" style="--seat-columns: <?php echo max($maxSeatsInRow, 1); ?>;">
                                <?php foreach ($seatsByRow as $row => $rowSeats): ?>
                                    <div class="seat-row">
                                        <span class="row-label"><?php echo htmlspecialchars($row); ?></span>
                                        <div class="seat-row-grid">
                                            <?php foreach ($rowSeats as $seat): ?>
                                                <?php
                                                $isBooked = ($seat['status'] === 'booked');
                                                $extraPrice = (float)($seat['base_price_extra'] ?? $seat['seat_type_price'] ?? 0);
                                                $seatPrice = $basePrice + $extraPrice;
                                                $seatNumber = (int)$seat['seat_number'];
                                                $seatLabel = $seat['seat_row'] . $seatNumber;
                                                $isVip = $extraPrice > 0;
                                                $seatClasses = ['seat-option'];
                                                if ($isBooked) {
                                                    $seatClasses[] = 'disabled';
                                                }
                                                if ($isVip) {
                                                    $seatClasses[] = 'vip';
                                                }
                                                if ($aisleAfter > 0 && $seatNumber === $aisleAfter + 1) {
                                                    $seatClasses[] = 'after-aisle';
                                                }
                                                ?>
                                                <label class="<?php echo implode(' ', $seatClasses); ?>" title="<?php echo htmlspecialchars($seatLabel); ?>">
                                                    <input type="checkbox"
                                                           name="seats[]"
                                                           value="<?php echo (int)$seat['id']; ?>"
                                                           data-price="<?php echo $seatPrice; ?>"
                                                           data-label="<?php echo htmlspecialchars($seatLabel); ?>"
                                                           <?php echo $isBooked ? 'disabled' : ''; ?>>
                                                    <span><?php echo $seatNumber; ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </form>
    </div>
</div>

<script>
function formatCurrency(value) {
    return value.toLocaleString('vi-VN') + 'đ';
}

function updateBookingSummary() {
    const selectedSeats = document.querySelectorAll('input[name="seats[]"]:checked');
    let total = 0;
    const labels = [];

    selectedSeats.forEach((seat) => {
        total += Number.parseFloat(seat.dataset.price || '0');
        labels.push(seat.dataset.label || '');
    });

    const totalDisplay = document.getElementById('totalDisplay');
    const seatCount = document.getElementById('seatCount');
    const selectedSeatsDisplay = document.getElementById('selectedSeatsDisplay');
    const submitButton = document.getElementById('bookingSubmit');

    if (totalDisplay) totalDisplay.textContent = formatCurrency(total);
    if (seatCount) seatCount.textContent = selectedSeats.length;
    if (selectedSeatsDisplay) selectedSeatsDisplay.textContent = labels.length ? labels.join(', ') : 'Chưa chọn ghế';
    if (submitButton) {
        submitButton.disabled = selectedSeats.length === 0;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.seat-option input[type="checkbox"]').forEach((checkbox) => {
        checkbox.addEventListener('change', function() {
            this.closest('.seat-option').classList.toggle('selected', this.checked);
            updateBookingSummary();
        });
    });

    document.querySelectorAll('.payment-option input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-option').forEach((option) => option.classList.remove('active'));
            this.closest('.payment-option').classList.add('active');
        });
    });

    const bookingForm = document.querySelector('.booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(event) {
            if (document.querySelectorAll('input[name="seats[]"]:checked').length === 0) {
                event.preventDefault();
                updateBookingSummary();
            }
        });
    }

    updateBookingSummary();
});
</script>

<?php require_once 'footer.php'; ?>

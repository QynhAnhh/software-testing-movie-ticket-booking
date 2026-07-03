<?php
require_once 'config.php';

use App\Controllers\ShowtimeController;
use App\Controllers\SeatController;
use App\Controllers\TicketController;

$showtimeId = (int)($_GET['showtime_id'] ?? 0);
if ($showtimeId <= 0) {
    header('Location: index.php');
    exit;
}

$showtimeController = new ShowtimeController();
$seatController = new SeatController();
$ticketController = new TicketController();

$showtime = $showtimeController->getShowtimeDetail($showtimeId);
if (!$showtime) {
    echo "<script>alert('Suất chiếu không tồn tại!'); window.location='index.php';</script>";
    exit;
}

$seats = $seatController->getSeatsByRoomId((int)$showtime['room_id']);
$bookedSeatIds = $ticketController->getBookedSeatIdsByShowtimeId($showtimeId);

$seatsByRow = [];
foreach ($seats as $seat) {
    $seatsByRow[$seat['seat_row']][] = $seat;
}

$poster = !empty($showtime['movie_poster']) ? $showtime['movie_poster'] : 'https://via.placeholder.com/400x600?text=No+Image';
$address = trim(($showtime['theatre_address'] ?? '') . ', ' . ($showtime['theatre_city'] ?? ''), ', ');
$basePrice = (float)$showtime['base_price'];

require_once 'header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .booking-page {
        background: #0b1120;
        color: #fff;
        padding: 30px 0 50px;
    }

    .booking-card {
        background: #1a1a1a;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 10px;
    }

    .booking-sidebar {
        padding: 20px;
    }

    .booking-sidebar img {
        width: 100%;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .booking-meta {
        color: #aaa;
        margin-bottom: 10px;
    }

    .booking-meta i {
        color: #e50914;
    }

    .booking-address {
        color: #999;
        font-size: 13px;
        margin-bottom: 15px;
    }

    .booking-summary {
        border-top: 1px solid #333;
        padding-top: 15px;
        margin-top: 15px;
    }

    .summary-value {
        color: #fff;
        font-weight: 700;
    }

    .summary-total {
        color: #e50914;
        font-size: 20px;
        font-weight: 700;
    }

    .btn-confirm-booking {
        background: #e50914;
        border-color: #e50914;
        height: 45px;
        font-weight: 700;
    }

    .btn-confirm-booking:hover {
        background: #b80710;
        border-color: #b80710;
    }

    .seat-legend-box {
        padding: 20px;
        margin-bottom: 25px;
    }

    .screen {
        background: linear-gradient(to bottom, #555, #222);
        height: 10px;
        border-radius: 50%;
        margin: 30px auto;
        width: 80%;
        box-shadow: 0 3px 20px rgba(229, 9, 20, 0.5);
    }

    .seat-map {
        padding: 30px;
    }

    .seat-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 14px;
    }

    .row-label {
        color: #e50914;
        font-weight: 700;
        width: 24px;
        text-align: center;
        flex: 0 0 24px;
    }

    .seat-group {
        display: grid;
        grid-template-columns: repeat(6, 42px);
        gap: 8px;
    }

    .seat-aisle {
        width: 28px;
        flex: 0 0 28px;
    }

    .seat {
        width: 42px;
        height: 38px;
        border: 0;
        border-radius: 8px 8px 4px 4px;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .seat.available {
        background: #374151;
        cursor: pointer;
    }

    .seat.vip {
        background: #facc15;
        color: #111827;
    }

    .seat.available:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(255, 255, 255, 0.15);
    }

    .seat.selected {
        background: #e50914;
        cursor: pointer;
    }

    .seat.booked,
    .seat.inactive {
        background: #111827;
        color: #6b7280;
        cursor: not-allowed;
    }

    .payment-option {
        background: #242424;
        border: 1px solid #333;
        border-radius: 8px;
        color: #ccc;
        padding: 10px 12px;
        cursor: pointer;
    }

    .payment-option:has(input:checked) {
        border-color: #e50914;
        color: #fff;
    }

    @media (max-width: 767.98px) {
        .seat-map {
            padding: 18px 10px;
            overflow-x: auto;
        }

        .seat-row {
            justify-content: flex-start;
            min-width: 650px;
        }
    }
</style>

<div class="booking-page">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="booking-card booking-sidebar">
                    <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($showtime['movie_title']) ?>" onerror="this.src='https://via.placeholder.com/400x600?text=No+Image';">
                    <h4 class="mb-3"><?= htmlspecialchars($showtime['movie_title']) ?></h4>

                    <p class="booking-meta">
                        <i class="bi bi-geo-alt-fill"></i>
                        <strong><?= htmlspecialchars($showtime['theatre_name']) ?></strong>
                    </p>
                    <p class="booking-address"><?= htmlspecialchars($address ?: 'Chưa cập nhật địa chỉ') ?></p>

                    <p class="booking-meta">
                        <i class="bi bi-calendar-fill"></i>
                        <?= date('d/m/Y', strtotime($showtime['show_date'])) ?>
                    </p>
                    <p class="booking-meta">
                        <i class="bi bi-clock-fill"></i>
                        <?= date('H:i', strtotime($showtime['start_time'])) ?> - <?= date('H:i', strtotime($showtime['end_time'])) ?>
                    </p>
                    <p class="booking-meta">
                        <i class="bi bi-display-fill"></i>
                        <?= htmlspecialchars($showtime['room_name']) ?>
                    </p>

                    <div class="booking-summary">
                        <p class="booking-meta">
                            Giá vé cơ bản:
                            <span class="summary-value"><?= number_format($basePrice, 0, ',', '.') ?>đ</span>
                        </p>
                        <p class="booking-meta">
                            Ghế đã chọn:
                            <span id="selected-seats" class="summary-value">Chưa chọn</span>
                        </p>
                        <p class="booking-meta">
                            Số ghế:
                            <span id="seat-count" class="summary-value">0</span>
                        </p>
                        <p class="booking-meta">
                            Tổng tiền:
                            <span id="total-price" class="summary-total">0đ</span>
                        </p>

                        <div class="mb-3">
                            <label class="form-label text-white fw-bold">Phương thức thanh toán</label>
                            <div class="d-grid gap-2">
                                <label class="payment-option">
                                    <input class="form-check-input me-2" type="radio" name="payment_method" value="momo" checked>
                                    Momo
                                </label>
                                <label class="payment-option">
                                    <input class="form-check-input me-2" type="radio" name="payment_method" value="vnpay">
                                    VNPay
                                </label>
                                <label class="payment-option">
                                    <input class="form-check-input me-2" type="radio" name="payment_method" value="bank_transfer">
                                    Chuyển khoản
                                </label>
                            </div>
                        </div>

                        <button id="btn-confirm" type="button" class="btn btn-danger btn-confirm-booking w-100" disabled>
                            <i class="bi bi-ticket-perforated-fill"></i> XÁC NHẬN ĐẶT VÉ
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <h2 class="mb-4">Chọn Ghế Ngồi</h2>

                <div class="booking-card seat-legend-box">
                    <div class="row text-center g-3">
                        <div class="col-6 col-lg-3">
                            <button class="seat available" type="button" disabled></button>
                            <span class="text-secondary ms-2">Ghế trống</span>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button class="seat vip" type="button" disabled></button>
                            <span class="text-secondary ms-2">Ghế VIP</span>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button class="seat selected" type="button" disabled></button>
                            <span class="text-secondary ms-2">Ghế đang chọn</span>
                        </div>
                        <div class="col-6 col-lg-3">
                            <button class="seat booked" type="button" disabled></button>
                            <span class="text-secondary ms-2">Ghế đã đặt</span>
                        </div>
                    </div>
                </div>

                <div class="screen"></div>
                <p class="text-center text-secondary mb-4">MÀN HÌNH</p>

                <div id="seat-map" class="booking-card seat-map">
                    <?php if (!empty($seatsByRow)): ?>
                        <?php foreach ($seatsByRow as $row => $rowSeats): ?>
                            <?php
                            usort($rowSeats, function ($a, $b) {
                                return (int)$a['seat_number'] <=> (int)$b['seat_number'];
                            });
                            $leftSeats = array_filter($rowSeats, function ($seat) {
                                return (int)$seat['seat_number'] <= 6;
                            });
                            $rightSeats = array_filter($rowSeats, function ($seat) {
                                return (int)$seat['seat_number'] > 6;
                            });
                            ?>
                            <div class="seat-row">
                                <span class="row-label"><?= htmlspecialchars($row) ?></span>

                                <div class="seat-group">
                                    <?php foreach ($leftSeats as $seat): ?>
                                        <?php renderSeatButton($seat, $bookedSeatIds, $basePrice); ?>
                                    <?php endforeach; ?>
                                </div>

                                <span class="seat-aisle"></span>

                                <div class="seat-group">
                                    <?php foreach ($rightSeats as $seat): ?>
                                        <?php renderSeatButton($seat, $bookedSeatIds, $basePrice); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-secondary mb-0">Phòng chiếu này chưa có dữ liệu ghế.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function renderSeatButton($seat, $bookedSeatIds, $basePrice) {
    $seatId = (int)$seat['seat_id'];
    $seatName = $seat['seat_row'] . $seat['seat_number'];
    $isBooked = in_array($seatId, $bookedSeatIds, true);
    $isInactive = !(bool)$seat['is_active'];
    $price = (float)$basePrice + (float)$seat['seat_type_price'];
    $isVip = strtoupper($seat['seat_type_name']) === 'VIP';
    $class = $isBooked ? 'booked' : ($isInactive ? 'inactive' : 'available' . ($isVip ? ' vip' : ''));
    $disabled = ($isBooked || $isInactive) ? 'disabled' : '';

    echo '<button type="button" class="seat ' . $class . '" data-seat-id="' . $seatId . '" data-seat-name="' . htmlspecialchars($seatName) . '" data-price="' . $price . '" title="' . htmlspecialchars($seat['seat_type_name']) . '" ' . $disabled . '>' . (int)$seat['seat_number'] . '</button>';
}
?>

<script>
    const seatButtons = document.querySelectorAll('.seat.available');
    const seatCount = document.getElementById('seat-count');
    const selectedSeats = document.getElementById('selected-seats');
    const totalPrice = document.getElementById('total-price');
    const confirmButton = document.getElementById('btn-confirm');
    const formatter = new Intl.NumberFormat('vi-VN');

    function getSelectedSeatButtons() {
        return Array.from(document.querySelectorAll('.seat.selected[data-seat-id]'));
    }

    function updateSummary() {
        const selected = getSelectedSeatButtons();
        const names = selected.map((seat) => seat.dataset.seatName);
        const total = selected.reduce((sum, seat) => sum + Number(seat.dataset.price || 0), 0);

        seatCount.textContent = selected.length;
        selectedSeats.textContent = names.length ? names.join(', ') : 'Chưa chọn';
        totalPrice.textContent = formatter.format(total) + 'đ';
        confirmButton.disabled = selected.length === 0;
    }

    seatButtons.forEach((button) => {
        button.addEventListener('click', () => {
            button.classList.toggle('available');
            button.classList.toggle('selected');
            updateSummary();
        });
    });

    confirmButton.addEventListener('click', () => {
        const selected = getSelectedSeatButtons();
        if (!selected.length) {
            return;
        }

        const payment = document.querySelector('input[name="payment_method"]:checked');
        const seatNames = selected.map((seat) => seat.dataset.seatName).join(', ');
        const total = selected.reduce((sum, seat) => sum + Number(seat.dataset.price || 0), 0);

        alert(
            'Ghế đã chọn: ' + seatNames +
            '\nPhương thức thanh toán: ' + (payment ? payment.parentElement.textContent.trim() : 'Chưa chọn') +
            '\nTổng tiền: ' + formatter.format(total) + 'đ'
        );
    });
</script>

<?php require_once 'footer.php'; ?>

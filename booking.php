<?php
require_once 'config.php';

use App\Controllers\ShowtimeController;
use App\Controllers\SeatController;
use App\Controllers\TicketController;
use App\Controllers\BookingController;
use App\Models\VoucherModel;

$showtimeController = new ShowtimeController();
$seatController = new SeatController();
$ticketController = new TicketController();
$bookingController = new BookingController();

// Check session
if (!isset($_SESSION['user'])) {
    echo "<script>alert('Vui lòng đăng nhập để đặt vé!'); window.location='login.php';</script>";
    exit;
}

$result = $bookingController->handleRequest();
if ($result) {
    if ($result['status'] === 'success') {
        $bookingId = (int)($result['booking_id'] ?? 0);
        // Redirect bằng PHP để chắc chắn chuyển sang lịch sử đặt vé sau khi transaction commit.
        header('Location: booking_history.php?booking_success=1&booking_id=' . $bookingId);
        exit;
    }
    if (isset($result['page'])) {
        echo "
            <script>
                alert('{$result['message']}');
                window.location='{$result['page']}';
            </script>
        ";    
    } else {
        echo "
            <script>
                alert('{$result['message']}');
            </script>
        "; 
    }
    exit;
}

$showtimeId = (int)(
    $_GET['showtime_id']
    ??
    $_POST['showtime_id']
    ??
    0
);

if ($showtimeId <= 0) {
    header('Location: index.php');
    exit;
}

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
$vipPrice = (float)$showtime['base_price'] + 20000;
$voucherModel = new VoucherModel();
$availableVouchers = $voucherModel->getActiveForUser((int)$_SESSION['user']['id']);

require_once 'header.php';
?>

<link rel="stylesheet" href="css/booking.css">

<div class="booking-page">
    <div class="container">
        <a href="movie_details.php?id=<?= (int)$showtime['movie_id'] ?>" class="btn btn-outline-light btn-sm mb-4">
            <i class="bi bi-arrow-left"></i> Quay lại chi tiết phim
        </a>

        <div class="row g-4">
            <!-- Sidebar thông tin vé & Thanh toán -->
            <div class="col-md-4">
                <div class="booking-card booking-sidebar">
                    <img src="<?= htmlspecialchars($poster) ?>" alt="<?= htmlspecialchars($showtime['movie_title']) ?>" onerror="this.src='https://via.placeholder.com/400x600?text=No+Image';">
                    <h4 class="mb-3 mt-3"><?= htmlspecialchars($showtime['movie_title']) ?></h4>

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

                    <div class="booking-summary mt-3">
                        <p class="booking-meta">
                            Giá vé cơ bản:
                            <span class="summary-value"><?= number_format($basePrice, 0, ',', '.') ?>đ</span>
                        </p>
                        <p class="booking-meta">
                            Giá vé VIP:
                            <span class="summary-value"><?= number_format($vipPrice, 0, ',', '.') ?>đ</span>
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
                            Tạm tính:
                            <span id="subtotal-price" class="summary-value">0đ</span>
                        </p>
                        <p class="booking-meta text-success">
                            Giảm giá:
                            <span id="discount-amount" class="summary-value text-success">-0đ</span>
                        </p>
                        <p class="booking-meta">
                            Tổng tiền:
                            <span id="total-price" class="summary-total">0đ</span>
                        </p>

                        <!-- KHUNG NHẬP MÃ VOUCHER -->
                        <div class="mb-3 mt-3 border-top pt-3 border-secondary">
                            <label class="form-label text-white small fw-bold">Mã giảm giá / Voucher</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="voucher-code" class="form-control bg-dark text-white border-secondary" placeholder="Nhập mã (VD: VIP1)">
                                <button type="button" id="btn-apply-voucher" class="btn btn-danger">Áp dụng</button>
                            </div>
                            <small id="voucher-message" class="d-block mt-1"></small>
                        </div>

                        <!-- PHƯƠNG THỨC THANH TOÁN -->
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

            <!-- Bản đồ ghế -->
            <div class="col-md-8">
                <h2 class="mb-4">Chọn Ghế Ngồi</h2>

                <div class="booking-card seat-legend-box mb-4">
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

                <div class="screen mb-2"></div>
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
    const seatButtons = document.querySelectorAll('.seat.available:not([disabled])');
    const seatCount = document.getElementById('seat-count');
    const selectedSeats = document.getElementById('selected-seats');
    const subtotalPriceEl = document.getElementById('subtotal-price');
    const discountAmountEl = document.getElementById('discount-amount');
    const totalPriceEl = document.getElementById('total-price');
    const confirmButton = document.getElementById('btn-confirm');
    const btnApplyVoucher = document.getElementById('btn-apply-voucher');
    const voucherInput = document.getElementById('voucher-code');
    const voucherMsg = document.getElementById('voucher-message');

    const formatter = new Intl.NumberFormat('vi-VN');
    const availableVouchers = <?= json_encode($availableVouchers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const voucherMap = {};
    availableVouchers.forEach(voucher => {
        voucherMap[String(voucher.code).toUpperCase()] = voucher;
    });
    let discountAmount = 0;
    let appliedVoucherCode = '';

    function getSelectedSeatButtons() {
        return Array.from(document.querySelectorAll('.seat.selected[data-seat-id]'));
    }

    function showVoucherMessage(message, type = 'danger') {
        voucherMsg.className = `d-block mt-1 text-${type} small`;
        voucherMsg.textContent = message;
    }

    function resetVoucher(message = '') {
        discountAmount = 0;
        appliedVoucherCode = '';

        if (message) {
            showVoucherMessage(message, 'warning');
        } else {
            voucherMsg.textContent = '';
        }
    }

    function updateSummary() {
        const selected = getSelectedSeatButtons();
        const names = selected.map(seat => seat.dataset.seatName);
        const subtotal = selected.reduce(
            (sum, seat) => sum + Number(seat.dataset.price || 0),
            0
        );

        // Nếu bỏ chọn hết ghế thì hủy voucher đã áp dụng.
        if (selected.length === 0 || subtotal === 0) {
            if (appliedVoucherCode !== '') {
                resetVoucher('Voucher đã bị hủy vì bạn chưa chọn ghế.');
            }
        } else {
            // Kiểm tra lại điều kiện voucher khi số ghế thay đổi.
            if (appliedVoucherCode !== '') {
                const voucher = voucherMap[appliedVoucherCode];
                if (!voucher) {
                    resetVoucher('Voucher không còn khả dụng hoặc tài khoản đã dùng mã này.');
                } else if (subtotal < Number(voucher.min_order_amount)) {
                    resetVoucher(
                        `Mã ${appliedVoucherCode} yêu cầu đơn từ ${formatter.format(Number(voucher.min_order_amount))}đ.`
                    );
                } else {
                    discountAmount = Math.min(Number(voucher.discount_amount), subtotal);
                }
            }
        }

        const finalTotal = Math.max(0, subtotal - discountAmount);

        seatCount.textContent = selected.length;
        selectedSeats.textContent = names.length ? names.join(', ') : 'Chưa chọn';
        subtotalPriceEl.textContent = formatter.format(subtotal) + 'đ';
        discountAmountEl.textContent = '-' + formatter.format(discountAmount) + 'đ';
        totalPriceEl.textContent = formatter.format(finalTotal) + 'đ';

        // Chỉ cho phép đặt vé khi đã chọn ít nhất 1 ghế.
        confirmButton.disabled = selected.length === 0;
    }

    // =========================
    // CHỌN / BỎ CHỌN GHẾ
    // =========================
    seatButtons.forEach(button => {
        button.addEventListener('click', function () {
            this.classList.toggle('selected');

            // Khi người dùng chọn/bỏ ghế, cập nhật lại tiền ngay lập tức.
            updateSummary();
        });
    });

    // =========================
    // ÁP DỤNG VOUCHER
    // =========================
    btnApplyVoucher.addEventListener('click', function () {
        const code = voucherInput.value.trim().toUpperCase();
        const selected = getSelectedSeatButtons();

        const subtotal = selected.reduce(
            (sum, seat) => sum + Number(seat.dataset.price || 0),
            0
        );

        // QUAN TRỌNG:
        // Chưa chọn ghế mà bấm "Áp dụng" -> hiện đúng thông báo yêu cầu.
        if (selected.length === 0 || subtotal === 0) {
            showVoucherMessage('Vui lòng chọn ghế ngồi trước khi áp dụng voucher!', 'danger');
            voucherInput.focus();
            return;
        }

        if (!code) {
            showVoucherMessage('Vui lòng nhập mã giảm giá!', 'danger');
            voucherInput.focus();
            return;
        }

        const voucher = voucherMap[code];

        if (!voucher) {
            resetVoucher();
            showVoucherMessage(
                'Mã không tồn tại, đã hết lượt, hết hạn hoặc tài khoản đã sử dụng mã này.',
                'danger'
            );
            return;
        }

        const minOrder = Number(voucher.min_order_amount);
        const discount = Number(voucher.discount_amount);

        if (subtotal < minOrder) {
            resetVoucher();
            showVoucherMessage(
                `Mã ${code} chỉ áp dụng cho đơn từ ${formatter.format(minOrder)}đ trở lên!`,
                'danger'
            );
            return;
        }

        discountAmount = Math.min(discount, subtotal);
        appliedVoucherCode = code;
        showVoucherMessage(
            `Áp dụng thành công ${code} (-${formatter.format(discountAmount)}đ)`,
            'success'
        );

        updateSummary();
    });

    // Cho phép nhấn Enter trong ô voucher để áp dụng.
    voucherInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            btnApplyVoucher.click();
        }
    });

    // =========================
    // XÁC NHẬN ĐẶT VÉ
    // =========================
    confirmButton.addEventListener('click', function () {
        const selected = getSelectedSeatButtons();

        // Kiểm tra lại một lần nữa trước khi gửi form.
        if (selected.length === 0) {
            alert('Vui lòng chọn ghế ngồi trước khi đặt vé!');
            return;
        }

        if (!confirm('Bạn có chắc chắn muốn đặt vé không?')) {
            return;
        }

        const paymentInput = document.querySelector(
            'input[name="payment_method"]:checked'
        );

        if (!paymentInput) {
            alert('Vui lòng chọn phương thức thanh toán!');
            return;
        }

        const payment = paymentInput.value;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'booking.php';

        let inputsHTML = `
            <input type="hidden" name="action" value="book_ticket">
            <input type="hidden" name="showtime_id" value="<?= $showtimeId ?>">
            <input type="hidden" name="payment_method" value="${payment}">
            <input type="hidden" name="voucher_code" value="${appliedVoucherCode}">
        `;

        selected.forEach(seat => {
            inputsHTML += `
                <input type="hidden" name="seats[]" value="${seat.dataset.seatId}">
            `;
        });

        form.innerHTML = inputsHTML;
        document.body.appendChild(form);
        form.submit();
    });

    // Khởi tạo trạng thái ban đầu.
    updateSummary();
</script>
<?php require_once 'footer.php'; ?>
<?php
// booking.php - Hệ thống đặt vé xem phim tích hợp
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user']['id'])) {
    $currentUrl = 'booking.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    header('Location: login.php?redirect=' . urlencode($currentUrl));
    exit;
}

// Đồng bộ user_id nếu AuthService lưu trong session['user']
if (!isset($_SESSION['user_id']) && isset($_SESSION['user']['id'])) {
    $_SESSION['user_id'] = $_SESSION['user']['id'];
}

// Kết nối database
$conn = new mysqli('localhost', 'root', '', 'movie_ticket_booking');
if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}

// ==================== LẤY DỮ LIỆU TỪ DATABASE ====================

// 1. Lấy danh sách phim
$movies_result = $conn->query("SELECT * FROM movies");
$movies = [];
while ($row = $movies_result->fetch_assoc()) {
    $movies[$row['id']] = $row;
}

// 2. Lấy suất chiếu
$showtimes_result = $conn->query("
    SELECT st.*, r.name as room_name 
    FROM showtimes st
    JOIN rooms r ON st.room_id = r.id
    WHERE st.show_date >= CURDATE()
    ORDER BY st.show_date, st.start_time
");

$showtimes = [];
while ($row = $showtimes_result->fetch_assoc()) {
    $showtimes[] = [
        'id' => $row['id'],
        'time' => date('H:i', strtotime($row['start_time'])),
        'room' => $row['room_id'],
        'available' => 50,
        'price' => $row['base_price'],
        'show_date' => $row['show_date'],
        'room_name' => $row['room_name'] ?? 'Phòng ' . $row['room_id'],
        'movie_id' => $row['movie_id']
    ];
}

// 3. Lấy danh sách ghế từ database theo phòng
function getSeatsByRoom($conn, $room_id) {
    $sql = "SELECT s.*, st.name as seat_type_name, st.price as seat_type_price 
            FROM seats s
            LEFT JOIN seat_types st ON s.seat_type_id = st.id
            WHERE s.room_id = ? AND s.is_active = 1
            ORDER BY s.seat_row, s.seat_number";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $seats = [];
    while ($row = $result->fetch_assoc()) {
        $seats[] = $row;
    }
    return $seats;
}

// 4. Lấy ghế đã đặt cho suất chiếu
function getBookedSeats($conn, $showtime_id) {
    $sql = "SELECT seat_id FROM tickets WHERE showtime_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $showtime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booked = [];
    while ($row = $result->fetch_assoc()) {
        $booked[] = $row['seat_id'];
    }
    return $booked;
}

// ==================== XỬ LÝ ACTION ====================
$action = isset($_GET['action']) ? $_GET['action'] : 'showtime';
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : (isset($_SESSION['booking_movie_id']) ? $_SESSION['booking_movie_id'] : 1);
$showtime_id = isset($_GET['showtime_id']) ? intval($_GET['showtime_id']) : (isset($_SESSION['booking_showtime_id']) ? $_SESSION['booking_showtime_id'] : 0);
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

$_SESSION['booking_movie_id'] = $movie_id;
$_SESSION['booking_showtime_id'] = $showtime_id;

// Lấy thông tin phim
$movie = isset($movies[$movie_id]) ? $movies[$movie_id] : reset($movies);

// Hàm lấy ảnh
function getMovieImage($movie) {
    if (!empty($movie['poster'])) {
        if (filter_var($movie['poster'], FILTER_VALIDATE_URL)) {
            return $movie['poster'];
        }
        return '/movie-ticket-booking/images/movies/' . $movie['poster'];
    }
    return '/movie-ticket-booking/images/movies/default.jpg';
}

// ============================================================
// ACTION 1: HIỂN THỊ DANH SÁCH SUẤT CHIẾU
// ============================================================
if ($action === 'showtime') {
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn Suất Chiếu - <?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?></title>
    <link rel="stylesheet" href="/movie-ticket-booking/css/showtime.css">
</head>
<body>
    <div class="booking-container">
        <div class="breadcrumb">
            <div class="step active-step">1. Chọn Suất Chiếu</div>
            <div class="step">2. Chọn Ghế</div>
            <div class="step">3. Xác Nhận</div>
            <div class="step">4. Thanh Toán</div>
        </div>

        <!-- MOVIE INFO -->
        <div class="movie-section">
            <div class="movie-poster">
                <img src="<?php echo getMovieImage($movie); ?>" alt="<?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?>">
            </div>
            <div class="movie-info">
                <h1><?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?></h1>
                <div class="movie-meta">
                    <span>⭐ <?php echo $movie['rating'] ?? 0; ?>/10</span>
                    <span>⏱ <?php echo intdiv($movie['duration'] ?? 0, 60); ?>h <?php echo ($movie['duration'] ?? 0) % 60; ?>m</span>
                    <span>🎬 <?php echo htmlspecialchars($movie['genre'] ?? 'Đang cập nhật'); ?></span>
                    <span>🌐 <?php echo htmlspecialchars($movie['country'] ?? 'Đang cập nhật'); ?></span>
                </div>
                <p class="movie-description"><?php echo htmlspecialchars($movie['description'] ?? 'Chưa có mô tả.'); ?></p>
                <div class="movie-detail">
                    <p><strong>Khởi chiếu:</strong> <?php echo htmlspecialchars($movie['screening_date'] ?? 'Đang cập nhật'); ?></p>
                    <p><strong>Đạo diễn:</strong> <?php echo htmlspecialchars($movie['director'] ?? 'Đang cập nhật'); ?></p>
                    <p><strong>Quốc gia:</strong> <?php echo htmlspecialchars($movie['country'] ?? 'Đang cập nhật'); ?></p>
                </div>
            </div>
        </div>

        <!-- DATE SELECTION -->
        <div class="card">
            <h2>📅 Chọn Ngày Chiếu</h2>
            <div class="date-list">
                <?php
                $dates = [
                    ['day' => 'Thứ 2', 'date' => '22/06'],
                    ['day' => 'Thứ 3', 'date' => '23/06', 'active' => true],
                    ['day' => 'Thứ 4', 'date' => '24/06'],
                    ['day' => 'Thứ 5', 'date' => '25/06'],
                    ['day' => 'Thứ 6', 'date' => '26/06'],
                    ['day' => 'Thứ 7', 'date' => '27/06'],
                    ['day' => 'Chủ nhật', 'date' => '28/06'],
                    ['day' => 'Thứ 2', 'date' => '29/06'],
                    ['day' => 'Thứ 3', 'date' => '30/06'],
                ];
                foreach ($dates as $d):
                    $activeClass = isset($d['active']) ? 'active' : '';
                ?>
                    <button class="date-btn <?php echo $activeClass; ?>" onclick="selectDate(this)">
                        <span><?php echo $d['day']; ?></span>
                        <strong><?php echo $d['date']; ?></strong>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- CINEMA INFO -->
        <div class="card">
            <h2>🏢 Rạp Chiếu</h2>
            <div class="cinema-box">
                <h3>🎭 CGV Hùng Vương Plaza</h3>
                <p>📍 126 Hồng Bàng, Quận 5, TP.HCM</p>
            </div>
        </div>

        <!-- SHOWTIME SELECTION -->
        <div class="card">
            <h2>⏰ Chọn Suất Chiếu</h2>
            <div class="showtime-list">
                <?php 
                $has_showtimes = false;
                foreach ($showtimes as $st): 
                    if ($st['movie_id'] == $movie_id):
                        $has_showtimes = true;
                ?>
                    <button class="showtime-btn <?php echo ($st['id'] == $showtime_id) ? 'active-showtime' : ''; ?>"
                        onclick="selectShowtime(this, <?php echo $st['id']; ?>, <?php echo $st['price']; ?>, <?php echo $st['room']; ?>)">
                        <span><?php echo htmlspecialchars($st['time']); ?></span>
                        <small>🎬 Phòng <?php echo $st['room']; ?></small>
                        <small>💺 <?php echo $st['available']; ?> ghế trống</small>
                    </button>
                <?php 
                    endif;
                endforeach; 
                if (!$has_showtimes):
                ?>
                    <p style="color: #888; grid-column: 1 / -1; text-align: center; padding: 20px;">
                        😅 Hiện chưa có suất chiếu cho phim này.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- SUMMARY -->
        <div class="summary-card">
            <h2>📋 Tóm Tắt Đặt Vé</h2>
            <div class="summary-item">
                <span>Phim</span>
                <strong><?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?></strong>
            </div>
            <div class="summary-item">
                <span>Ngày</span>
                <strong id="summary-date"><?php echo date('d/m/Y'); ?></strong>
            </div>
            <div class="summary-item">
                <span>Suất Chiếu</span>
                <strong id="summary-time">
                    <?php
                    $selected_st = array_filter($showtimes, fn($s) => $s['id'] == $showtime_id && $s['movie_id'] == $movie_id);
                    $selected_st = reset($selected_st);
                    echo $selected_st ? htmlspecialchars($selected_st['time']) : 'Chưa chọn';
                    ?>
                </strong>
            </div>
            <div class="summary-item">
                <span>Phòng</span>
                <strong id="summary-room">
                    <?php echo $selected_st ? 'Phòng ' . $selected_st['room'] : 'Chưa chọn'; ?>
                </strong>
            </div>
            <div class="summary-item">
                <span>Giá Vé</span>
                <strong id="summary-price">
                    <?php echo $selected_st ? number_format($selected_st['price'], 0, '.', '.') . ' VNĐ' : 'Chưa chọn'; ?>
                </strong>
            </div>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="action-group">
            <button class="btn-back" onclick="history.back()">← Quay Lại</button>
            <form action="booking.php" method="GET">
                <input type="hidden" name="action" value="seat">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                <input type="hidden" name="showtime_id" id="showtime_id_input" value="<?php echo $showtime_id; ?>">
                <input type="hidden" name="room_id" id="room_id_input" value="<?php echo $selected_st['room'] ?? 0; ?>">
                <button type="submit" class="btn-next" <?php echo !$showtime_id ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                    Tiếp Tục Chọn Ghế →
                </button>
            </form>
        </div>
    </div>

    <script>
        function selectDate(element) {
            document.querySelectorAll('.date-btn').forEach(b => b.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('summary-date').textContent = element.querySelector('strong').textContent;
        }

        function selectShowtime(element, showtimeId, price, room) {
            document.querySelectorAll('.showtime-btn').forEach(b => b.classList.remove('active-showtime'));
            element.classList.add('active-showtime');

            const time = element.querySelector('span').textContent;

            document.getElementById('summary-time').textContent = time;
            document.getElementById('summary-room').textContent = 'Phòng ' + room;
            document.getElementById('summary-price').textContent = price.toLocaleString('vi-VN') + ' VNĐ';
            document.getElementById('showtime_id_input').value = showtimeId;
            document.getElementById('room_id_input').value = room;
        }
    </script>
</body>
</html>
<?php
    exit;
}

// ============================================================
// CHỌN GHẾ - LẤY GHẾ TỪ DATABASE
if ($action === 'seat') {
    // Lấy room_id từ URL
    $room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
    // Lấy danh sách ghế từ database theo phòng
    $seats = getSeatsByRoom($conn, $room_id);
    // Lấy ghế đã đặt cho suất chiếu
    $bookedSeatIds = getBookedSeats($conn, $showtime_id);
    
    // Lọc suất chiếu
    $selected_showtime = array_filter($showtimes, fn($s) => $s['id'] == $showtime_id && $s['movie_id'] == $movie_id);
    $selected_showtime = reset($selected_showtime);

    if (!$selected_showtime) {
        header('Location: booking.php?action=showtime&movie_id=' . $movie_id);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn Ghế - <?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?></title>
    <link rel="stylesheet" href="/movie-ticket-booking/css/seat.css">
</head>
<body>
    <div class="seat-page">
        <div class="breadcrumb">
            <div class="step">1. Chọn Suất Chiếu</div>
            <div class="step active-step">2. Chọn Ghế</div>
            <div class="step">3. Xác Nhận</div>
            <div class="step">4. Thanh Toán</div>
        </div>

        <div class="seat-header">
            <h1>🎫 Chọn Ghế Ngồi</h1>
            <p><?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?> | <?php echo htmlspecialchars($selected_showtime['time']); ?> - Phòng <?php echo $selected_showtime['room']; ?></p>
        </div>

        <div class="screen">🎬 MÀN HÌNH 🎬</div>

        <div class="seat-legend">
            <div class="legend-item"><div class="legend-box available"></div><span>Ghế Trống</span></div>
            <div class="legend-item"><div class="legend-box selected"></div><span>Ghế Được Chọn</span></div>
            <div class="legend-item"><div class="legend-box unavailable"></div><span>Ghế Đã Đặt</span></div>
            <div class="legend-item"><div class="legend-box vip"></div><span>VIP (Thêm 20.000đ)</span></div>
            <div class="legend-item"><div class="legend-box couple"></div><span>COUPLE (Thêm 50.000đ)</span></div>
        </div>

        <div class="seat-map">
            <?php if (!empty($seats)): ?>
                <?php foreach ($seats as $seat): 
                    $isBooked = in_array($seat['id'], $bookedSeatIds);
                    $class = $isBooked ? 'unavailable' : '';
                    $seatType = strtolower($seat['seat_type_name'] ?? 'regular');
                    if ($seatType == 'vip') $class .= ' vip';
                    if ($seatType == 'couple') $class .= ' couple';
                ?>
                    <button class="seat <?php echo $class; ?>" 
                        data-seat-id="<?php echo $seat['id']; ?>"
                        data-seat-type="<?php echo $seatType; ?>"
                        data-price="<?php echo $seat['seat_type_price'] ?? 0; ?>"
                        <?php echo $isBooked ? 'disabled' : ''; ?>
                        onclick="toggleSeat(this)">
                        <?php echo $seat['seat_row'] . $seat['seat_number']; ?>
                    </button>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #888; grid-column: 1 / -1; text-align: center; padding: 30px;">
                    Không có ghế cho phòng này.
                </p>
            <?php endif; ?>
        </div>

        <div class="seat-info">
            <h3>🪑 Ghế Đã Chọn</h3>
            <div class="seat-list" id="selected-seats-list">Chưa chọn ghế nào</div>
        </div>

        <div class="confirmation-section">
            <h3>📋 Tóm Tắt Đặt Vé</h3>
            <div class="confirmation-item"><span>Phim</span><strong><?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?></strong></div>
            <div class="confirmation-item"><span>Suất Chiếu</span><strong><?php echo htmlspecialchars($selected_showtime['time']); ?> - Phòng <?php echo $selected_showtime['room']; ?> | <?php echo date('d/m/Y', strtotime($selected_showtime['show_date'])); ?></strong></div>
            <div class="confirmation-item"><span>Số Vé</span><strong id="seat-count">0</strong></div>
            <div class="confirmation-item"><span>Giá Vé (1 vé)</span><strong><?php echo number_format($selected_showtime['price'], 0, '.', '.'); ?> VNĐ</strong></div>
            <div class="confirmation-item"><span>Tổng Tiền</span><strong id="total-price">0 VNĐ</strong></div>
        </div>

        <div class="action-group">
            <button class="btn-back" onclick="history.back()">← Quay Lại</button>
            <form action="booking.php" method="GET">
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                <input type="hidden" name="seats" id="selected_seats_input" value="">
                <button type="submit" class="btn-submit" id="confirm-btn" disabled>Xác Nhận Đặt Vé →</button>
            </form>
        </div>
    </div>

    <script>
        const selectedSeats = new Map(); // seat_id => {seat_name, price}
        const basePrice = <?php echo $selected_showtime['price']; ?>;

        function toggleSeat(element) {
            if (element.classList.contains('unavailable')) return;
            
            const seatId = element.dataset.seatId;
            const seatName = element.textContent.trim();
            const extraPrice = parseInt(element.dataset.price) || 0;
            
            if (selectedSeats.has(seatId)) {
                selectedSeats.delete(seatId);
                element.classList.remove('selected');
            } else {
                selectedSeats.set(seatId, {name: seatName, extraPrice: extraPrice});
                element.classList.add('selected');
            }
            updateSummary();
        }

        function updateSummary() {
            const seatsList = document.getElementById('selected-seats-list');
            const seatCount = selectedSeats.size;
            let totalExtra = 0;
            let seatNames = [];
            
            selectedSeats.forEach((value, key) => {
                seatNames.push(value.name);
                totalExtra += value.extraPrice;
            });
            
            const totalPrice = seatCount * basePrice + totalExtra;
            
            seatsList.textContent = seatNames.length ? seatNames.join(', ') : 'Chưa chọn ghế nào';
            document.getElementById('seat-count').textContent = seatCount;
            document.getElementById('total-price').textContent = totalPrice.toLocaleString('vi-VN') + ' VNĐ';
            document.getElementById('selected_seats_input').value = Array.from(selectedSeats.keys()).join(',');
            
            const confirmBtn = document.getElementById('confirm-btn');
            confirmBtn.disabled = seatCount === 0;
        }
    </script>
</body>
</html>
<?php
    exit;
}

// ============================================================
// 3: XÁC NHẬN (config)
// ============================================================
if ($action === 'confirm') {
    $selected_showtime = array_filter($showtimes, fn($s) => $s['id'] == $showtime_id && $s['movie_id'] == $movie_id);
    $selected_showtime = reset($selected_showtime);
    $seatIds = isset($_GET['seats']) ? explode(',', $_GET['seats']) : [];
    $seatCount = count($seatIds);
    $totalPrice = $seatCount * ($selected_showtime ? $selected_showtime['price'] : 0);
    
    // Lấy tên ghế từ database
    $seatNames = [];
    if (!empty($seatIds)) {
        $ids = implode(',', array_map('intval', $seatIds));
        $sql = "SELECT CONCAT(seat_row, seat_number) as seat_name FROM seats WHERE id IN ($ids)";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $seatNames[] = $row['seat_name'];
        }
    }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác Nhận Đặt Vé</title>
    <link rel="stylesheet" href="/movie-ticket-booking/css/confirm.css">
</head>
<body>
    <div class="confirm-container">
        <h1>🎬 Xác Nhận Đặt Vé</h1>
        <p class="subtitle">Vui lòng kiểm tra thông tin trước khi thanh toán</p>

        <div class="detail-row"><span class="label">🎥 Phim</span><span class="value"><?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?></span></div>
        <div class="detail-row"><span class="label">📅 Ngày chiếu</span><span class="value"><?php echo date('d/m/Y', strtotime($selected_showtime['show_date'])); ?></span></div>
        <div class="detail-row"><span class="label">⏰ Suất chiếu</span><span class="value"><?php echo htmlspecialchars($selected_showtime['time']); ?></span></div>
        <div class="detail-row"><span class="label">🏢 Phòng</span><span class="value">Phòng <?php echo $selected_showtime['room']; ?></span></div>
        <div class="detail-row"><span class="label">🪑 Ghế</span><span class="value"><?php echo implode(', ', $seatNames); ?></span></div>
        <div class="detail-row"><span class="label">🎫 Số vé</span><span class="value"><?php echo $seatCount; ?></span></div>
        <div class="detail-row"><span class="label">💰 Giá vé</span><span class="value"><?php echo number_format($selected_showtime['price'], 0, '.', '.'); ?> VNĐ</span></div>

        <div class="total-row">
            <span class="label">Tổng cộng</span>
            <span class="value"><?php echo number_format($totalPrice, 0, '.', '.'); ?> VNĐ</span>
        </div>

        <div class="action-group">
            <button class="btn-back" onclick="history.back()">← Quay lại</button>
            <form action="booking.php" method="GET" style="flex:1;">
                <input type="hidden" name="action" value="payment">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                <input type="hidden" name="seats" value="<?php echo implode(',', $seatIds); ?>">
                <button type="submit" class="btn-pay" style="width:100%;">💳 Thanh Toán</button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// ============================================================
// 4: THANH TOÁN
// ============================================================
if ($action === 'payment') {
    $selected_showtime = array_filter($showtimes, fn($s) => $s['id'] == $showtime_id && $s['movie_id'] == $movie_id);
    $selected_showtime = reset($selected_showtime);
    $seats = isset($_GET['seats']) ? explode(',', $_GET['seats']) : [];
    $seatCount = count($seats);
    $totalPrice = $seatCount * ($selected_showtime ? $selected_showtime['price'] : 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán</title>
    <link rel="stylesheet" href="/movie-ticket-booking/css/payment.css">
</head>
<body>
    <div class="payment-container">
        <h1>💳 Thanh Toán</h1>

        <div class="total-amount">
            <p class="label">Số tiền cần thanh toán</p>
            <div class="amount"><?php echo number_format($totalPrice, 0, '.', '.'); ?> VNĐ</div>
            <p class="note">🎫 <?php echo $seatCount; ?> vé</p>
        </div>

        <p class="method-title">Chọn phương thức thanh toán:</p>
        <div class="payment-methods">
            <form action="booking.php" method="GET" style="width:100%;">
                <input type="hidden" name="action" value="success">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                <input type="hidden" name="seats" value="<?php echo implode(',', $seats); ?>">
                <button type="submit" class="payment-btn" style="width:100%;">
                    <span class="icon">🏦</span> Chuyển khoản ngân hàng
                </button>
            </form>
            <form action="booking.php" method="GET" style="width:100%;">
                <input type="hidden" name="action" value="success">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                <input type="hidden" name="seats" value="<?php echo implode(',', $seats); ?>">
                <button type="submit" class="payment-btn" style="width:100%;">
                    <span class="icon">💳</span> Thẻ tín dụng / Thẻ ghi nợ
                </button>
            </form>
            <form action="booking.php" method="GET" style="width:100%;">
                <input type="hidden" name="action" value="success">
                <input type="hidden" name="movie_id" value="<?php echo $movie_id; ?>">
                <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                <input type="hidden" name="seats" value="<?php echo implode(',', $seats); ?>">
                <button type="submit" class="payment-btn" style="width:100%;">
                    <span class="icon">📱</span> Ví điện tử (MoMo, ZaloPay)
                </button>
            </form>
        </div>

        <button class="btn-back" onclick="history.back()">← Quay lại</button>
        <p class="secure-note">🔒 Thanh toán an toàn và bảo mật</p>
    </div>
</body>
</html>
<?php
    exit;
}

// ============================================================
// 5: THÀNH CÔNG
// ============================================================
if ($action === 'success') {
    $selected_showtime = array_filter($showtimes, fn($s) => $s['id'] == $showtime_id && $s['movie_id'] == $movie_id);
    $selected_showtime = reset($selected_showtime);
    $seats = isset($_GET['seats']) ? explode(',', $_GET['seats']) : [];
    $seatCount = count($seats);
    $totalPrice = $seatCount * ($selected_showtime ? $selected_showtime['price'] : 0);
    $bookingId = 'BK' . date('Ymd') . rand(1000, 9999);
    
    // Lấy tên ghế
    $seatNames = [];
    if (!empty($seats)) {
        $ids = implode(',', array_map('intval', $seats));
        $sql = "SELECT CONCAT(seat_row, seat_number) as seat_name FROM seats WHERE id IN ($ids)";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $seatNames[] = $row['seat_name'];
        }
    }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Vé Thành Công!</title>
    <link rel="stylesheet" href="/movie-ticket-booking/css/success.css">
</head>
<body>
    <div class="success-container">
        <div class="success-icon">🎉</div>
        <h1>Đặt Vé Thành Công!</h1>
        <p class="subtitle">Cảm ơn bạn đã đặt vé tại rạp chiếu phim của chúng tôi</p>

        <div class="booking-code">🎫 <?php echo $bookingId; ?></div>

        <div class="ticket-box">
            <div class="row"><span class="label">🎥 Phim</span><span class="value"><?php echo htmlspecialchars($movie['title'] ?? 'Phim'); ?></span></div>
            <div class="row"><span class="label">📅 Ngày chiếu</span><span class="value"><?php echo date('d/m/Y', strtotime($selected_showtime['show_date'])); ?></span></div>
            <div class="row"><span class="label">⏰ Suất chiếu</span><span class="value"><?php echo htmlspecialchars($selected_showtime['time']); ?></span></div>
            <div class="row"><span class="label">🏢 Phòng</span><span class="value">Phòng <?php echo $selected_showtime['room']; ?></span></div>
            <div class="row"><span class="label">🪑 Ghế</span><span class="value"><?php echo implode(', ', $seatNames); ?></span></div>
            <div class="row"><span class="label">🎫 Số vé</span><span class="value"><?php echo $seatCount; ?></span></div>
            <div class="row"><span class="label">💰 Tổng tiền</span><span class="value" style="color:#4CAF50;font-weight:700;"><?php echo number_format($totalPrice, 0, '.', '.'); ?> VNĐ</span></div>
        </div>

        <a href="index.php" class="btn-home">🏠 Về Trang Chủ</a>
        <p class="note">📧 Mã vé đã được gửi đến email của bạn</p>
    </div>
</body>
</html>
<?php
    exit;
}

// ==================== DEFAULT ====================
header('Location: booking.php?action=showtime');
exit;
?>
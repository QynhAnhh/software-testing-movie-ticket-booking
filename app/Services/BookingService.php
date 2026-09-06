<?php
namespace App\Services;

use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;
use App\Models\VoucherModel;

class BookingService {
    private $bookingModel;
    private $showtimeModel;
    private $seatModel;
    private $ticketModel;
    private $voucherModel;

    public function __construct() {
        $this->bookingModel = new BookingModel();
        $this->showtimeModel = new ShowtimeModel();
        $this->seatModel = new SeatModel();
        $this->ticketModel = new TicketModel();
        $this->voucherModel = new VoucherModel();
    }

    // process
    public function processBooking($userId, $showtimeId, $seatIds, $paymentMethod, $voucherCode = '') {
        if ($userId <= 0) {
            return ['status' => 'error', 'message' => 'Vui lòng đăng nhập để đặt vé.', 'page' => 'login.php'];
        }

        if ($showtimeId <= 0) {
            return ['status' => 'error', 'message' => 'Suất chiếu không hợp lệ.'];
        }

        if (empty($seatIds) || !is_array($seatIds)) {
            return ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 ghế.'];
        }

        // Chỉ nhận đúng các phương thức có trong ENUM của bảng bookings.
        $allowedPaymentMethods = ['momo', 'vnpay', 'bank_transfer'];

        if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
            return ['status' => 'error', 'message' => 'Phương thức thanh toán không hợp lệ.'];
        }

        $voucherCode = strtoupper(trim((string)$voucherCode));

        $showtime = $this->showtimeModel->getDetailById($showtimeId);

        if (!$showtime || ($showtime['status'] ?? '') !== 'active') {
            return ['status' => 'error', 'message' => 'Suất chiếu không khả dụng.'];
        }

        $showDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $showtime['show_date'] . ' ' . $showtime['start_time']);
        if ($showDateTime && $showDateTime <= new \DateTime()) {
            return ['status' => 'error', 'message' => 'Suất chiếu này đã bắt đầu hoặc đã kết thúc.'];
        }

        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));

        $selectedSeats = $this->seatModel->getByIds($seatIds);

        if (count($selectedSeats) !== count($seatIds)) {
            return ['status' => 'error', 'message' => 'Danh sách ghế không hợp lệ.'];
        }

        $seatPrices = [];
        $totalPrice = 0;

        foreach ($seatIds as $seatId) {
            $seat = null;

            foreach ($selectedSeats as $item) {
                if ((int)$item['id'] === (int)$seatId) {
                    $seat = $item;
                    break;
                }
            }

            if (!$seat) {
                return ['status' => 'error', 'message' => 'Ghế không hợp lệ.'];
            }

            if ((int)$seat['room_id'] !== (int)$showtime['room_id']) {
                return ['status' => 'error', 'message' => 'Ghế không thuộc phòng chiếu này.'];
            }

            if ((int)$seat['is_active'] !== 1) {
                return ['status' => 'error', 'message' => 'Có ghế không khả dụng.'];
            }

            if ($this->ticketModel->isSeatBooked($showtimeId, $seatId)) {
                return ['status' => 'error', 'message' => 'Có ghế vừa được đặt. Vui lòng chọn ghế khác.'];
            }

            $price = (float)$showtime['base_price'] + (float)($seat['seat_type_price'] ?? 0);

            $seatPrices[] = [
                'seat_id' => $seatId,
                'price' => $price
            ];

            $totalPrice += $price;
        }

        // Voucher được kiểm tra ở server. JavaScript chỉ dùng để hiển thị nhanh;
        // quyết định cuối cùng luôn lấy từ database để tránh gian lận/sử dụng quá số lượt.
        $discountAmount = 0;
        $voucher = null;

        if ($voucherCode !== '') {
            $voucher = $this->voucherModel->getActiveByCode($voucherCode, $userId);
            if (!$voucher || ($voucher['status'] ?? '') !== 'active') {
                return ['status' => 'error', 'message' => 'Mã giảm giá không tồn tại hoặc đã bị tắt.'];
            }
            if ((int)$voucher['used_quantity'] >= (int)$voucher['total_quantity']) {
                return ['status' => 'error', 'message' => 'Mã giảm giá đã hết lượt sử dụng.'];
            }
            if (!empty($voucher['valid_from']) && strtotime($voucher['valid_from']) > time()) {
                return ['status' => 'error', 'message' => 'Mã giảm giá chưa đến thời gian sử dụng.'];
            }
            if (!empty($voucher['expires_at']) && strtotime($voucher['expires_at']) < time()) {
                return ['status' => 'error', 'message' => 'Mã giảm giá đã hết hạn.'];
            }
            if ((int)$voucher['user_used'] === 1) {
                return ['status' => 'error', 'message' => 'Tài khoản của bạn đã sử dụng mã này trước đó.'];
            }
            if ($totalPrice < (float)$voucher['min_order_amount']) {
                return ['status' => 'error', 'message' => 'Đơn hàng chưa đạt tối thiểu ' . number_format((float)$voucher['min_order_amount'], 0, ',', '.') . 'đ để dùng mã ' . $voucherCode . '.'];
            }
            $discountAmount = min((float)$voucher['discount_amount'], $totalPrice);
        }

        $finalTotal = max(0, $totalPrice - $discountAmount);

        $this->bookingModel->beginTransaction();

        try {
            // Khóa dòng voucher trong transaction để 2 người không thể cùng lấy lượt cuối.
            if ($voucherCode !== '') {
                $voucher = $this->voucherModel->getByCodeForUpdate($voucherCode);
                if (!$voucher || ($voucher['status'] ?? '') !== 'active') {
                    throw new \Exception('Mã giảm giá không tồn tại hoặc đã bị tắt.');
                }
                if ((int)$voucher['used_quantity'] >= (int)$voucher['total_quantity']) {
                    throw new \Exception('Mã giảm giá đã hết lượt sử dụng.');
                }
                if (!empty($voucher['valid_from']) && strtotime($voucher['valid_from']) > time()) {
                    throw new \Exception('Mã giảm giá chưa đến thời gian sử dụng.');
                }
                if (!empty($voucher['expires_at']) && strtotime($voucher['expires_at']) < time()) {
                    throw new \Exception('Mã giảm giá đã hết hạn.');
                }
                if ($this->voucherModel->hasUserUsed((int)$voucher['id'], $userId)) {
                    throw new \Exception('Tài khoản của bạn đã sử dụng mã này trước đó.');
                }
                if ($totalPrice < (float)$voucher['min_order_amount']) {
                    throw new \Exception('Đơn hàng chưa đạt tối thiểu ' . number_format((float)$voucher['min_order_amount'], 0, ',', '.') . 'đ để dùng mã ' . $voucherCode . '.');
                }
                $discountAmount = min((float)$voucher['discount_amount'], $totalPrice);
                $finalTotal = max(0, $totalPrice - $discountAmount);
            }

            $bookingId = $this->bookingModel->createBooking($userId, $finalTotal, $paymentMethod);

            if (!$bookingId) {
                throw new \Exception('Không thể tạo booking: ' . $this->bookingModel->getError());
            }

            $ticketsCreated = $this->ticketModel->createMany($bookingId, $showtimeId, $seatPrices);

            if (!$ticketsCreated) {
                throw new \Exception('Không thể tạo vé: ' . $this->ticketModel->getError());
            }

            if ($voucherCode !== '') {
                if (!$this->voucherModel->consume((int)$voucher['id'], $userId, $bookingId, $discountAmount)) {
                    throw new \Exception('Không thể ghi nhận lượt sử dụng voucher: ' . $this->voucherModel->getError());
                }
            }

            $this->bookingModel->commit();

            return [
                'status' => 'success',
                'message' => 'Đặt vé thành công!',
                'booking_id' => $bookingId
            ];
        } catch (\Exception $e) {
            $this->bookingModel->rollback();

            return [
                'status' => 'error',
                'message' => $e->getMessage() ?: 'Có lỗi xảy ra khi đặt vé.'
            ];
        }
    }

    public function getUserBookings($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return [];
        }
        return $this->bookingModel->getBookingsByUser($userId);
    }

    public function cancelBooking($userId, $bookingId) {
        $userId = (int)$userId;
        $bookingId = (int)$bookingId;

        if ($userId <= 0) {
            return ['status' => 'error', 'message' => 'Vui long dang nhap de huy ve.'];
        }

        if ($bookingId <= 0) {
            return ['status' => 'error', 'message' => 'Booking khong hop le.'];
        }

        $booking = $this->bookingModel->getByIdAndUser($bookingId, $userId);
        if (!$booking) {
            return ['status' => 'error', 'message' => 'Khong tim thay booking can huy.'];
        }

        if (($booking['status'] ?? '') === 'canceled') {
            return ['status' => 'error', 'message' => 'Booking nay da duoc huy truoc do.'];
        }

        $showtime = $this->bookingModel->getPrimaryShowtimeByBookingId($bookingId);
        if ($showtime && !empty($showtime['show_date']) && !empty($showtime['start_time'])) {
            $showDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $showtime['show_date'] . ' ' . $showtime['start_time']);
            if ($showDateTime && $showDateTime <= new \DateTime()) {
                return ['status' => 'error', 'message' => 'Khong the huy ve khi suat chieu da bat dau.'];
            }
        }

        $this->bookingModel->beginTransaction();

        try {
            if (!$this->bookingModel->cancelBooking($bookingId, $userId)) {
                throw new \Exception('Loi khi huy booking: ' . $this->bookingModel->getError());
            }

            // Nếu booking có voucher và bị hủy, trả lại 1 lượt cho voucher.
            if (!$this->voucherModel->releaseByBookingId($bookingId)) {
                throw new \Exception('Không thể hoàn lại lượt sử dụng voucher.');
            }

            $this->bookingModel->commit();
            return ['status' => 'success', 'message' => 'Huy ve thanh cong.'];
        } catch (\Exception $e) {
            $this->bookingModel->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getAdminBookingStats() {
        return $this->bookingModel->getAdminBookingStats();
    }

    public function getAdminBookings($input) {
        return $this->bookingModel->getAdminBookings($this->normalizeAdminFilters($input));
    }

    public function getAdminBookingDetail($bookingId) {
        $bookingId = (int)$bookingId;
        if ($bookingId <= 0) {
            return null;
        }

        $booking = $this->bookingModel->getAdminBookingDetail($bookingId);
        if (!$booking) {
            return null;
        }

        return [
            'booking' => $booking,
            'tickets' => $this->bookingModel->getAdminBookingTickets($bookingId)
        ];
    }

    public function updateAdminBookingStatus($bookingId, $status) {
        $bookingId = (int)$bookingId;
        $status = trim((string)$status);
        $allowedStatuses = ['pending', 'paid', 'canceled'];

        if ($bookingId <= 0) {
            return ['status' => 'error', 'message' => 'Booking không hợp lệ.'];
        }

        if (!in_array($status, $allowedStatuses, true)) {
            return ['status' => 'error', 'message' => 'Trạng thái booking không hợp lệ.'];
        }

        $booking = $this->bookingModel->getAdminBookingById($bookingId);
        if (!$booking) {
            return ['status' => 'error', 'message' => 'Không tìm thấy booking cần cập nhật.'];
        }

        if (($booking['status'] ?? '') === 'canceled' && $status !== 'canceled') {
            if ($this->bookingModel->hasSeatConflictWhenRestoring($bookingId)) {
                return [
                    'status' => 'error',
                    'message' => 'Không thể khôi phục booking vì có ghế đã được đặt bởi booking khác.'
                ];
            }
        }

        $ticketStatus = $status === 'canceled' ? 'canceled' : 'booked';

        $this->bookingModel->beginTransaction();

        try {
            if (!$this->bookingModel->updateBookingStatus($bookingId, $status)) {
                throw new \Exception('Lỗi khi cập nhật trạng thái booking: ' . $this->bookingModel->getError());
            }

            if (!$this->bookingModel->updateTicketsStatusByBooking($bookingId, $ticketStatus)) {
                throw new \Exception('Lỗi khi cập nhật trạng thái vé: ' . $this->bookingModel->getError());
            }

            $this->bookingModel->commit();
            return ['status' => 'success', 'message' => 'Cập nhật trạng thái booking thành công.'];
        } catch (\Exception $e) {
            $this->bookingModel->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function deleteAdminBooking($bookingId) {
        $bookingId = (int)$bookingId;

        if ($bookingId <= 0) {
            return ['status' => 'error', 'message' => 'Booking không hợp lệ.'];
        }

        $booking = $this->bookingModel->getAdminBookingById($bookingId);
        if (!$booking) {
            return ['status' => 'error', 'message' => 'Không tìm thấy booking cần xóa.'];
        }

        $this->bookingModel->beginTransaction();

        try {
            if (!$this->bookingModel->deleteBooking($bookingId)) {
                throw new \Exception('Lỗi khi xóa booking: ' . $this->bookingModel->getError());
            }

            $this->bookingModel->commit();
            return ['status' => 'success', 'message' => 'Xóa booking thành công.'];
        } catch (\Exception $e) {
            $this->bookingModel->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function normalizeAdminFilters($input) {
        $allowedStatuses = ['pending', 'paid', 'canceled'];
        $filters = [
            'status' => '',
            'from_date' => '',
            'to_date' => '',
            'search' => ''
        ];

        $status = trim((string)($input['status'] ?? ''));
        if (in_array($status, $allowedStatuses, true)) {
            $filters['status'] = $status;
        }

        $fromDate = trim((string)($input['from_date'] ?? ''));
        if ($this->isValidDate($fromDate)) {
            $filters['from_date'] = $fromDate;
        }

        $toDate = trim((string)($input['to_date'] ?? ''));
        if ($this->isValidDate($toDate)) {
            $filters['to_date'] = $toDate;
        }

        $filters['search'] = trim((string)($input['search'] ?? ''));

        return $filters;
    }

    public function getTotalSpentByUser($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return 0;
        }
        return $this->bookingModel->getTotalSpentByUser($userId);
    }

    private function isValidDate($date) {
        if ($date === '') {
            return false;
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }
}

<?php
namespace App\Services;

use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;

class BookingService {
    private $bookingModel;
    private $showtimeModel;
    private $seatModel;
    private $ticketModel;

    public function __construct() {
        $this->bookingModel = new BookingModel();
        $this->showtimeModel = new ShowtimeModel();
        $this->seatModel = new SeatModel();
        $this->ticketModel = new TicketModel();
    }

    // process
    public function processBooking($userId, $showtimeId, $seatIds, $paymentMethod) {
        if ($userId <= 0) {
            return ['status' => 'error', 'message' => 'Vui lòng đăng nhập để đặt vé.'];
        }

        if ($showtimeId <= 0) {
            return ['status' => 'error', 'message' => 'Suất chiếu không hợp lệ.'];
        }

        if (empty($seatIds) || !is_array($seatIds)) {
            return ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 ghế.'];
        }

        $allowedPaymentMethods = ['cash', 'momo', 'vnpay', 'bank_transfer'];

        if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
            $paymentMethod = 'cash';
        }

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

        $this->bookingModel->beginTransaction();

        try {
            $bookingId = $this->bookingModel->createBooking($userId, $totalPrice, $paymentMethod);

            if (!$bookingId) {
                throw new \Exception('Không thể tạo booking.');
            }

            $ticketsCreated = $this->ticketModel->createMany($bookingId, $showtimeId, $seatPrices);

            if (!$ticketsCreated) {
                throw new \Exception('Không thể tạo vé.');
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
                'message' => 'Có lỗi xảy ra khi đặt vé.'
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

            $this->bookingModel->commit();
            return ['status' => 'success', 'message' => 'Huy ve thanh cong.'];
        } catch (\Exception $e) {
            $this->bookingModel->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getTotalSpentByUser($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return 0;
        }
        return $this->bookingModel->getTotalSpentByUser($userId);
    }
}

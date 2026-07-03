<?php
namespace App\Services;

use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;

class BookingService {
    private $bookingModel;
    private $showtimeModel;
    private $seatModel;

    public function __construct() {
        $this->bookingModel = new BookingModel();
        $this->showtimeModel = new ShowtimeModel();
        $this->seatModel = new SeatModel();
    }

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

        $showtime = $this->showtimeModel->getShowtimeDetails($showtimeId);

        if (!$showtime || ($showtime['status'] ?? '') !== 'active') {
            return ['status' => 'error', 'message' => 'Suất chiếu hiện không khả dụng.'];
        }

        $seatIds = array_values(array_unique(array_map('intval', $seatIds)));
        $selectedSeats = $this->seatModel->getByIds($seatIds);

        if (count($selectedSeats) !== count($seatIds)) {
            return ['status' => 'error', 'message' => 'Danh sách ghế không hợp lệ.'];
        }

        $prices = [];
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
                return ['status' => 'error', 'message' => 'Ghế không thuộc phòng chiếu của suất chiếu này.'];
            }

            if ((int)$seat['is_active'] !== 1) {
                return ['status' => 'error', 'message' => 'Có ghế đang không khả dụng.'];
            }

            if ($this->bookingModel->isSeatBooked($showtimeId, $seatId)) {
                return ['status' => 'error', 'message' => 'Có ghế vừa được người khác đặt. Vui lòng chọn ghế khác.'];
            }

            $ticketPrice = (float)$showtime['base_price'] + (float)$seat['seat_type_price'];
            $prices[] = $ticketPrice;
            $totalPrice += $ticketPrice;
        }

        $this->bookingModel->beginTransaction();

        try {
            $bookingId = $this->bookingModel->createBooking($userId, $totalPrice, $paymentMethod);

            if (!$bookingId) {
                throw new \Exception('Không thể tạo booking.');
            }

            if (!$this->bookingModel->createTickets($bookingId, $showtimeId, $seatIds, $prices)) {
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
                'message' => 'Có lỗi xảy ra khi đặt vé: ' . $e->getMessage()
            ];
        }
    }

    public function getUserBookings($userId) {
        if ($userId <= 0) {
            return [];
        }

        return $this->bookingModel->getBookingsByUser($userId);
    }
}
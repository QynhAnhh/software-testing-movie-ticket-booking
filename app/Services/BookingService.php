<?php
namespace App\Services;

use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;

class BookingService {
    private $bookingModel;

    public function __construct() {
        $this->bookingModel = new BookingModel();
    }

    public function processBooking($userId, $showtimeId, $seatIds, $paymentMethod) {
        if (empty($seatIds)) {
            return ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 ghế.'];
        }

        // Get showtime details to get base_price and room_id
        $showtimeModel = new ShowtimeModel();
        $showtime = $showtimeModel->getShowtimeDetails($showtimeId);
        
        if (!$showtime) {
            return ['status' => 'error', 'message' => 'Lịch chiếu không hợp lệ.'];
        }
        
        $basePrice = $showtime['base_price'] ?? 0;

        // Get seat details to calculate extra price
        $seatModel = new SeatModel();
        $allSeats = $seatModel->getByRoom($showtime['room_id']);
        
        $totalPrice = 0;
        $prices = [];
        foreach ($seatIds as $seatId) {
            $seatPrice = $basePrice;
            foreach ($allSeats as $seat) {
                if ($seat['id'] == $seatId) {
                    $seatPrice += $seat['base_price_extra'] ?? $seat['seat_type_price'] ?? 0;
                    break;
                }
            }
            $prices[] = $seatPrice;
            $totalPrice += $seatPrice;
        }

        // Tạo Booking
        $bookingId = $this->bookingModel->createBooking($userId, $totalPrice, $paymentMethod);
        if ($bookingId) {
            $success = $this->bookingModel->createTickets($bookingId, $showtimeId, $seatIds, $prices);
            if ($success) {
                return ['status' => 'success', 'message' => 'Đặt vé thành công!', 'booking_id' => $bookingId];
            }
        }
        return ['status' => 'error', 'message' => 'Có lỗi xảy ra khi đặt vé.'];
    }

    public function getUserBookings($userId) {
        return $this->bookingModel->getBookingsByUser($userId);
    }

    public function cancelBooking($bookingId, $userId) {
        $success = $this->bookingModel->cancelBooking($bookingId, $userId);
        if ($success) {
            return ['status' => 'success', 'message' => 'Hủy vé thành công!'];
        }
        return ['status' => 'error', 'message' => 'Không thể hủy vé. Vé có thể đã bị hủy hoặc bạn không có quyền.'];
    }
}

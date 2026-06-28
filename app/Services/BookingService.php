<?php
namespace App\Services;

use App\Models\BookingModel;

class BookingService {
    private $bookingModel;

    public function __construct() {
        $this->bookingModel = new BookingModel();
    }

    // TODO: Viết logic xử lý đặt vé
    public function processBooking($userId, $scheduleId, $seatIds) {
        // 1. Validate dữ liệu
        // 2. Check ghế trống (gọi SeatModel)
        // 3. Tính tiền
        // 4. Lưu Booking (gọi BookingModel)
    }

    // TODO: Lấy lịch sử đặt vé
    public function getUserBookings($userId) {
        return $this->bookingModel->getBookingsByUser($userId);
    }
}

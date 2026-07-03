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

    public function processBooking($userId, $showtimeId, $seatIds, $paymentMethod) {
        $userId = (int)$userId;
        $showtimeId = (int)$showtimeId;
        $seatIds = array_values(array_unique(array_map('intval', (array)$seatIds)));
        $seatIds = array_filter($seatIds, function ($seatId) {
            return $seatId > 0;
        });

        if ($userId <= 0) {
            return ['status' => 'error', 'message' => 'Vui lòng đăng nhập để đặt vé!'];
        }

        $showtime = $this->showtimeModel->getDetailById($showtimeId);
        if (!$showtime || $showtime['status'] !== 'active') {
            return ['status' => 'error', 'message' => 'Suất chiếu không hợp lệ hoặc đã bị hủy!'];
        }

        if (empty($seatIds)) {
            return ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất một ghế!'];
        }

        if (!in_array($paymentMethod, ['cash', 'momo', 'vnpay', 'bank_transfer'], true)) {
            return ['status' => 'error', 'message' => 'Phương thức thanh toán không hợp lệ!'];
        }

        $roomSeats = $this->seatModel->getByRoomIdWithType($showtime['room_id']);
        $seatMap = [];
        foreach ($roomSeats as $seat) {
            $seatMap[(int)$seat['seat_id']] = $seat;
        }

        $bookedSeatIds = $this->ticketModel->getBookedSeatIdsByShowtimeId($showtimeId);
        $seatPrices = [];
        $totalPrice = 0;

        foreach ($seatIds as $seatId) {
            if (!isset($seatMap[$seatId])) {
                return ['status' => 'error', 'message' => 'Ghế đã chọn không thuộc phòng chiếu này!'];
            }

            $seat = $seatMap[$seatId];
            if (!(bool)$seat['is_active']) {
                return ['status' => 'error', 'message' => "Ghế {$seat['seat_row']}{$seat['seat_number']} hiện không hoạt động!"];
            }

            if (in_array($seatId, $bookedSeatIds, true)) {
                return ['status' => 'error', 'message' => "Ghế {$seat['seat_row']}{$seat['seat_number']} đã được đặt!"];
            }

            $price = (float)$showtime['base_price'] + (float)$seat['seat_type_price'];
            $seatPrices[] = [
                'seat_id' => $seatId,
                'price' => $price
            ];
            $totalPrice += $price;
        }

        $bookingId = $this->bookingModel->createBooking([
            'user_id' => $userId,
            'total_price' => $totalPrice,
            'payment_method' => $paymentMethod,
            'status' => $paymentMethod === 'cash' ? 'pending' : 'paid'
        ]);

        if (!$bookingId) {
            return ['status' => 'error', 'message' => 'Lỗi khi tạo booking: ' . $this->bookingModel->getError()];
        }

        if (!$this->ticketModel->createMany($bookingId, $showtimeId, $seatPrices)) {
            return ['status' => 'error', 'message' => 'Booking đã tạo nhưng lỗi khi tạo vé: ' . $this->ticketModel->getError()];
        }

        return [
            'status' => 'success',
            'message' => 'Đặt vé thành công!',
            'booking_id' => $bookingId
        ];
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
            return ['status' => 'error', 'message' => 'Vui lòng đăng nhập để hủy vé!'];
        }

        if ($bookingId <= 0) {
            return ['status' => 'error', 'message' => 'Booking không hợp lệ!'];
        }

        $booking = $this->bookingModel->getByIdAndUser($bookingId, $userId);
        if (!$booking) {
            return ['status' => 'error', 'message' => 'Không tìm thấy booking cần hủy!'];
        }

        if ($booking['status'] === 'canceled') {
            return ['status' => 'error', 'message' => 'Booking này đã được hủy trước đó!'];
        }

        if (!$this->bookingModel->cancelBookingForUser($bookingId, $userId)) {
            return ['status' => 'error', 'message' => 'Lỗi khi hủy booking: ' . $this->bookingModel->getError()];
        }

        return ['status' => 'success', 'message' => 'Hủy vé thành công!'];
    }

    public function getTotalSpentByUser($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return 0;
        }
        return $this->bookingModel->getTotalSpentByUser($userId);
    }
}

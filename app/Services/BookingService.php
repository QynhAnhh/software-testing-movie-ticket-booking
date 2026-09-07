<?php
namespace App\Services;

use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;
use App\Services\Traits\BookingValidationTrait;

class BookingService {
    use BookingValidationTrait;

    private $bookingModel;
    private $showtimeModel;
    private $seatModel;
    private $ticketModel;

    // Sửa constructor để hỗ trợ Dependency Injection cho Unit Test
    public function __construct(
        $bookingModel = null,
        $showtimeModel = null,
        $seatModel = null,
        $ticketModel = null
    ) {
        $this->bookingModel = $bookingModel ?? new BookingModel();
        $this->showtimeModel = $showtimeModel ?? new ShowtimeModel();
        $this->seatModel = $seatModel ?? new SeatModel();
        $this->ticketModel = $ticketModel ?? new TicketModel();
    }

    public function processBooking($userId, $showtimeId, $seatIds, $paymentMethod) {
        try {
            return $this->createBooking($userId, $showtimeId, $seatIds, $paymentMethod);
        } catch (\InvalidArgumentException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function createBooking($userId, $showtimeId, $seatIds, $paymentMethod) {
        if (!is_array($seatIds) || count($seatIds) === 0) {
            throw new \InvalidArgumentException('Vui lòng chọn ít nhất 1 ghế');
        }

        $this->validateBookingRequest($userId, $showtimeId, $seatIds);
        $seatIds = $this->normalizeSeatIds($seatIds);
        $this->validateBookingSeats($seatIds);
        $paymentMethod = $this->normalizePaymentMethod($paymentMethod);
        $showtime = $this->getAvailableShowtime($showtimeId);

        $this->bookingModel->beginTransaction();

        try {
            [$seatPrices, $totalPrice] = $this->processSeatLocking($seatIds, $showtime, $showtimeId);
            $bookingId = $this->handleDatabaseInsertion(
                $userId,
                $showtimeId,
                $paymentMethod,
                $totalPrice,
                $seatPrices
            );
            $this->bookingModel->commit();

            return [
                'status' => 'success',
                'message' => 'Đặt vé thành công!',
                'booking_id' => $bookingId
            ];
        } catch (\Throwable $e) {
            $this->bookingModel->rollback();
            throw $e;
        }
    }

    private function processSeatLocking(array $seatIds, array $showtime, $showtimeId): array {
        $selectedSeats = $this->seatModel->getByIds($seatIds);
        if (count($selectedSeats) !== count($seatIds)) {
            throw new \InvalidArgumentException('Danh sách ghế không hợp lệ.');
        }

        $seatPrices = [];
        $totalPrice = 0;
        foreach ($seatIds as $seatId) {
            $seat = $this->findSeat($selectedSeats, $seatId);
            $this->validateSeatForShowtime($seat, $showtime, $showtimeId, $seatId);

            $price = (float)$showtime['base_price'] + (float)($seat['seat_type_price'] ?? 0);
            $seatPrices[] = ['seat_id' => $seatId, 'price' => $price];
            $totalPrice += $price;
        }

        return [$seatPrices, $totalPrice];
    }

    private function handleDatabaseInsertion($userId, $showtimeId, $paymentMethod, $totalPrice, array $seatPrices) {
        $bookingId = $this->bookingModel->createBooking($userId, $totalPrice, $paymentMethod);
        if (!$bookingId) {
            throw new \RuntimeException('Không thể tạo booking.');
        }

        if (!$this->ticketModel->createMany($bookingId, $showtimeId, $seatPrices)) {
            throw new \RuntimeException('Không thể tạo vé.');
        }

        return $bookingId;
    }

    public function getUserBookings($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return [];
        }
        return $this->bookingModel->getBookingsByUser($userId);
    }

    private function validateBookingSeats(array $seatIds): void {
        if (empty($seatIds)) {
            throw new \InvalidArgumentException('Vui lòng chọn ít nhất một ghế');
        }

        if (count($seatIds) > 10) {
            throw new \InvalidArgumentException('Bạn chỉ được đặt tối đa 10 ghế cho mỗi giao dịch.');
        }
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
            } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
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
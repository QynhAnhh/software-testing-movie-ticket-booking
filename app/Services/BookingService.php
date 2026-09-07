<?php
namespace App\Services;

use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;

/**
 * Custom Dedicated Exceptions cho BookingService
 * Giải quyết dứt điểm quy tắc SonarCloud S112 (Generic Exceptions)
 */
class BookingValidationException extends \InvalidArgumentException {}
class BookingRuntimeException extends \RuntimeException {}

class BookingService {
    private $bookingModel;
    private $showtimeModel;
    private $seatModel;
    private $ticketModel;

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

    // ==========================================
    // 1. QUẢN LÝ ĐẶT VÉ (USER BOOKING)
    // ==========================================

    public function processBooking($userId, $showtimeId, $seatIds, $paymentMethod) {
        $validation = $this->validateBookingRequest($userId, $showtimeId, $seatIds, $paymentMethod);
        $result = $validation['error'];

        if ($result === null) {
            $result = $this->createBookingTransaction($validation['data']);
        }

        return $result;
    }

    private function validateBookingRequest($userId, $showtimeId, $seatIds, $paymentMethod) {
        $result = [
            'error' => null,
            'data' => null
        ];

        do {
            if ($userId <= 0) {
                $result['error'] = [
                    'status' => 'error',
                    'message' => 'Vui lòng đăng nhập để đặt vé.',
                    'page' => 'login.php'
                ];
                break;
            }

            if ($showtimeId <= 0) {
                $result['error'] = ['status' => 'error', 'message' => 'Suất chiếu không hợp lệ.'];
                break;
            }

            if (!is_array($seatIds) || empty($seatIds)) {
                $result['error'] = ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 ghế'];
                break;
            }

            if (count($seatIds) > 10) {
                $result['error'] = ['status' => 'error', 'message' => 'Bạn chỉ được đặt tối đa 10 ghế cho mỗi giao dịch.'];
                break;
            }

            $showtime = $this->showtimeModel->getDetailById($showtimeId);
            if (!$showtime || ($showtime['status'] ?? '') !== 'active') {
                $result['error'] = ['status' => 'error', 'message' => 'Suất chiếu không khả dụng.'];
                break;
            }

            if ($this->showtimeHasStarted($showtime)) {
                $result['error'] = ['status' => 'error', 'message' => 'Suất chiếu này đã bắt đầu hoặc đã kết thúc.'];
                break;
            }

            $allowedPaymentMethods = ['cash', 'momo', 'vnpay', 'bank_transfer'];
            $normalizedPaymentMethod = in_array($paymentMethod, $allowedPaymentMethods, true)
                ? $paymentMethod
                : 'cash';

            $result['data'] = [
                'user_id' => $userId,
                'showtime_id' => $showtimeId,
                'seat_ids' => array_values(array_unique(array_map('intval', $seatIds))),
                'payment_method' => $normalizedPaymentMethod,
                'showtime' => $showtime
            ];
        } while (false);

        return $result;
    }

    private function showtimeHasStarted(array $showtime) {
        $showDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $showtime['show_date'] . ' ' . $showtime['start_time']
        );

        return $showDateTime && $showDateTime <= new \DateTime();
    }

    private function createBookingTransaction(array $bookingData) {
        $result = null;
        $this->bookingModel->beginTransaction();

        try {
            $seatData = $this->prepareSeatData($bookingData);
            $bookingId = $this->bookingModel->createBooking(
                $bookingData['user_id'],
                $seatData['total_price'],
                $bookingData['payment_method']
            );

            if (!$bookingId) {
                throw new BookingRuntimeException('Không thể tạo booking.');
            }

            if (!$this->ticketModel->createMany($bookingId, $bookingData['showtime_id'], $seatData['seat_prices'])) {
                throw new BookingRuntimeException('Không thể tạo vé.');
            }

            $this->bookingModel->commit();
            $result = [
                'status' => 'success',
                'message' => 'Đặt vé thành công!',
                'booking_id' => $bookingId
            ];
        } catch (\InvalidArgumentException $e) {
            $this->bookingModel->rollback();
            $result = ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->bookingModel->rollback();
            $result = ['status' => 'error', 'message' => 'Có lỗi xảy ra khi đặt vé.'];
        }

        return $result;
    }

    private function prepareSeatData(array $bookingData) {
        $selectedSeats = $this->seatModel->getByIds($bookingData['seat_ids']);
        if (count($selectedSeats) !== count($bookingData['seat_ids'])) {
            throw new BookingValidationException('Danh sách ghế không hợp lệ.');
        }

        $seatPrices = [];
        $totalPrice = 0;
        foreach ($bookingData['seat_ids'] as $seatId) {
            $seat = $this->findSeat($selectedSeats, $seatId);
            $this->validateSeat($seat, $bookingData);
            $price = (float)$bookingData['showtime']['base_price'] + (float)($seat['seat_type_price'] ?? 0);
            $seatPrices[] = ['seat_id' => $seatId, 'price' => $price];
            $totalPrice += $price;
        }

        return ['seat_prices' => $seatPrices, 'total_price' => $totalPrice];
    }

    private function findSeat(array $selectedSeats, $seatId) {
        foreach ($selectedSeats as $seat) {
            if ((int)$seat['id'] === (int)$seatId) {
                return $seat;
            }
        }

        return null;
    }

    private function validateSeat($seat, array $bookingData) {
        if (!$seat) {
            throw new BookingValidationException('Ghế không hợp lệ.');
        }
        if ((int)$seat['room_id'] !== (int)$bookingData['showtime']['room_id']) {
            throw new BookingValidationException('Ghế không thuộc phòng chiếu này.');
        }
        if ((int)$seat['is_active'] !== 1) {
            throw new BookingValidationException('Có ghế không khả dụng.');
        }
        if ($this->ticketModel->isSeatBooked($bookingData['showtime_id'], $seat['id'])) {
            throw new BookingValidationException('Có ghế vừa được đặt. Vui lòng chọn ghế khác.');
        }
    }

    public function getUserBookings($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return [];
        }
        return $this->bookingModel->getBookingsByUser($userId);
    }

    public function getTotalSpentByUser($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return 0;
        }
        return $this->bookingModel->getTotalSpentByUser($userId);
    }

    // ==========================================
    // 2. HỦY VÉ (CANCEL BOOKING)
    // ==========================================

    public function cancelBooking($userId, $bookingId) {
        $userId = (int)$userId;
        $bookingId = (int)$bookingId;

        try {
            $this->validateCancelPreconditions($userId, $bookingId);

            $this->bookingModel->beginTransaction();
            if (!$this->bookingModel->cancelBooking($bookingId, $userId)) {
                throw new BookingRuntimeException('Loi khi huy booking: ' . $this->bookingModel->getError());
            }
            $this->bookingModel->commit();

            return ['status' => 'success', 'message' => 'Huy ve thanh cong.'];
        } catch (\InvalidArgumentException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->bookingModel->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function validateCancelPreconditions(int $userId, int $bookingId): void {
        if ($userId <= 0) {
            throw new BookingValidationException('Vui long dang nhap de huy ve.');
        }
        if ($bookingId <= 0) {
            throw new BookingValidationException('Booking khong hop le.');
        }
        $booking = $this->bookingModel->getByIdAndUser($bookingId, $userId);
        if (!$booking) {
            throw new BookingValidationException('Khong tim thay booking can huy.');
        }
        if (($booking['status'] ?? '') === 'canceled') {
            throw new BookingValidationException('Booking nay da duoc huy truoc do.');
        }
        $showtime = $this->bookingModel->getPrimaryShowtimeByBookingId($bookingId);
        if ($showtime && !empty($showtime['show_date']) && !empty($showtime['start_time']) && $this->showtimeHasStarted($showtime)) {
            throw new BookingValidationException('Khong the huy ve khi suat chieu da bat dau.');
        }
    }

    // ==========================================
    // 3. QUẢN TRỊ ADMIN (ADMIN OPERATIONS)
    // ==========================================

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

        try {
            $this->validateAdminUpdatePreconditions($bookingId, $status);

            $ticketStatus = $status === 'canceled' ? 'canceled' : 'booked';
            $this->bookingModel->beginTransaction();

            if (!$this->bookingModel->updateBookingStatus($bookingId, $status)) {
                throw new BookingRuntimeException('Lỗi khi cập nhật trạng thái booking: ' . $this->bookingModel->getError());
            }
            if (!$this->bookingModel->updateTicketsStatusByBooking($bookingId, $ticketStatus)) {
                throw new BookingRuntimeException('Lỗi khi cập nhật trạng thái vé: ' . $this->bookingModel->getError());
            }

            $this->bookingModel->commit();
            return ['status' => 'success', 'message' => 'Cập nhật trạng thái booking thành công.'];
        } catch (\InvalidArgumentException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->bookingModel->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function validateAdminUpdatePreconditions(int $bookingId, string $status): array {
        $allowedStatuses = ['pending', 'paid', 'canceled'];
        if ($bookingId <= 0) {
            throw new BookingValidationException('Booking không hợp lệ.');
        }
        if (!in_array($status, $allowedStatuses, true)) {
            throw new BookingValidationException('Trạng thái booking không hợp lệ.');
        }

        $booking = $this->bookingModel->getAdminBookingById($bookingId);
        if (!$booking) {
            throw new BookingValidationException('Không tìm thấy booking cần cập nhật.');
        }

        // Đã gộp câu lệnh if lồng nhau (SonarCloud S1066 - Merge collapsible IF)
        if (($booking['status'] ?? '') === 'canceled' && $status !== 'canceled' && $this->bookingModel->hasSeatConflictWhenRestoring($bookingId)) {
            throw new BookingValidationException('Không thể khôi phục booking vì có ghế đã được đặt bởi booking khác.');
        }

        return $booking;
    }

    public function deleteAdminBooking($bookingId) {
        $bookingId = (int)$bookingId;

        try {
            $this->validateAdminDeletePreconditions($bookingId);

            $this->bookingModel->beginTransaction();
            if (!$this->bookingModel->deleteBooking($bookingId)) {
                throw new BookingRuntimeException('Lỗi khi xóa booking: ' . $this->bookingModel->getError());
            }

            $this->bookingModel->commit();
            return ['status' => 'success', 'message' => 'Xóa booking thành công.'];
        } catch (\InvalidArgumentException $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->bookingModel->rollback();
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function validateAdminDeletePreconditions(int $bookingId): void {
        if ($bookingId <= 0) {
            throw new BookingValidationException('Booking không hợp lệ.');
        }

        $booking = $this->bookingModel->getAdminBookingById($bookingId);
        if (!$booking) {
            throw new BookingValidationException('Không tìm thấy booking cần xóa.');
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

    private function isValidDate($date) {
        if ($date === '') {
            return false;
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime && $dateTime->format('Y-m-d') === $date;
    }
}
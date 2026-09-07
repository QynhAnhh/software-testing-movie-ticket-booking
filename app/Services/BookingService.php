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

    // process
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
                $result['error'] = ['status' => 'error', 'message' => 'Vui lòng đăng nhập để đặt vé.', 'page' => 'login.php'];
                break;
            }
            if ($showtimeId <= 0) {
                $result['error'] = ['status' => 'error', 'message' => 'Suất chiếu không hợp lệ.'];
                break;
            }
            if (!is_array($seatIds) || count($seatIds) === 0) {
                $result['error'] = ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 ghế'];
                break;
            }

            $showtime = $this->showtimeModel->getDetailById($showtimeId);
            $normalizedSeatIds = array_values(array_unique(array_map('intval', $seatIds)));

            if (!$showtime || ($showtime['status'] ?? '') !== 'active') {
                $result['error'] = ['status' => 'error', 'message' => 'Suất chiếu không khả dụng.'];
                break;
            }
            if ($this->showtimeHasStarted($showtime)) {
                $result['error'] = ['status' => 'error', 'message' => 'Suất chiếu này đã bắt đầu hoặc đã kết thúc.'];
                break;
            }
            if (count($seatIds) > 10) {
                $result['error'] = ['status' => 'error', 'message' => 'Bạn chỉ được đặt tối đa 10 ghế cho mỗi giao dịch.'];
                break;
            }

            $allowedPaymentMethods = ['cash', 'momo', 'vnpay', 'bank_transfer'];
            $normalizedPaymentMethod = in_array($paymentMethod, $allowedPaymentMethods, true)
                ? $paymentMethod
                : 'cash';
            $result['data'] = [
                'user_id' => $userId,
                'showtime_id' => $showtimeId,
                'seat_ids' => $normalizedSeatIds,
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
                throw new \RuntimeException('Không thể tạo booking.');
            }

            if (!$this->ticketModel->createMany($bookingId, $bookingData['showtime_id'], $seatData['seat_prices'])) {
                throw new \RuntimeException('Không thể tạo vé.');
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
            throw new \InvalidArgumentException('Danh sách ghế không hợp lệ.');
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
            throw new \InvalidArgumentException('Ghế không hợp lệ.');
        }
        if ((int)$seat['room_id'] !== (int)$bookingData['showtime']['room_id']) {
            throw new \InvalidArgumentException('Ghế không thuộc phòng chiếu này.');
        }
        if ((int)$seat['is_active'] !== 1) {
            throw new \InvalidArgumentException('Có ghế không khả dụng.');
        }
        if ($this->ticketModel->isSeatBooked($bookingData['showtime_id'], $seat['id'])) {
            throw new \InvalidArgumentException('Có ghế vừa được đặt. Vui lòng chọn ghế khác.');
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
        $result = null;

        do {
            if ($userId <= 0) {
                $result = ['status' => 'error', 'message' => 'Vui long dang nhap de huy ve.'];
                break;
            }
            if ($bookingId <= 0) {
                $result = ['status' => 'error', 'message' => 'Booking khong hop le.'];
                break;
            }

            $booking = $this->bookingModel->getByIdAndUser($bookingId, $userId);
            if (!$booking) {
                $result = ['status' => 'error', 'message' => 'Khong tim thay booking can huy.'];
                break;
            }
            if (($booking['status'] ?? '') === 'canceled') {
                $result = ['status' => 'error', 'message' => 'Booking nay da duoc huy truoc do.'];
                break;
            }

            $showtime = $this->bookingModel->getPrimaryShowtimeByBookingId($bookingId);
            if ($showtime && !empty($showtime['show_date']) && !empty($showtime['start_time'])
                && $this->showtimeHasStarted($showtime)) {
                $result = ['status' => 'error', 'message' => 'Khong the huy ve khi suat chieu da bat dau.'];
                break;
            }

            $this->bookingModel->beginTransaction();
            try {
                if (!$this->bookingModel->cancelBooking($bookingId, $userId)) {
                    throw new \RuntimeException('Loi khi huy booking: ' . $this->bookingModel->getError());
                }

                $this->bookingModel->commit();
                $result = ['status' => 'success', 'message' => 'Huy ve thanh cong.'];
            } catch (\Throwable $e) {
                $this->bookingModel->rollback();
                $result = ['status' => 'error', 'message' => $e->getMessage()];
            }
        } while (false);

        return $result;
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
        $result = null;

        do {
            if ($bookingId <= 0) {
                $result = ['status' => 'error', 'message' => 'Booking không hợp lệ.'];
                break;
            }

            if (!in_array($status, $allowedStatuses, true)) {
                $result = ['status' => 'error', 'message' => 'Trạng thái booking không hợp lệ.'];
                break;
            }

            $booking = $this->bookingModel->getAdminBookingById($bookingId);
            if (!$booking) {
                $result = ['status' => 'error', 'message' => 'Không tìm thấy booking cần cập nhật.'];
                break;
            }

            $isRestoring = ($booking['status'] ?? '') === 'canceled' && $status !== 'canceled';
            if ($isRestoring && $this->bookingModel->hasSeatConflictWhenRestoring($bookingId)) {
                $result = [
                    'status' => 'error',
                    'message' => 'Không thể khôi phục booking vì có ghế đã được đặt bởi booking khác.'
                ];
                break;
            }

            $ticketStatus = $status === 'canceled' ? 'canceled' : 'booked';
            $this->bookingModel->beginTransaction();

            try {
                if (!$this->bookingModel->updateBookingStatus($bookingId, $status)) {
                    throw new \RuntimeException('Lỗi khi cập nhật trạng thái booking: ' . $this->bookingModel->getError());
                }

                if (!$this->bookingModel->updateTicketsStatusByBooking($bookingId, $ticketStatus)) {
                    throw new \RuntimeException('Lỗi khi cập nhật trạng thái vé: ' . $this->bookingModel->getError());
                }

                $this->bookingModel->commit();
                $result = ['status' => 'success', 'message' => 'Cập nhật trạng thái booking thành công.'];
            } catch (\Throwable $e) {
                $this->bookingModel->rollback();
                $result = ['status' => 'error', 'message' => $e->getMessage()];
            }
        } while (false);

        return $result;
    }

    public function deleteAdminBooking($bookingId) {
        $bookingId = (int)$bookingId;
        $result = null;

        do {
            if ($bookingId <= 0) {
                $result = ['status' => 'error', 'message' => 'Booking không hợp lệ.'];
                break;
            }

            $booking = $this->bookingModel->getAdminBookingById($bookingId);
            if (!$booking) {
                $result = ['status' => 'error', 'message' => 'Không tìm thấy booking cần xóa.'];
                break;
            }

            $this->bookingModel->beginTransaction();

            try {
                if (!$this->bookingModel->deleteBooking($bookingId)) {
                    throw new \RuntimeException('Lỗi khi xóa booking: ' . $this->bookingModel->getError());
                }

                $this->bookingModel->commit();
                $result = ['status' => 'success', 'message' => 'Xóa booking thành công.'];
            } catch (\Throwable $e) {
                $this->bookingModel->rollback();
                $result = ['status' => 'error', 'message' => $e->getMessage()];
            }
        } while (false);

        return $result;
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

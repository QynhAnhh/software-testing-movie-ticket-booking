<?php
namespace App\Services;

use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;

function validateBookingRequestData($showtimeModel, $userId, $showtimeId, $seatIds, $paymentMethod) {
    $result = [
        'error' => null,
        'data' => null
    ];

    $input = [
        'user_id' => $userId,
        'showtime_id' => $showtimeId,
        'seat_ids' => $seatIds,
        'payment_method' => $paymentMethod
    ];
    $rules = [
        'user_id' => 'required|integer|gt:0',
        'showtime_id' => 'required|integer|gt:0',
        'seat_ids' => 'required|array|min:1',
        'payment_method' => 'required|string'
    ];

    if (class_exists('Illuminate\\Support\\Facades\\Validator')) {
        $validator = \Illuminate\Support\Facades\Validator::make($input, $rules);
        if ($validator->fails()) {
            $result['error'] = [
                'status' => 'error',
                'message' => $validator->errors()->first()
            ];
        }
    } else {
        $fallbackError = null;
        if ($userId <= 0) {
            $fallbackError = ['status' => 'error', 'message' => 'Vui lòng đăng nhập để đặt vé.', 'page' => 'login.php'];
        } elseif ($showtimeId <= 0) {
            $fallbackError = ['status' => 'error', 'message' => 'Suất chiếu không hợp lệ.'];
        } elseif (!is_array($seatIds) || count($seatIds) === 0) {
            $fallbackError = ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 ghế'];
        } elseif (!is_string($paymentMethod) || $paymentMethod === '') {
            $fallbackError = ['status' => 'error', 'message' => 'Phương thức thanh toán không hợp lệ.'];
        }

        $result['error'] = $fallbackError;
    }

    if ($result['error'] === null) {
        $showtime = $showtimeModel->getDetailById($showtimeId);
        $normalizedSeatIds = array_values(array_unique(array_map('intval', $seatIds)));

        if (!$showtime || ($showtime['status'] ?? '') !== 'active') {
            $result['error'] = ['status' => 'error', 'message' => 'Suất chiếu không khả dụng.'];
        } elseif (!empty($showtime['show_date']) && !empty($showtime['start_time'])) {
            $showDateTime = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $showtime['show_date'] . ' ' . $showtime['start_time']
            );
            if ($showDateTime && $showDateTime <= new \DateTime()) {
                $result['error'] = [
                    'status' => 'error',
                    'message' => 'Suất chiếu này đã bắt đầu hoặc đã kết thúc.'
                ];
            }
        }

        if ($result['error'] === null && count($normalizedSeatIds) > 10) {
            $result['error'] = [
                'status' => 'error',
                'message' => 'Bạn chỉ được đặt tối đa 10 ghế cho mỗi giao dịch.'
            ];
        }

        if ($result['error'] === null) {
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
        }
    }

    return $result;
}

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
        return validateBookingRequestData(
            $this->showtimeModel,
            $userId,
            $showtimeId,
            $seatIds,
            $paymentMethod
        );
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
        $findSeat = static function (array $seats, $seatId) {
            foreach ($seats as $seat) {
                if ((int)$seat['id'] === (int)$seatId) {
                    return $seat;
                }
            }

            return null;
        };

        foreach ($bookingData['seat_ids'] as $seatId) {
            $seat = $findSeat($selectedSeats, $seatId);
            $this->validateSeat($seat, $bookingData);
            $price = (float)$bookingData['showtime']['base_price'] + (float)($seat['seat_type_price'] ?? 0);
            $seatPrices[] = ['seat_id' => $seatId, 'price' => $price];
            $totalPrice += $price;
        }

        return ['seat_prices' => $seatPrices, 'total_price' => $totalPrice];
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
        $validation = $this->validateCancellation($userId, $bookingId);
        $response = $validation['error'];

        if ($response === null) {
            $bookingData = $validation['data'];
            $this->bookingModel->beginTransaction();
            try {
                if (!$this->bookingModel->cancelBooking($bookingData['booking_id'], $bookingData['user_id'])) {
                    throw new \RuntimeException('Loi khi huy booking: ' . $this->bookingModel->getError());
                }

                $this->bookingModel->commit();
                $response = ['status' => 'success', 'message' => 'Huy ve thanh cong.'];
            } catch (\Throwable $e) {
                $this->bookingModel->rollback();
                $response = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $response;
    }

    private function validateCancellation($userId, $bookingId) {
        $result = ['error' => null, 'data' => null];
        if ($userId <= 0) {
            $result['error'] = ['status' => 'error', 'message' => 'Vui long dang nhap de huy ve.'];
        } elseif ($bookingId <= 0) {
            $result['error'] = ['status' => 'error', 'message' => 'Booking khong hop le.'];
        } else {
            $booking = $this->bookingModel->getByIdAndUser($bookingId, $userId);
            $showtime = $booking ? $this->bookingModel->getPrimaryShowtimeByBookingId($bookingId) : null;
            $showtimeHasStarted = false;
            if (is_array($showtime) && !empty($showtime['show_date']) && !empty($showtime['start_time'])) {
                $showDateTime = \DateTime::createFromFormat(
                    'Y-m-d H:i:s',
                    $showtime['show_date'] . ' ' . $showtime['start_time']
                );
                $showtimeHasStarted = $showDateTime && $showDateTime <= new \DateTime();
            }

            if (!$booking) {
                $result['error'] = ['status' => 'error', 'message' => 'Khong tim thay booking can huy.'];
            } elseif (($booking['status'] ?? '') === 'canceled') {
                $result['error'] = ['status' => 'error', 'message' => 'Booking nay da duoc huy truoc do.'];
            } elseif ($showtimeHasStarted) {
                $result['error'] = ['status' => 'error', 'message' => 'Khong the huy ve khi suat chieu da bat dau.'];
            } else {
                $result['data'] = ['user_id' => $userId, 'booking_id' => $bookingId];
            }
        }

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
        $validation = $this->validateAdminStatusUpdate($bookingId, $status);
        $response = $validation['error'];

        if ($response === null) {
            $response = $this->executeAdminStatusUpdate($validation['data']);
        }

        return $response;
    }

    private function validateAdminStatusUpdate($bookingId, $status) {
        $result = ['error' => null, 'data' => null];
        $allowedStatuses = ['pending', 'paid', 'canceled'];

        if ($bookingId <= 0) {
            $result['error'] = ['status' => 'error', 'message' => 'Booking không hợp lệ.'];
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $result['error'] = ['status' => 'error', 'message' => 'Trạng thái booking không hợp lệ.'];
        } else {
            $booking = $this->bookingModel->getAdminBookingById($bookingId);
            $isRestoring = $booking && ($booking['status'] ?? '') === 'canceled' && $status !== 'canceled';
            $hasConflict = $isRestoring && $this->bookingModel->hasSeatConflictWhenRestoring($bookingId);

            if (!$booking) {
                $result['error'] = ['status' => 'error', 'message' => 'Không tìm thấy booking cần cập nhật.'];
            } elseif ($hasConflict) {
                $result['error'] = [
                    'status' => 'error',
                    'message' => 'Không thể khôi phục booking vì có ghế đã được đặt bởi booking khác.'
                ];
            } else {
                $result['data'] = [
                    'booking_id' => $bookingId,
                    'status' => $status,
                    'ticket_status' => $status === 'canceled' ? 'canceled' : 'booked'
                ];
            }
        }

        return $result;
    }

    private function executeAdminStatusUpdate(array $bookingData) {
        $response = null;
        $this->bookingModel->beginTransaction();
        try {
            if (!$this->bookingModel->updateBookingStatus($bookingData['booking_id'], $bookingData['status'])) {
                throw new \RuntimeException('Lỗi khi cập nhật trạng thái booking: ' . $this->bookingModel->getError());
            }
            if (!$this->bookingModel->updateTicketsStatusByBooking($bookingData['booking_id'], $bookingData['ticket_status'])) {
                throw new \RuntimeException('Lỗi khi cập nhật trạng thái vé: ' . $this->bookingModel->getError());
            }

            $this->bookingModel->commit();
            $response = ['status' => 'success', 'message' => 'Cập nhật trạng thái booking thành công.'];
        } catch (\Throwable $e) {
            $this->bookingModel->rollback();
            $response = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return $response;
    }

    public function deleteAdminBooking($bookingId) {
        $bookingId = (int)$bookingId;
        $booking = $bookingId > 0 ? $this->bookingModel->getAdminBookingById($bookingId) : null;
        $response = null;

        if ($bookingId <= 0) {
            $response = ['status' => 'error', 'message' => 'Booking không hợp lệ.'];
        } elseif (!$booking) {
            $response = ['status' => 'error', 'message' => 'Không tìm thấy booking cần xóa.'];
        } else {
            $this->bookingModel->beginTransaction();
            try {
                if (!$this->bookingModel->deleteBooking($bookingId)) {
                    throw new \RuntimeException('Lỗi khi xóa booking: ' . $this->bookingModel->getError());
                }

                $this->bookingModel->commit();
                $response = ['status' => 'success', 'message' => 'Xóa booking thành công.'];
            } catch (\Throwable $e) {
                $this->bookingModel->rollback();
                $response = ['status' => 'error', 'message' => $e->getMessage()];
            }
        }

        return $response;
    }

    public function normalizeAdminFilters($input) {
        $allowedStatuses = ['pending', 'paid', 'canceled'];
        $filters = [
            'status' => '',
            'from_date' => '',
            'to_date' => '',
            'search' => ''
        ];
        $isValidDate = static function ($date) {
            if ($date === '') {
                return false;
            }

            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            return $dateTime && $dateTime->format('Y-m-d') === $date;
        };

        $status = trim((string)($input['status'] ?? ''));
        if (in_array($status, $allowedStatuses, true)) {
            $filters['status'] = $status;
        }

        $fromDate = trim((string)($input['from_date'] ?? ''));
        if ($isValidDate($fromDate)) {
            $filters['from_date'] = $fromDate;
        }

        $toDate = trim((string)($input['to_date'] ?? ''));
        if ($isValidDate($toDate)) {
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

}

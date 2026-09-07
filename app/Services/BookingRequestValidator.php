<?php
namespace App\Services;

use App\Models\ShowtimeModel;

class BookingRequestValidator {
    private $showtimeModel;

    public function __construct(ShowtimeModel $showtimeModel) {
        $this->showtimeModel = $showtimeModel;
    }

    public function validate($userId, $showtimeId, $seatIds, $paymentMethod) {
        if ($userId <= 0) {
            return [
                'error' => [
                    'status' => 'error',
                    'message' => 'Vui lòng đăng nhập để đặt vé.',
                    'page' => 'login.php'
                ],
                'data' => null
            ];
        }

        if ($showtimeId <= 0) {
            return ['error' => ['status' => 'error', 'message' => 'Suất chiếu không hợp lệ.'], 'data' => null];
        }

        if (!is_array($seatIds) || empty($seatIds)) {
            return ['error' => ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 ghế'], 'data' => null];
        }

        if (count($seatIds) > 10) {
            return ['error' => ['status' => 'error', 'message' => 'Bạn chỉ được đặt tối đa 10 ghế cho mỗi giao dịch.'], 'data' => null];
        }

        $showtimeError = $this->validateShowtime($showtimeId);
        if ($showtimeError) {
            return ['error' => $showtimeError, 'data' => null];
        }

        $showtime = $this->showtimeModel->getDetailById($showtimeId);
        $allowedPaymentMethods = ['cash', 'momo', 'vnpay', 'bank_transfer'];
        $normalizedPaymentMethod = in_array($paymentMethod, $allowedPaymentMethods, true) ? $paymentMethod : 'cash';

        return [
            'error' => null,
            'data' => [
                'user_id' => $userId,
                'showtime_id' => $showtimeId,
                'seat_ids' => array_values(array_unique(array_map('intval', $seatIds))),
                'payment_method' => $normalizedPaymentMethod,
                'showtime' => $showtime
            ]
        ];
    }

    private function validateShowtime($showtimeId) {
        $showtime = $this->showtimeModel->getDetailById($showtimeId);
        
        if (!$showtime || ($showtime['status'] ?? '') !== 'active') {
            return ['status' => 'error', 'message' => 'Suất chiếu không khả dụng.'];
        }

        if ($this->showtimeHasStarted($showtime)) {
            return ['status' => 'error', 'message' => 'Suất chiếu này đã bắt đầu hoặc đã kết thúc.'];
        }

        return null;
    }

    private function showtimeHasStarted(array $showtime) {
        $showDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $showtime['show_date'] . ' ' . $showtime['start_time']
        );

        return $showDateTime && $showDateTime <= new \DateTime();
    }
}
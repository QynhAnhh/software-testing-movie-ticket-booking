<?php
namespace App\Services;

use App\Models\ShowtimeModel;

class BookingRequestValidator {
    private $showtimeModel;

    public function __construct(ShowtimeModel $showtimeModel) {
        $this->showtimeModel = $showtimeModel;
    }

    public function validate($userId, $showtimeId, $seatIds, $paymentMethod) {
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
}


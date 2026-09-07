<?php
namespace App\Services\Traits;

trait BookingValidationTrait {
    
    /**
     * @param int $userId
     * @param int $showtimeId
     * @param mixed $seatIds
     * @throws \InvalidArgumentException
     */
    protected function validateBookingRequest($userId, $showtimeId, $seatIds) {
        if (empty($userId) || empty($showtimeId)) {
            throw new \InvalidArgumentException("User ID và Showtime ID không được để trống.");
        }

        if (empty($seatIds)) {
            throw new \InvalidArgumentException("Danh sách ghế không được để trống.");
        }
    }

    /**
     * @param mixed $seatIds
     * @return array
     */
    protected function normalizeSeatIds($seatIds) {
        if (is_string($seatIds)) {
            $decoded = json_decode($seatIds, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $seatIds = $decoded;
            } else {
                // If it's a comma separated string
                $seatIds = explode(',', $seatIds);
            }
        }

        if (!is_array($seatIds)) {
            $seatIds = [$seatIds];
        }

        // Convert to integers and remove empty values
        return array_filter(array_map('intval', $seatIds));
    }

    /**
     * @param string $paymentMethod
     * @return string
     */
    protected function normalizePaymentMethod($paymentMethod) {
        $paymentMethod = strtolower(trim((string)$paymentMethod));
        $allowedMethods = ['cash', 'vnpay', 'momo']; // Add other allowed methods if needed

        if (in_array($paymentMethod, $allowedMethods)) {
            return $paymentMethod;
        }

        return 'cash'; // Default
    }

    /**
     * @param int $showtimeId
     * @return array
     * @throws \Exception
     */
    protected function getAvailableShowtime($showtimeId) {
        $showtime = $this->showtimeModel->getDetailById($showtimeId);
        
        if (!$showtime || $showtime['status'] !== 'active') {
            throw new \Exception("Suất chiếu không tồn tại hoặc không còn hoạt động.");
        }

        return $showtime;
    }

    /**
     * @param array $selectedSeats
     * @param int $seatId
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function findSeat($selectedSeats, $seatId) {
        foreach ($selectedSeats as $seat) {
            if ((int) $seat['id'] === (int) $seatId) {
                return $seat;
            }
        }
        throw new \InvalidArgumentException("Ghế ID {$seatId} không tồn tại hoặc không hợp lệ.");
    }

    /**
     * @param array $seat
     * @param array $showtime
     * @param int $showtimeId
     * @param int $seatId
     * @throws \InvalidArgumentException
     */
    protected function validateSeatForShowtime($seat, $showtime, $showtimeId, $seatId) {
        if ((int) $seat['room_id'] !== (int) $showtime['room_id']) {
            throw new \InvalidArgumentException("Ghế ID {$seatId} không thuộc phòng chiếu này.");
        }
        
        if (empty($seat['is_active'])) {
            throw new \InvalidArgumentException("Ghế ID {$seatId} đang bảo trì hoặc không hoạt động.");
        }

        if ($this->ticketModel->isSeatBooked($showtimeId, $seatId)) {
            throw new \InvalidArgumentException("Ghế ID {$seatId} đã được đặt.");
        }
    }
}


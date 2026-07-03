<?php

namespace App\Services;

use App\Models\TicketModel;

class TicketService {
    private $model;

    public function __construct() {
        $this->model = new TicketModel();
    }

    public function getBookedSeatIdsByShowtimeId($showtimeId) {
        $showtimeId = (int)$showtimeId;

        if ($showtimeId <= 0) {
            return [];
        }

        return $this->model->getBookedSeatIdsByShowtimeId($showtimeId);
    }

    public function isSeatBooked($showtimeId, $seatId) {
        return $this->model->isSeatBooked(
            (int)$showtimeId,
            (int)$seatId
        );
    }

    public function createMany($bookingId, $showtimeId, $seatPrices) {
        if ($bookingId <= 0 || $showtimeId <= 0 || empty($seatPrices)) {
            return false;
        }

        return $this->model->createMany(
            (int)$bookingId,
            (int)$showtimeId,
            $seatPrices
        );
    }
}
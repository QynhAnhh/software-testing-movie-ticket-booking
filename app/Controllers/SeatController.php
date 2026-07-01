<?php
namespace App\Controllers;

use App\Services\SeatService;

class SeatController {
    private $seatService;

    public function __construct() {
        $this->seatService = new SeatService();
    }

    public function getSeatMap($showtimeId, $roomId) {
        return $this->seatService->getSeatMap($showtimeId, $roomId);
    }
}

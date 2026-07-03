<?php

namespace App\Controllers;

use App\Services\TicketService;

class TicketController {
    private $service;

    public function __construct() {
        $this->service = new TicketService();
    }

    public function getBookedSeatIdsByShowtimeId($showtimeId) {
        return $this->service->getBookedSeatIdsByShowtimeId($showtimeId);
    }
}
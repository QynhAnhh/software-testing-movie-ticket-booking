<?php

namespace App\Controllers;

use App\Services\BookingService;

class BookingController {
    private $service;

    public function __construct() {
        $this->service = new BookingService();
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'book_ticket') {
            $userId = (int)($_SESSION['user']['id'] ?? 0);
            $showtimeId = (int)($_POST['showtime_id'] ?? 0);
            $seatIds = $_POST['seats'] ?? [];
            $paymentMethod = $_POST['payment_method'] ?? 'cash';

            return $this->service->processBooking(
                $userId,
                $showtimeId,
                $seatIds,
                $paymentMethod
            );
        }

        return null;
    }

    public function getUserBookings($userId) {
        return $this->service->getUserBookings((int)$userId);
    }
}
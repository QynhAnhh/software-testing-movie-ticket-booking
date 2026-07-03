<?php

namespace App\Controllers;

use App\Services\BookingService;

class BookingController
{
    private $bookingService;

    public function __construct()
    {
        $this->bookingService = new BookingService();
    }

    public function handleRequest()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'book_ticket') {
            if (!isset($_SESSION['user']['id'])) {
                return [
                    'status' => 'error',
                    'message' => 'Vui lòng đăng nhập để đặt vé.'
                ];
            }

            $userId = (int) $_SESSION['user']['id'];
            $showtimeId = (int) ($_POST['showtime_id'] ?? 0);
            $seatIds = $_POST['seats'] ?? [];
            $paymentMethod = $_POST['payment_method'] ?? 'cash';

            return $this->bookingService->processBooking(
                $userId,
                $showtimeId,
                $seatIds,
                $paymentMethod
            );
        }

        return null;
    }

    public function getUserBookings($userId)
    {
        return $this->bookingService->getUserBookings((int) $userId);
    }
}
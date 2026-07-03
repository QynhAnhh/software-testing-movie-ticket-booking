<?php
namespace App\Controllers;

use App\Services\BookingService;

class BookingController {
    private $bookingService;

    public function __construct() {
        $this->bookingService = new BookingService();
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'book_ticket') {
                $userId = (int)($_SESSION['user']['id'] ?? 0);
                $showtimeId = (int)($_POST['showtime_id'] ?? 0);
                $seatIds = $_POST['seat_ids'] ?? [];
                $paymentMethod = $_POST['payment_method'] ?? 'cash';

                return $this->bookingService->processBooking($userId, $showtimeId, $seatIds, $paymentMethod);
            }

            if ($action === 'cancel_booking') {
                $userId = (int)($_SESSION['user']['id'] ?? 0);
                $bookingId = (int)($_POST['booking_id'] ?? 0);

                return $this->bookingService->cancelBooking($userId, $bookingId);
            }
        }
        return null;
    }

    public function processBooking($userId, $showtimeId, $seatIds, $paymentMethod) {
        return $this->bookingService->processBooking($userId, $showtimeId, $seatIds, $paymentMethod);
    }

    public function getUserBookings($userId) {
        return $this->bookingService->getUserBookings($userId);
    }

    public function cancelBooking($userId, $bookingId) {
        return $this->bookingService->cancelBooking((int)$userId, (int)$bookingId);
    }

    public function getTotalSpentByUser($userId) {
        return $this->bookingService->getTotalSpentByUser((int)$userId);
    }
}

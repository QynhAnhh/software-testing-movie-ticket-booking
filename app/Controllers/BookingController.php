<?php
namespace App\Controllers;

use App\Services\BookingService;

class BookingController {
    private $bookingService;

    public function __construct() {
        $this->bookingService = new BookingService();
    }

    public function handleRequest() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];
            
            if ($action === 'book_ticket') {
                if (!isset($_SESSION['user']['id'])) {
                    return ['status' => 'error', 'message' => 'Vui lòng đăng nhập để đặt vé.'];
                }
                $userId = $_SESSION['user']['id'];
                $showtimeId = (int)($_POST['showtime_id'] ?? 0);
                $seatIds = $_POST['seats'] ?? []; // Array of seat IDs
                $paymentMethod = $_POST['payment_method'] ?? 'cash';
                
                return $this->bookingService->processBooking($userId, $showtimeId, $seatIds, $paymentMethod);
            }
            
            if ($action === 'cancel_ticket') {
                if (!isset($_SESSION['user']['id'])) {
                    return ['status' => 'error', 'message' => 'Vui lòng đăng nhập.'];
                }
                $userId = $_SESSION['user']['id'];
                $bookingId = (int)($_POST['booking_id'] ?? 0);
                
                return $this->bookingService->cancelBooking($bookingId, $userId);
            }
        }
        return null;
    }

    public function getUserBookings($userId) {
        return $this->bookingService->getUserBookings($userId);
    }
}

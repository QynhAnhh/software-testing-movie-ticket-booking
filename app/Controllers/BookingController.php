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
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'book_ticket') {
                // TODO: Lấy dữ liệu từ $_POST và gọi BookingService->processBooking()
            }
        }
    }
}

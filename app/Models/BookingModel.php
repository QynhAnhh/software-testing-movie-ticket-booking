<?php
namespace App\Models;

use App\Config\Database;

class BookingModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    // TODO: Viết hàm truy vấn lấy danh sách vé của User
    public function getBookingsByUser($userId) {
        // SELECT * FROM bookings WHERE user_id = ?
    }

    // TODO: Viết hàm insert vé mới
    public function createBooking($data) {
        // INSERT INTO bookings ...
    }

    public function getError() {
        return mysqli_error($this->conn);
    }
}

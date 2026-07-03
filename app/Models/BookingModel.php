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
        $query = "
            SELECT 
                b.id, 
                b.booking_code, 
                b.total_price, 
                b.status, 
                b.created_at,
                m.title AS movie_title,
                th.name AS theatre_name,
                GROUP_CONCAT(CONCAT(s.seat_row, s.seat_number) SEPARATOR ', ') AS seat_names
            FROM bookings b
            LEFT JOIN tickets t ON b.id = t.booking_id
            LEFT JOIN showtimes st ON t.showtime_id = st.id
            LEFT JOIN movies m ON st.movie_id = m.id
            LEFT JOIN seats s ON t.seat_id = s.id
            LEFT JOIN rooms r ON st.room_id = r.id
            LEFT JOIN theatres th ON r.theatre_id = th.id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY b.created_at DESC
        ";
        
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $bookings = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $bookings[] = $row;
            }
        }
        return $bookings;
    }

    // TODO: Viết hàm insert vé mới
    public function createBooking($data) {
        // INSERT INTO bookings ...
    }

    public function getTotalBookings() {
        $result = mysqli_query($this->conn, "SELECT COUNT(*) as count FROM bookings");
        if ($result) {
            return mysqli_fetch_assoc($result)['count'];
        }
        return 0;
    }

    public function getTotalRevenue() {
        $result = mysqli_query($this->conn, "SELECT SUM(total_price) as total FROM bookings WHERE status='paid'");
        if ($result) {
            return mysqli_fetch_assoc($result)['total'] ?? 0;
        }
        return 0;
    }

    public function getTodayBookingsCount() {
        $result = mysqli_query($this->conn, "SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = CURDATE()");
        if ($result) {
            return mysqli_fetch_assoc($result)['count'];
        }
        return 0;
    }

    public function getError() {
        return mysqli_error($this->conn);
    }
}

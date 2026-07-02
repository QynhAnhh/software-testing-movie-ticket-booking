<?php
namespace App\Models;

use App\Config\Database;

class BookingModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function getBookingsByUser($userId) {
        $query = "
            SELECT b.*, 
                   GROUP_CONCAT(t.seat_id) as seat_ids,
                   GROUP_CONCAT(CONCAT(se.seat_row, se.seat_number) SEPARATOR ', ') as seat_names,
                   s.show_date, 
                   s.start_time, 
                   m.title as movie_title, 
                   r.name as room_name, 
                   th.name as theatre_name,
                   CONCAT('BK', LPAD(b.id, 6, '0')) as booking_code
            FROM bookings b
            JOIN tickets t ON b.id = t.booking_id
            JOIN showtimes s ON t.showtime_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN rooms r ON s.room_id = r.id
            JOIN theatres th ON r.theatre_id = th.id
            JOIN seats se ON t.seat_id = se.id
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

    public function createBooking($userId, $totalPrice, $paymentMethod) {
        $status = 'paid';
        $stmt = mysqli_prepare($this->conn, "INSERT INTO bookings (user_id, total_price, payment_method, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, "idss", $userId, $totalPrice, $paymentMethod, $status);
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    public function createTickets($bookingId, $showtimeId, $seatIds, $prices) {
        if (empty($seatIds)) return true;
        $stmt = mysqli_prepare($this->conn, "INSERT INTO tickets (booking_id, showtime_id, seat_id, price, status) VALUES (?, ?, ?, ?, 'booked')");
        $success = true;
        foreach ($seatIds as $index => $seatId) {
            $price = $prices[$index] ?? 0;
            mysqli_stmt_bind_param($stmt, "iiid", $bookingId, $showtimeId, $seatId, $price);
            if (!mysqli_stmt_execute($stmt)) {
                $success = false;
            }
        }
        return $success;
    }

    public function cancelBooking($bookingId, $userId) {
        $stmt = mysqli_prepare($this->conn, "UPDATE bookings SET status = 'canceled' WHERE id = ? AND user_id = ? AND status != 'canceled'");
        mysqli_stmt_bind_param($stmt, "ii", $bookingId, $userId);
        mysqli_stmt_execute($stmt);
        
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $stmtTicket = mysqli_prepare($this->conn, "UPDATE tickets SET status = 'canceled' WHERE booking_id = ?");
            mysqli_stmt_bind_param($stmtTicket, "i", $bookingId);
            mysqli_stmt_execute($stmtTicket);
            return true;
        }
        return false;
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

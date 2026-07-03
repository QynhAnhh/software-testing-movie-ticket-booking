<?php
namespace App\Models;

use App\Config\Database;

class BookingModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function getById($id) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM bookings WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    public function getByIdAndUser($id, $userId) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $id, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    public function getBookingsByUser($userId) {
        $query = "
            SELECT b.*, 
                   GROUP_CONCAT(CONCAT(s.seat_row, s.seat_number) ORDER BY s.seat_row, s.seat_number SEPARATOR ', ') AS seats,
                   m.title AS movie_title,
                   m.poster AS movie_poster,
                   st.show_date, st.start_time, st.end_time,
                   r.name AS room_name,
                   th.name AS theatre_name,
                   th.address AS theatre_address,
                   th.city AS theatre_city
            FROM bookings b
            LEFT JOIN tickets t ON t.booking_id = b.id
            LEFT JOIN showtimes st ON st.id = t.showtime_id
            LEFT JOIN movies m ON m.id = st.movie_id
            LEFT JOIN rooms r ON r.id = st.room_id
            LEFT JOIN theatres th ON th.id = r.theatre_id
            LEFT JOIN seats s ON s.id = t.seat_id
            WHERE b.user_id = ?
            GROUP BY b.id, b.user_id, b.booking_code, b.total_price, b.payment_method,
                     b.status, b.created_at, b.updated_at,
                     m.title, m.poster, st.show_date, st.start_time, st.end_time,
                     r.name, th.name, th.address, th.city
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

    public function createBooking($data) {
        $bookingCode = 'BK-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO bookings (user_id, booking_code, total_price, payment_method, status) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "isdss",
            $data['user_id'],
            $bookingCode,
            $data['total_price'],
            $data['payment_method'],
            $data['status']
        );
        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }
        return false;
    }

    public function cancelBookingForUser($bookingId, $userId) {
        mysqli_begin_transaction($this->conn);

        $bookingStmt = mysqli_prepare(
            $this->conn,
            "UPDATE bookings SET status = 'canceled' WHERE id = ? AND user_id = ? AND status != 'canceled'"
        );
        mysqli_stmt_bind_param($bookingStmt, "ii", $bookingId, $userId);

        if (!mysqli_stmt_execute($bookingStmt) || mysqli_stmt_affected_rows($bookingStmt) < 1) {
            mysqli_rollback($this->conn);
            return false;
        }

        $ticketStmt = mysqli_prepare(
            $this->conn,
            "UPDATE tickets SET status = 'canceled' WHERE booking_id = ? AND status != 'canceled'"
        );
        mysqli_stmt_bind_param($ticketStmt, "i", $bookingId);

        if (!mysqli_stmt_execute($ticketStmt)) {
            mysqli_rollback($this->conn);
            return false;
        }

        return mysqli_commit($this->conn);
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

    public function getTotalSpentByUser($userId) {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT SUM(t.price) AS total_spent
             FROM tickets t
             INNER JOIN bookings b ON b.id = t.booking_id
             WHERE b.user_id = ?
               AND b.status = 'paid'
               AND t.status != 'canceled'"
        );
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        return ($row && $row['total_spent'] !== null) ? (int)$row['total_spent'] : 0;
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

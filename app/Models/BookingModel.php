<?php
namespace App\Models;

use App\Config\Database;

class BookingModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function beginTransaction() {
        mysqli_begin_transaction($this->conn);
    }

    public function commit() {
        mysqli_commit($this->conn);
    }

    public function rollback() {
        mysqli_rollback($this->conn);
    }

    //

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

    //
    public function getBookingsByUser($userId) {
        $query = "
            SELECT
                b.*,
                GROUP_CONCAT(CONCAT(s.seat_row, s.seat_number) ORDER BY s.seat_row, s.seat_number SEPARATOR ', ') AS seat_names,
                st.show_date,
                st.start_time,
                st.end_time,
                m.title AS movie_title,
                r.name AS room_name,
                th.name AS theatre_name
            FROM bookings b
            INNER JOIN tickets t ON t.booking_id = b.id
            INNER JOIN seats s ON s.id = t.seat_id
            INNER JOIN showtimes st ON st.id = t.showtime_id
            INNER JOIN movies m ON m.id = st.movie_id
            INNER JOIN rooms r ON r.id = st.room_id
            INNER JOIN theatres th ON th.id = r.theatre_id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY b.created_at DESC
        ";

        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $bookings = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $bookings[] = $row;
        }

        return $bookings;
    }
    //

    //
    public function createBooking($userId, $totalPrice, $paymentMethod) {
        $status = 'paid';

        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO bookings (user_id, total_price, payment_method, status)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param($stmt, "idss", $userId, $totalPrice, $paymentMethod, $status);

        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }

        return false;
    }

    public function cancelBooking($bookingId, $userId) {
        $stmt = mysqli_prepare(
            $this->conn,
            "
            UPDATE bookings
            SET status = 'canceled'
            WHERE id = ?
              AND user_id = ?
              AND status != 'canceled'
            "
        );

        mysqli_stmt_bind_param($stmt, "ii", $bookingId, $userId);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $stmtTicket = mysqli_prepare(
                $this->conn,
                "
                UPDATE tickets
                SET status = 'canceled'
                WHERE booking_id = ?
                "
            );

            mysqli_stmt_bind_param($stmtTicket, "i", $bookingId);

            return mysqli_stmt_execute($stmtTicket);
        }

        return false;
    }
    //

    //
    public function getTotalBookings() {
        $result = mysqli_query($this->conn, "SELECT COUNT(*) as count FROM bookings");
        if ($result) {
            return mysqli_fetch_assoc($result)['count'];
        }
        return 0;
    }
    //

    //
    public function getTotalRevenue() {
        $result = mysqli_query($this->conn, "SELECT SUM(total_price) as total FROM bookings WHERE status='paid'");
        if ($result) {
            return mysqli_fetch_assoc($result)['total'] ?? 0;
        }
        return 0;
    }
    //

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

    //
    public function getTodayBookingsCount() {
        $result = mysqli_query($this->conn, "SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = CURDATE()");
        if ($result) {
            return mysqli_fetch_assoc($result)['count'];
        }
        return 0;
    }
    //

    //
    public function getError() {
        return mysqli_error($this->conn);
    }
    //
}

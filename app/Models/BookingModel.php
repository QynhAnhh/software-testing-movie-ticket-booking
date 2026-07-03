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

    public function getError() {
        return mysqli_error($this->conn);
    }
}
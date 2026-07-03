<?php

namespace App\Models;

use App\Config\Database;

class BookingModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
    }

    public function beginTransaction()
    {
        mysqli_begin_transaction($this->conn);
    }

    public function commit()
    {
        mysqli_commit($this->conn);
    }

    public function rollback()
    {
        mysqli_rollback($this->conn);
    }

    public function createBooking($userId, $totalPrice, $paymentMethod)
    {
        $status = 'paid';

        $stmt = mysqli_prepare(
            $this->conn,
            "
            INSERT INTO bookings (
                user_id,
                total_price,
                payment_method,
                status
            )
            VALUES (?, ?, ?, ?)
            "
        );

        mysqli_stmt_bind_param(
            $stmt,
            "idss",
            $userId,
            $totalPrice,
            $paymentMethod,
            $status
        );

        if (mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }

        return false;
    }

    public function createTickets($bookingId, $showtimeId, $seatIds, $prices)
    {
        if (empty($seatIds)) {
            return false;
        }

        $stmt = mysqli_prepare(
            $this->conn,
            "
            INSERT INTO tickets (
                booking_id,
                showtime_id,
                seat_id,
                price,
                status
            )
            VALUES (?, ?, ?, ?, 'booked')
            "
        );

        foreach ($seatIds as $index => $seatId) {
            $price = $prices[$index] ?? 0;

            mysqli_stmt_bind_param(
                $stmt,
                "iiid",
                $bookingId,
                $showtimeId,
                $seatId,
                $price
            );

            if (!mysqli_stmt_execute($stmt)) {
                return false;
            }
        }

        return true;
    }

    public function isSeatBooked($showtimeId, $seatId)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "
            SELECT id
            FROM tickets
            WHERE showtime_id = ?
              AND seat_id = ?
              AND status != 'canceled'
            LIMIT 1
            "
        );

        mysqli_stmt_bind_param($stmt, "ii", $showtimeId, $seatId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        return mysqli_fetch_assoc($result) !== null;
    }

    public function getBookingsByUser($userId)
    {
        $query = "
            SELECT
                b.id,
                b.user_id,
                b.booking_code,
                b.total_price,
                b.payment_method,
                b.status,
                b.created_at,

                GROUP_CONCAT(t.seat_id ORDER BY se.seat_row, se.seat_number) AS seat_ids,
                GROUP_CONCAT(CONCAT(se.seat_row, se.seat_number) ORDER BY se.seat_row, se.seat_number SEPARATOR ', ') AS seat_names,

                st.show_date,
                st.start_time,
                st.end_time,

                m.title AS movie_title,
                r.name AS room_name,
                th.name AS theatre_name

            FROM bookings b
            INNER JOIN tickets t ON t.booking_id = b.id
            INNER JOIN showtimes st ON st.id = t.showtime_id
            INNER JOIN movies m ON m.id = st.movie_id
            INNER JOIN rooms r ON r.id = st.room_id
            INNER JOIN theatres th ON th.id = r.theatre_id
            INNER JOIN seats se ON se.id = t.seat_id

            WHERE b.user_id = ?

            GROUP BY
                b.id,
                b.user_id,
                b.booking_code,
                b.total_price,
                b.payment_method,
                b.status,
                b.created_at,
                st.show_date,
                st.start_time,
                st.end_time,
                m.title,
                r.name,
                th.name

            ORDER BY b.created_at DESC
        ";

        $stmt = mysqli_prepare($this->conn, $query);

        mysqli_stmt_bind_param($stmt, "i", $userId);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $bookings = [];

        while ($row = mysqli_fetch_assoc($result)) {
            if (empty($row['booking_code'])) {
                $row['booking_code'] = 'BK-' . str_pad($row['id'], 6, '0', STR_PAD_LEFT);
            }

            $bookings[] = $row;
        }

        return $bookings;
    }

    public function cancelBooking($bookingId, $userId)
    {
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

    public function getTotalBookings()
    {
        $result = mysqli_query(
            $this->conn,
            "SELECT COUNT(*) AS count FROM bookings"
        );

        if ($result) {
            return (int) mysqli_fetch_assoc($result)['count'];
        }

        return 0;
    }

    public function getTotalRevenue()
    {
        $result = mysqli_query(
            $this->conn,
            "SELECT SUM(total_price) AS total FROM bookings WHERE status = 'paid'"
        );

        if ($result) {
            return mysqli_fetch_assoc($result)['total'] ?? 0;
        }

        return 0;
    }

    public function getTodayBookingsCount()
    {
        $result = mysqli_query(
            $this->conn,
            "SELECT COUNT(*) AS count FROM bookings WHERE DATE(created_at) = CURDATE()"
        );

        if ($result) {
            return (int) mysqli_fetch_assoc($result)['count'];
        }

        return 0;
    }

    public function getError()
    {
        return mysqli_error($this->conn);
    }
}
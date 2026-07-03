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
            "
            INSERT INTO bookings
            (
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

        if(mysqli_stmt_execute($stmt)) {
            return mysqli_insert_id($this->conn);
        }

        return false;
    }

    public function createTickets(
        $bookingId,
        $showtimeId,
        $seatIds,
        $prices
    ) {

        $stmt = mysqli_prepare(
            $this->conn,
            "
            INSERT INTO tickets
            (
                booking_id,
                showtime_id,
                seat_id,
                price,
                status
            )
            VALUES (?, ?, ?, ?, 'booked')
            "
        );

        foreach($seatIds as $index=>$seatId) {

            $price = $prices[$index];

            mysqli_stmt_bind_param(
                $stmt,
                "iiid",
                $bookingId,
                $showtimeId,
                $seatId,
                $price
            );

            if(!mysqli_stmt_execute($stmt)) {
                return false;
            }
        }
        return true;
    }

    public function isSeatBooked($showtimeId,$seatId) {
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

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $showtimeId,
            $seatId
        );

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result) !== null;
    }


    public function getError() {
        return mysqli_error($this->conn);
    }
}
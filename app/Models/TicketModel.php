<?php

namespace App\Models;

use App\Config\Database;

class TicketModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = Database::getConnection();
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

    public function getBookedSeatIdsByShowtimeId($showtimeId)
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "
            SELECT seat_id
            FROM tickets
            WHERE showtime_id = ?
              AND status != 'canceled'
            "
        );

        mysqli_stmt_bind_param($stmt, "i", $showtimeId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $seatIds = [];

        while ($row = mysqli_fetch_assoc($result)) {
            $seatIds[] = (int)$row['seat_id'];
        }

        return $seatIds;
    }

    public function createMany($bookingId, $showtimeId, $seatPrices)
    {
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

        foreach ($seatPrices as $seatPrice) {
            mysqli_stmt_bind_param(
                $stmt,
                "iiid",
                $bookingId,
                $showtimeId,
                $seatPrice['seat_id'],
                $seatPrice['price']
            );

            if (!mysqli_stmt_execute($stmt)) {
                return false;
            }
        }

        return true;
    }

    public function getError()
    {
        return mysqli_error($this->conn);
    }
}
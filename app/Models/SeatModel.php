<?php
namespace App\Models;

use App\Config\Database;

class SeatModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function findById($id) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM seats WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    public function findByPosition($roomId, $seatRow, $seatNumber, $excludeId = null) {
        if ($excludeId) {
            $stmt = mysqli_prepare(
                $this->conn,
                "SELECT id FROM seats WHERE room_id = ? AND seat_row = ? AND seat_number = ? AND id != ?"
            );
            mysqli_stmt_bind_param($stmt, "isii", $roomId, $seatRow, $seatNumber, $excludeId);
        } else {
            $stmt = mysqli_prepare(
                $this->conn,
                "SELECT id FROM seats WHERE room_id = ? AND seat_row = ? AND seat_number = ?"
            );
            mysqli_stmt_bind_param($stmt, "isi", $roomId, $seatRow, $seatNumber);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    public function insert($data) {
        $isActive = $data['is_active'] ? 1 : 0;
        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO seats (room_id, seat_row, seat_number, seat_type_id, is_active) VALUES (?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "isiii",
            $data['room_id'],
            $data['seat_row'],
            $data['seat_number'],
            $data['seat_type_id'],
            $isActive
        );
        return mysqli_stmt_execute($stmt);
    }

    public function update($id, $data) {
        $isActive = $data['is_active'] ? 1 : 0;
        $stmt = mysqli_prepare(
            $this->conn,
            "UPDATE seats SET room_id = ?, seat_row = ?, seat_number = ?, seat_type_id = ?, is_active = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "isiiii",
            $data['room_id'],
            $data['seat_row'],
            $data['seat_number'],
            $data['seat_type_id'],
            $isActive,
            $id
        );
        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $stmt = mysqli_prepare($this->conn, "DELETE FROM seats WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function countByRoomId($roomId) {
        $stmt = mysqli_prepare($this->conn, "SELECT COUNT(*) AS total FROM seats WHERE room_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $roomId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return (int)($row['total'] ?? 0);
    }

    public function getAllWithDetails($roomId = null) {
        $query = "
            SELECT s.*, r.name AS room_name, t.name AS theatre_name,
                   st.name AS seat_type_name, st.price AS seat_type_price
            FROM seats s
            INNER JOIN rooms r ON r.id = s.room_id
            INNER JOIN theatres t ON t.id = r.theatre_id
            INNER JOIN seat_types st ON st.id = s.seat_type_id
        ";
        if ($roomId) {
            $query .= " WHERE s.room_id = " . (int)$roomId;
        }
        $query .= " ORDER BY t.name ASC, r.name ASC, s.seat_row ASC, s.seat_number ASC";

        $result = mysqli_query($this->conn, $query);
        $seats = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $seats[] = $row;
            }
        }
        return $seats;
    }

    public function getError() {
        return mysqli_error($this->conn);
    }
}

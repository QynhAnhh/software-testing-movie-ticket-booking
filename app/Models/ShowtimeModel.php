<?php
namespace App\Models;

use App\Config\Database;

class ShowtimeModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function findById($id) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM showtimes WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    public function findConflict($roomId, $showDate, $startTime, $excludeId = null) {
        if ($excludeId) {
            $stmt = mysqli_prepare(
                $this->conn,
                "SELECT id FROM showtimes WHERE room_id = ? AND show_date = ? AND start_time = ? AND id != ?"
            );
            mysqli_stmt_bind_param($stmt, "issi", $roomId, $showDate, $startTime, $excludeId);
        } else {
            $stmt = mysqli_prepare(
                $this->conn,
                "SELECT id FROM showtimes WHERE room_id = ? AND show_date = ? AND start_time = ?"
            );
            mysqli_stmt_bind_param($stmt, "iss", $roomId, $showDate, $startTime);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    public function getMovieDuration($movieId) {
        $stmt = mysqli_prepare($this->conn, "SELECT duration FROM movies WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $movieId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return (int)($row['duration'] ?? 0);
    }

    public function movieExists($movieId) {
        $stmt = mysqli_prepare($this->conn, "SELECT id FROM movies WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $movieId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return (bool)mysqli_fetch_assoc($result);
    }

    public function roomExists($roomId) {
        $stmt = mysqli_prepare($this->conn, "SELECT id FROM rooms WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $roomId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return (bool)mysqli_fetch_assoc($result);
    }

    public function insert($data) {
        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price, status) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iisssds",
            $data['movie_id'],
            $data['room_id'],
            $data['show_date'],
            $data['start_time'],
            $data['end_time'],
            $data['base_price'],
            $data['status']
        );
        return mysqli_stmt_execute($stmt);
    }

    public function update($id, $data) {
        $stmt = mysqli_prepare(
            $this->conn,
            "UPDATE showtimes SET movie_id = ?, room_id = ?, show_date = ?, start_time = ?, end_time = ?, base_price = ?, status = ? WHERE id = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "iisssdsi",
            $data['movie_id'],
            $data['room_id'],
            $data['show_date'],
            $data['start_time'],
            $data['end_time'],
            $data['base_price'],
            $data['status'],
            $id
        );
        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $stmt = mysqli_prepare($this->conn, "DELETE FROM showtimes WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function getAllWithDetails() {
        $query = "
            SELECT st.*, m.title AS movie_title, m.duration AS movie_duration,
                   r.name AS room_name, t.name AS theatre_name
            FROM showtimes st
            INNER JOIN movies m ON m.id = st.movie_id
            INNER JOIN rooms r ON r.id = st.room_id
            INNER JOIN theatres t ON t.id = r.theatre_id
            ORDER BY st.show_date DESC, st.start_time ASC
        ";
        $result = mysqli_query($this->conn, $query);
        $showtimes = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $showtimes[] = $row;
            }
        }
        return $showtimes;
    }

    public function getAllMovies() {
        $query = "SELECT id, title, duration, status FROM movies ORDER BY title ASC";
        $result = mysqli_query($this->conn, $query);
        $movies = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $movies[] = $row;
            }
        }
        return $movies;
    }

    public function getAllRooms() {
        $query = "
            SELECT r.id, r.name, r.is_active, t.name AS theatre_name
            FROM rooms r
            INNER JOIN theatres t ON t.id = r.theatre_id
            WHERE r.is_active = 1
            ORDER BY t.name ASC, r.name ASC
        ";
        $result = mysqli_query($this->conn, $query);
        $rooms = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rooms[] = $row;
            }
        }
        return $rooms;
    }

    public function getError() {
        return mysqli_error($this->conn);
    }
}

<?php
namespace App\Models;

use App\Config\Database;

class ShowtimeModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function getShowtimesByMovie($movie_id, $date = null) {
        $sql = "SELECT st.*, 
                       r.name as room_name,
                       r.total_seats,
                       t.name as theatre_name,
                       t.address,
                       (
                           SELECT COUNT(*)
                           FROM tickets tk
                           WHERE tk.showtime_id = st.id
                             AND tk.status <> 'canceled'
                       ) as booked_seats
                FROM showtimes st
                JOIN rooms r ON st.room_id = r.id
                JOIN theatres t ON r.theatre_id = t.id
                WHERE st.movie_id = ? AND st.show_date >= CURDATE()";
        
        if ($date) {
            $sql .= " AND st.show_date = ?";
        }
        
        $sql .= " ORDER BY st.show_date, st.start_time";
        
        $stmt = $this->conn->prepare($sql);
        if ($date) {
            $stmt->bind_param("is", $movie_id, $date);
        } else {
            $stmt->bind_param("i", $movie_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $showtimes = [];
        while ($row = $result->fetch_assoc()) {
            $row['booked_seats'] = (int)($row['booked_seats'] ?? 0);
            $row['total_seats'] = (int)($row['total_seats'] ?? 0);
            $row['available_seats'] = max($row['total_seats'] - $row['booked_seats'], 0);
            $showtimes[] = $row;
        }
        return $showtimes;
    }

    public function getShowtimeDetails($id) {
        $sql = "SELECT st.*, 
                       r.name as room_name,
                       r.id as room_id,
                       t.name as theatre_name,
                       t.address,
                       m.title as movie_title,
                       m.id as movie_id,
                       m.poster,
                       m.duration,
                       m.country,
                       m.age_restriction
                FROM showtimes st
                JOIN rooms r ON st.room_id = r.id
                JOIN theatres t ON r.theatre_id = t.id
                JOIN movies m ON st.movie_id = m.id
                WHERE st.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getById($id) {
        return $this->getShowtimeDetails($id);
    }
}

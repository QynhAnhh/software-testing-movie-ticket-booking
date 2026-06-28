<?php

namespace App\Models;

use PDO;

class MovieModel extends BaseModel
{
    protected string $table = 'movies';

    /**
     * Lấy danh sách thể loại của một phim
     */
    public function getGenres(int $movieId)
    {
        $sql = "SELECT g.* FROM genres g 
                JOIN movie_genre mg ON g.id = mg.genre_id 
                WHERE mg.movie_id = :movie_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['movie_id' => $movieId]);
        return $stmt->fetchAll();
    }

    /**
     * Đồng bộ thể loại của một phim (Xóa cũ, Thêm mới)
     */
    public function syncGenres(int $movieId, array $genreIds)
    {
        // 1. Xóa tất cả thể loại cũ của phim này
        $stmt = $this->db->prepare("DELETE FROM movie_genre WHERE movie_id = :movie_id");
        $stmt->execute(['movie_id' => $movieId]);

        // 2. Thêm các thể loại mới
        if (!empty($genreIds)) {
            $sql = "INSERT INTO movie_genre (movie_id, genre_id) VALUES (:movie_id, :genre_id)";
            $stmt = $this->db->prepare($sql);
            foreach ($genreIds as $genreId) {
                $stmt->execute([
                    'movie_id' => $movieId,
                    'genre_id' => $genreId
                ]);
            }
        }
        return true;
    }
}

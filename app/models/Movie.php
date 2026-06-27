<?php
namespace app\Models;

use core\Database;

class Movie extends BaseModel
{
    protected $table = 'movies';
    
    // Lấy tất cả phim kèm thể loại (dùng GROUP_CONCAT)
    public function getAllWithGenres()
    {
        $sql = "SELECT m.*, 
                       GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names,
                       GROUP_CONCAT(g.id) as genre_ids
                FROM {$this->table} m
                LEFT JOIN movie_genre mg ON m.id = mg.movie_id
                LEFT JOIN genres g ON mg.genre_id = g.id
                GROUP BY m.id
                ORDER BY m.id DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    // Lấy một phim kèm thể loại
    public function getWithGenres($id)
    {
        $sql = "SELECT m.*, 
                       GROUP_CONCAT(g.name SEPARATOR ', ') as genre_names,
                       GROUP_CONCAT(g.id) as genre_ids
                FROM {$this->table} m
                LEFT JOIN movie_genre mg ON m.id = mg.movie_id
                LEFT JOIN genres g ON mg.genre_id = g.id
                WHERE m.id = ?
                GROUP BY m.id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Lấy danh sách genre_id của một phim (dùng cho edit)
    public function getGenreIds($movieId)
    {
        $sql = "SELECT genre_id FROM movie_genre WHERE movie_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$movieId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    // Gán thể loại cho phim (xóa cũ, thêm mới)
    public function syncGenres($movieId, array $genreIds)
    {
        // Xóa cũ
        $del = $this->db->prepare("DELETE FROM movie_genre WHERE movie_id = ?");
        $del->execute([$movieId]);

        // Thêm mới
        if (!empty($genreIds)) {
            $insert = $this->db->prepare("INSERT INTO movie_genre (movie_id, genre_id) VALUES (?, ?)");
            foreach ($genreIds as $gid) {
                $insert->execute([$movieId, $gid]);
            }
        }
    }

    // Override create để tự động thêm timestamps
    public function create($data)
    {
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return parent::create($data);
    }

    // Override update để tự động cập nhật updated_at
    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return parent::update($id, $data);
    }

    // Xóa phim (có xóa cả quan hệ và poster nếu cần)
    public function delete($id)
    {
        // Lấy thông tin phim để xóa poster
        $movie = $this->find($id);
        if ($movie && !empty($movie['poster'])) {
            $posterPath = $_SERVER['DOCUMENT_ROOT'] . '/movie-ticket-booking/public/' . $movie['poster'];
            if (file_exists($posterPath)) {
                unlink($posterPath);
            }
        }
        // Xóa quan hệ thể loại
        $this->syncGenres($id, []);
        // Xóa phim
        return parent::delete($id);
    }
}
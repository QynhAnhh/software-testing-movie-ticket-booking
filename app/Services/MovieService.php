<?php
namespace App\Services;

use App\Models\MovieModel;

class MovieService {
    private $model;

    public function __construct() {
        $this->model = new MovieModel();
    }

    public function addMovie($data, $genreIds) {
        if (empty($data['title']) || empty($data['country']) || empty($data['screening_date'])) {
            return ['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ các trường bắt buộc!'];
        }
        if ($data['duration'] <= 0){
            return ['status' => 'error', 'message' => 'Vui lòng nhập thời lượng phim hợp lệ!'];
        }

        $new_movie_id = $this->model->insertMovie($data);
        if ($new_movie_id) {
            $this->model->insertMovieGenres($new_movie_id, $genreIds);
            return ['status' => 'success', 'message' => 'Thêm phim thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi thêm phim: ' . $this->model->getError()];
    }

    public function updateMovie($id, $data, $genreIds) {
        if ($id <= 0 || empty($data['title']) || empty($data['country']) || $data['duration'] <= 0 || empty($data['screening_date'])) {
            return ['status' => 'error', 'message' => 'Dữ liệu cập nhật không hợp lệ!'];
        }

        if ($this->model->updateMovie($id, $data)) {
            $this->model->deleteMovieGenres($id);
            $this->model->insertMovieGenres($id, $genreIds);
            return ['status' => 'success', 'message' => 'Cập nhật phim thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi cập nhật phim: ' . $this->model->getError()];
    }

    public function deleteMovie($id) {
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'ID không hợp lệ!'];
        }

        if ($this->model->deleteMovie($id)) {
            return ['status' => 'success', 'message' => 'Xóa phim thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi xóa: ' . $this->model->getError()];
    }

    public function getAllMovies() {
        return $this->model->getAllMoviesWithGenres();
    }

    public function getMovieById($id) {
        if ($id <= 0) return null;
        return $this->model->getMovieByIdWithGenres($id);
    }

    public function getNowShowingMovies() {
        return $this->model->getNowShowingMovies();
    }
}

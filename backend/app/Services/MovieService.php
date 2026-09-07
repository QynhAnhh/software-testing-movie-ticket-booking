<?php
namespace App\Services;

use App\Models\MovieModel;

class MovieService {
    private $model;
    private const POSTER_UPLOAD_DIR = 'images/movies';
    private const MAX_POSTER_SIZE = 5 * 1024 * 1024;
    private const MAX_DESCRIPTION_LENGTH = 5000;

    public function __construct(?MovieModel $model = null)
    {
        $this->model = $model ?? new MovieModel();
    }

    public function addMovie($data, $genreIds, $posterFile = null) {
        if (empty($data['title']) || empty($data['country']) || empty($data['screening_date'])) {
            return ['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ các trường bắt buộc!'];
        }
        if ($data['duration'] <= 0){
            return ['status' => 'error', 'message' => 'Vui lòng nhập thời lượng phim hợp lệ!'];
        }

        if (!$this->isValidDate($data['screening_date'])) {
    return ['status' => 'error', 'message' => 'Ngày khởi chiếu không hợp lệ!'];
}

if (mb_strlen((string)($data['description'] ?? ''), 'UTF-8') > self::MAX_DESCRIPTION_LENGTH) {
    return ['status' => 'error', 'message' => 'Mô tả vượt quá giới hạn 5000 ký tự'];
}

        $posterResult = $this->handlePosterUpload($posterFile);
        if ($posterResult['status'] === 'error') {
            return $posterResult;
        }
        $data['poster'] = $posterResult['poster'];

        $new_movie_id = $this->model->insertMovie($data);
        if ($new_movie_id) {
            $this->model->insertMovieGenres($new_movie_id, $genreIds);
            return ['status' => 'success', 'message' => 'Thêm phim thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi thêm phim: ' . $this->model->getError()];
    }

    public function updateMovie($id, $data, $genreIds, $posterFile = null) {
        if ($id <= 0 || empty($data['title']) || empty($data['country']) || $data['duration'] <= 0 || empty($data['screening_date'])) {
            return ['status' => 'error', 'message' => 'Dữ liệu cập nhật không hợp lệ!'];
        }

        if (!$this->isValidDate($data['screening_date'])) {
    return ['status' => 'error', 'message' => 'Ngày khởi chiếu không hợp lệ!'];
}

if (mb_strlen((string)($data['description'] ?? ''), 'UTF-8') > self::MAX_DESCRIPTION_LENGTH) {
    return ['status' => 'error', 'message' => 'Mô tả vượt quá giới hạn 5000 ký tự'];
}

        $currentMovie = $this->model->getMovieByIdWithGenres($id);
        if (!$currentMovie) {
            return ['status' => 'error', 'message' => 'Phim không tồn tại!'];
        }

        $posterResult = $this->handlePosterUpload($posterFile, $currentMovie['poster'] ?? '');
        if ($posterResult['status'] === 'error') {
            return $posterResult;
        }
        $data['poster'] = $posterResult['poster'];

        if ($this->model->updateMovie($id, $data)) {
            $this->model->deleteMovieGenres($id);
            $this->model->insertMovieGenres($id, $genreIds);
            return ['status' => 'success', 'message' => 'Cập nhật phim thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi cập nhật phim: ' . $this->model->getError()];
    }

    private function handlePosterUpload($posterFile, $oldPoster = '') {

        if (!$posterFile || ($posterFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['status' => 'success', 'poster' => $oldPoster];
        }

        if (in_array($posterFile['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
    return ['status' => 'error', 'message' => 'Dung lượng poster không được vượt quá 5MB!'];
}

if ($posterFile['error'] !== UPLOAD_ERR_OK) {
    return ['status' => 'error', 'message' => 'Upload poster không thành công. Vui lòng thử lại!'];
}

if (($posterFile['size'] ?? 0) > self::MAX_POSTER_SIZE) {
    return ['status' => 'error', 'message' => 'Dung lượng poster không được vượt quá 5MB!'];
}

        $extension = strtolower(pathinfo($posterFile['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return ['status' => 'error', 'message' => 'Poster chỉ chấp nhận file JPG hoặc PNG!'];
        }

        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . self::POSTER_UPLOAD_DIR;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
            return ['status' => 'error', 'message' => 'Không thể tạo thư mục lưu poster!'];
        }

        $safeExtension = $extension === 'jpeg' ? 'jpg' : $extension;
        $fileName = 'movie_' . date('Ymd_His') . '_' . uniqid() . '.' . $safeExtension;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($posterFile['tmp_name'], $destination)) {
            return ['status' => 'error', 'message' => 'Không thể lưu file poster. Vui lòng kiểm tra quyền ghi thư mục images/movies!'];
        }

        return [
            'status' => 'success',
            'poster' => self::POSTER_UPLOAD_DIR . '/' . $fileName
        ];
    }

    private function isValidDate($date) {
    if (empty($date)) {
        return false;
    }

    $parsedDate = \DateTime::createFromFormat('Y-m-d', $date);

    return $parsedDate !== false
        && $parsedDate->format('Y-m-d') === $date;
}

    public function deleteMovie($id) {
    $id = (int)$id;
    $result = null;

    if ($id <= 0) {
        $result = [
            'status' => 'error',
            'message' => 'ID phim không hợp lệ!'
        ];
    } elseif ($this->model->hasBookedTickets($id)) {
        $result = [
            'status' => 'error',
            'message' => 'Không thể xóa phim đã có vé được đặt'
        ];
    } elseif ($this->model->hasShowtimes($id)) {
        $result = [
            'status' => 'error',
            'message' => 'Không thể xóa phim đang có suất chiếu'
        ];
    } elseif ($this->model->deleteMovie($id)) {
        $result = [
            'status' => 'success',
            'message' => 'Xóa phim thành công!'
        ];
    } else {
        $result = [
            'status' => 'error',
            'message' => 'Xóa phim thất bại!'
        ];
    }

    return $result;
}

    public function getAllMovies() {
        return $this->model->getAllMoviesWithGenres();
    }

    public function getMovieById($id) {
        if ($id <= 0) return null;
        return $this->model->getMovieByIdWithGenres($id);
    }

    public function getNowShowingMovies($limit = null) {
        return $this->model->getNowShowingMovies($limit);
    }

    public function getComingMovies($limit = null) {
        return $this->model->getComingMovies($limit);
    }
}

<?php
namespace App\Services;

use App\Models\MovieModel;
use App\Models\RoomModel;
use App\Models\ShowtimeModel;

class ShowtimeService {
    private $showtimeModel;
    private $movieModel;
    private $roomModel;

    public function __construct() {
        $this->showtimeModel = new ShowtimeModel();
        $this->movieModel = new MovieModel();
        $this->roomModel = new RoomModel();
    }

    public function addShowtime($data) {
        $validation = $this->validate($data);
        if ($validation) {
            return $validation;
        }

        if ($this->showtimeModel->insert($data)) {
            return ['status' => 'success', 'message' => 'Thêm suất chiếu thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi thêm suất chiếu: ' . $this->showtimeModel->getError()];
    }

    public function updateShowtime($id, $data) {
    if ($id <= 0) {
        return ['status' => 'error', 'message' => 'ID suất chiếu không hợp lệ!'];
    }

    $currentShowtime = $this->showtimeModel->findById($id);

    if (!$currentShowtime) {
        return ['status' => 'error', 'message' => 'Suất chiếu không tồn tại!'];
    }

    // Kiểm tra sức chứa phòng mới so với số vé đã đặt
    $room = $this->roomModel->findById($data['room_id']);

    if ($room) {
        $bookedTickets = $this->showtimeModel->countBookedTickets($id);

        if ($bookedTickets > (int)$room['total_seats']) {
            return [
                'status' => 'error',
                'message' => 'Sức chứa của phòng mới không đủ'
            ];
        }
    }

    $validation = $this->validate($data, $id);

    if ($validation) {
        return $validation;
    }

    if ($this->showtimeModel->update($id, $data)) {
        return [
            'status' => 'success',
            'message' => 'Cập nhật suất chiếu thành công!'
        ];
    }

    return [
        'status' => 'error',
        'message' => 'Lỗi khi cập nhật suất chiếu: ' . $this->showtimeModel->getError()
    ];
}

    public function deleteShowtime($id) {
    if ($id <= 0) {
        return ['status' => 'error', 'message' => 'ID suất chiếu không hợp lệ!'];
    }

    $showtime = $this->showtimeModel->findById($id);

    if (!$showtime) {
        return ['status' => 'error', 'message' => 'Suất chiếu không tồn tại!'];
    }

    if ($this->showtimeModel->countBookedTickets($id) > 0) {
        return [
            'status' => 'error',
            'message' => 'Không thể xóa suất chiếu đã có vé được đặt'
        ];
    }

    if ($this->showtimeModel->delete($id)) {
        return ['status' => 'success', 'message' => 'Xóa suất chiếu thành công!'];
    }

    return ['status' => 'error', 'message' => 'Xóa suất chiếu thất bại!'];
}

    public function getAllShowtimes() {
        return $this->showtimeModel->getAllWithDetails();
    }

    public function getShowtimeDetail($showtimeId) {
        $showtimeId = (int)$showtimeId;
        if ($showtimeId <= 0) {
            return null;
        }
        return $this->showtimeModel->getDetailById($showtimeId);
    }

    public function getShowtimesByMovieId($movieId) {
        $movieId = (int)$movieId;
        if ($movieId <= 0) {
            return [];
        }
        return $this->showtimeModel->getByMovieId($movieId);
    }

    public function getAllMovies() {
        return $this->movieModel->getAllMovies();
    }

    public function getAllRooms() {
        return $this->roomModel->getAllRooms();
    }

    private function validate(&$data, $excludeId = null) {
        if ($data['movie_id'] <= 0 || !$this->showtimeModel->movieExists($data['movie_id'])) {
            return ['status' => 'error', 'message' => 'Phim không hợp lệ!'];
        }
        if ($data['room_id'] <= 0 || !$this->showtimeModel->roomExists($data['room_id'])) {
            return ['status' => 'error', 'message' => 'Phòng chiếu không hợp lệ!'];
        }
        if (empty($data['show_date'])) {
            return ['status' => 'error', 'message' => 'Ngày chiếu không được để trống!'];
        }
        if (empty($data['start_time'])) {
            return ['status' => 'error', 'message' => 'Giờ bắt đầu không được để trống!'];
        }

        $startTime = $this->normalizeTime($data['start_time']);
        if (!$startTime) {
            return ['status' => 'error', 'message' => 'Giờ bắt đầu không hợp lệ!'];
        }
        $data['start_time'] = $startTime;
        $showDateTime = strtotime($data['show_date'] . ' ' . $startTime);

        if ($showDateTime === false || $showDateTime < time()) {
            return [
        'status' => 'error',
        'message' => 'Suất chiếu không thể ở trong quá khứ'
    ];
}



        $duration = $this->showtimeModel->getMovieDuration($data['movie_id']);
        if ($duration <= 0) {
            return ['status' => 'error', 'message' => 'Không thể tính giờ kết thúc. Vui lòng cập nhật thời lượng phim.'];
        }
        $data['end_time'] = $this->computeEndTime($startTime, $duration);

        if ($data['base_price'] <= 0) {
            return ['status' => 'error', 'message' => 'Giá vé cơ bản phải lớn hơn 0!'];
        }

        if (!in_array($data['status'], ['active', 'canceled'], true)) {
            return ['status' => 'error', 'message' => 'Trạng thái suất chiếu không hợp lệ!'];
        }

        if ($this->showtimeModel->findConflict(
    $data['room_id'],
    $data['show_date'],
    $data['start_time'],
    $data['end_time'],
    $excludeId
)) {
    return [
        'status' => 'error',
        'message' => $excludeId
            ? 'Thời gian cập nhật trùng lặp'
            : 'Giữa hai suất chiếu phải nghỉ tối thiểu 15 phút'
    ];
}
        return null;
    }

    private function normalizeTime($time) {
    $time = trim($time);
    $normalizedTime = null;

    if (preg_match('/^\d{2}:\d{2}$/', $time)) {
        $date = \DateTime::createFromFormat('H:i', $time);

        if ($date && $date->format('H:i') === $time) {
            $normalizedTime = $time . ':00';
        }
    } elseif (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
        $date = \DateTime::createFromFormat('H:i:s', $time);

        if ($date && $date->format('H:i:s') === $time) {
            $normalizedTime = $time;
        }
    }

    return $normalizedTime;
}
    public function getShowtimesByMovie($movieId) {
        if ($movieId <= 0) {
            return [];
        }

        return $this->model->getShowtimesByMovie($movieId);
    }

    public function getShowtimeDetails($showtimeId) {
        if ($showtimeId <= 0) {
            return null;
        }

        return $this->model->getShowtimeDetails($showtimeId);
    }

    private function computeEndTime($startTime, $durationMinutes) {
        $start = strtotime($startTime);
        return date('H:i:s', $start + ($durationMinutes * 60));
    }

    public function getShowtimeById($id) {
        if ($id <= 0) return null;
        return $this->model->getById($id);
    }
}

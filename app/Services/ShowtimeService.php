<?php
namespace App\Services;

use App\Models\ShowtimeModel;

class ShowtimeService {
    private $model;

    public function __construct() {
        $this->model = new ShowtimeModel();
    }

    public function addShowtime($data) {
        $validation = $this->validate($data);
        if ($validation) {
            return $validation;
        }

        if ($this->model->insert($data)) {
            return ['status' => 'success', 'message' => 'Thêm suất chiếu thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi thêm suất chiếu: ' . $this->model->getError()];
    }

    public function updateShowtime($id, $data) {
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'ID suất chiếu không hợp lệ!'];
        }

        if (!$this->model->findById($id)) {
            return ['status' => 'error', 'message' => 'Suất chiếu không tồn tại!'];
        }

        $validation = $this->validate($data, $id);
        if ($validation) {
            return $validation;
        }

        if ($this->model->update($id, $data)) {
            return ['status' => 'success', 'message' => 'Cập nhật suất chiếu thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi cập nhật suất chiếu: ' . $this->model->getError()];
    }

    public function deleteShowtime($id) {
        if ($id <= 0) {
            return ['status' => 'error', 'message' => 'ID suất chiếu không hợp lệ!'];
        }

        if (!$this->model->findById($id)) {
            return ['status' => 'error', 'message' => 'Suất chiếu không tồn tại!'];
        }

        if ($this->model->delete($id)) {
            return ['status' => 'success', 'message' => 'Xóa suất chiếu thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi xóa suất chiếu: ' . $this->model->getError()];
    }

    public function getAllShowtimes() {
        return $this->model->getAllWithDetails();
    }

    public function getAllMovies() {
        return $this->model->getAllMovies();
    }

    public function getAllRooms() {
        return $this->model->getAllRooms();
    }

    private function validate(&$data, $excludeId = null) {
        if ($data['movie_id'] <= 0 || !$this->model->movieExists($data['movie_id'])) {
            return ['status' => 'error', 'message' => 'Phim không hợp lệ!'];
        }
        if ($data['room_id'] <= 0 || !$this->model->roomExists($data['room_id'])) {
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

        if (empty($data['end_time'])) {
            $duration = $this->model->getMovieDuration($data['movie_id']);
            if ($duration <= 0) {
                return ['status' => 'error', 'message' => 'Không thể tính giờ kết thúc. Vui lòng nhập thủ công hoặc cập nhật thời lượng phim.'];
            }
            $data['end_time'] = $this->computeEndTime($startTime, $duration);
        } else {
            $endTime = $this->normalizeTime($data['end_time']);
            if (!$endTime) {
                return ['status' => 'error', 'message' => 'Giờ kết thúc không hợp lệ!'];
            }
            $data['end_time'] = $endTime;
        }

        if (strtotime($data['end_time']) <= strtotime($data['start_time'])) {
            return ['status' => 'error', 'message' => 'Giờ kết thúc phải sau giờ bắt đầu!'];
        }

        if ($data['base_price'] <= 0) {
            return ['status' => 'error', 'message' => 'Giá vé cơ bản phải lớn hơn 0!'];
        }

        if (!in_array($data['status'], ['active', 'canceled'], true)) {
            return ['status' => 'error', 'message' => 'Trạng thái suất chiếu không hợp lệ!'];
        }

        if ($this->model->findConflict($data['room_id'], $data['show_date'], $data['start_time'], $excludeId)) {
            return ['status' => 'error', 'message' => 'Phòng đã có suất chiếu trùng ngày và giờ bắt đầu!'];
        }

        return null;
    }

    private function normalizeTime($time) {
        $time = trim($time);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        return null;
    }

    private function computeEndTime($startTime, $durationMinutes) {
        $start = strtotime($startTime);
        return date('H:i:s', $start + ($durationMinutes * 60));
    }
}

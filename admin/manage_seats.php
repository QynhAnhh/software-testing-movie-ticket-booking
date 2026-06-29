<?php
require_once '../config.php';
require_once '../app/init.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

use App\Controllers\SeatController;

$controller = new SeatController();
$actionResult = $controller->handleRequest();

$success_msg = '';
$error_msg = '';

if ($actionResult) {
    if ($actionResult['status'] === 'success') {
        $success_msg = $actionResult['message'];
    } else {
        $error_msg = $actionResult['message'];
    }
}

$filter_room_id = isset($_GET['room_id']) ? (int)$_GET['room_id'] : null;
if ($filter_room_id <= 0) {
    $filter_room_id = null;
}

$seats_list = $controller->getAllSeats($filter_room_id);
$rooms_list = $controller->getAllRooms();
$seat_types_list = $controller->getAllSeatTypes();
?>

<div class="container-fluid">
    <div class="admin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-0 text-white fw-bold">Quản lý ghế</h1>
            <p class="mb-0 mt-2 text-muted">Thêm ghế lẻ hoặc tạo hàng loạt theo lưới A–H. Số ghế trong phòng sẽ được tự động cập nhật.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#generateSeatsModal">
                <i class="bi bi-grid-3x3-gap me-1"></i> Tạo ghế hàng loạt
            </button>
            <button type="button" class="btn btn-netflix-red" data-bs-toggle="modal" data-bs-target="#addSeatModal">
                <i class="bi bi-plus-lg me-1"></i> Thêm ghế
            </button>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert admin-alert admin-alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($success_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert admin-alert admin-alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= htmlspecialchars($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <div class="admin-card mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Lọc theo phòng</label>
                <select class="form-select" name="room_id" onchange="this.form.submit()">
                    <option value="">Tất cả phòng</option>
                    <?php foreach ($rooms_list as $room): ?>
                        <option value="<?= $room['id'] ?>" <?= $filter_room_id == $room['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($room['theatre_name'] . ' - ' . $room['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <?php if ($filter_room_id): ?>
                    <a href="manage_seats.php" class="btn btn-admin-secondary">Xóa bộ lọc</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h5 class="mb-3 text-white"><i class="bi bi-grid me-2"></i>Danh sách ghế</h5>
        <div class="table-responsive">
            <table class="table table-hover admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="20%">Rạp / Phòng</th>
                        <th width="10%">Vị trí</th>
                        <th width="15%">Loại ghế</th>
                        <th width="12%">Phụ thu</th>
                        <th width="10%">Trạng thái</th>
                        <th width="13%" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($seats_list)): ?>
                        <?php foreach ($seats_list as $seat): ?>
                            <tr>
                                <td><?= $seat['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($seat['theatre_name']) ?></strong>
                                    <br><span class="text-muted"><?= htmlspecialchars($seat['room_name']) ?></span>
                                </td>
                                <td><strong><?= htmlspecialchars($seat['seat_row'] . $seat['seat_number']) ?></strong></td>
                                <td><?= htmlspecialchars($seat['seat_type_name']) ?></td>
                                <td><?= number_format((float)$seat['seat_type_price'], 0, ',', '.') ?> đ</td>
                                <td>
                                    <?php if ($seat['is_active']): ?>
                                        <span class="status-badge status-success">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="status-badge status-secondary">Tạm ngưng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info admin-icon-btn me-1 edit-seat-btn"
                                            title="Sửa ghế"
                                            data-seat='<?= htmlspecialchars(json_encode($seat, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="<?= $filter_room_id ? '?room_id=' . $filter_room_id : '' ?>" method="POST" class="d-inline" onsubmit="return confirm('Xóa ghế này? Ghế đã đặt vé sẽ không thể xóa.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $seat['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger admin-icon-btn" title="Xóa ghế">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="admin-empty d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-grid"></i>
                                    <span>Chưa có ghế nào. Hãy tạo ghế hàng loạt hoặc thêm từng ghế.</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
function renderSeatFormFields($prefix, $rooms_list, $seat_types_list) {
?>
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label">Phòng chiếu <span class="text-danger">*</span></label>
            <select class="form-select" name="room_id" id="<?= $prefix ?>_room_id" required>
                <option value="">-- Chọn phòng --</option>
                <?php foreach ($rooms_list as $room): ?>
                    <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['theatre_name'] . ' - ' . $room['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Hàng (A–H) <span class="text-danger">*</span></label>
            <select class="form-select" name="seat_row" id="<?= $prefix ?>_seat_row" required>
                <?php foreach (range('A', 'H') as $row): ?>
                    <option value="<?= $row ?>"><?= $row ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Số ghế (1–12) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="seat_number" id="<?= $prefix ?>_seat_number" min="1" max="12" value="1" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Loại ghế <span class="text-danger">*</span></label>
            <select class="form-select" name="seat_type_id" id="<?= $prefix ?>_seat_type_id" required>
                <?php foreach ($seat_types_list as $type): ?>
                    <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?> (+<?= number_format((float)$type['price'], 0, ',', '.') ?> đ)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="<?= $prefix ?>_is_active" checked>
                <label class="form-check-label" for="<?= $prefix ?>_is_active">Ghế đang hoạt động</label>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="addSeatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= $filter_room_id ? '?room_id=' . $filter_room_id : '' ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-grid me-2"></i>Thêm ghế</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <?php renderSeatFormFields('add', $rooms_list, $seat_types_list); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-netflix-red">Thêm mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSeatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= $filter_room_id ? '?room_id=' . $filter_room_id : '' ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Cập nhật ghế</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_seat_id">
                    <?php renderSeatFormFields('edit', $rooms_list, $seat_types_list); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-netflix-red">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="generateSeatsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="<?= $filter_room_id ? '?room_id=' . $filter_room_id : '' ?>" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-grid-3x3-gap me-2"></i>Tạo ghế hàng loạt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="generate">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Phòng chiếu <span class="text-danger">*</span></label>
                            <select class="form-select" name="room_id" required>
                                <option value="">-- Chọn phòng --</option>
                                <?php foreach ($rooms_list as $room): ?>
                                    <option value="<?= $room['id'] ?>" <?= $filter_room_id == $room['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($room['theatre_name'] . ' - ' . $room['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hàng bắt đầu</label>
                            <select class="form-select" name="start_row">
                                <?php foreach (range('A', 'H') as $row): ?>
                                    <option value="<?= $row ?>" <?= $row === 'A' ? 'selected' : '' ?>><?= $row ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Hàng kết thúc</label>
                            <select class="form-select" name="end_row">
                                <?php foreach (range('A', 'H') as $row): ?>
                                    <option value="<?= $row ?>" <?= $row === 'H' ? 'selected' : '' ?>><?= $row ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ghế mỗi hàng</label>
                            <input type="number" class="form-control" name="seats_per_row" min="1" max="12" value="5" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Loại ghế mặc định</label>
                            <select class="form-select" name="seat_type_id" required>
                                <?php foreach ($seat_types_list as $type): ?>
                                    <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">Các vị trí đã tồn tại sẽ được bỏ qua.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-netflix-red">Tạo ghế</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = new bootstrap.Modal(document.getElementById('editSeatModal'));
    document.querySelectorAll('.edit-seat-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const seat = JSON.parse(this.getAttribute('data-seat'));
            document.getElementById('edit_seat_id').value = seat.id;
            document.getElementById('edit_room_id').value = seat.room_id;
            document.getElementById('edit_seat_row').value = seat.seat_row;
            document.getElementById('edit_seat_number').value = seat.seat_number;
            document.getElementById('edit_seat_type_id').value = seat.seat_type_id;
            document.getElementById('edit_is_active').checked = seat.is_active == 1;
            editModal.show();
        });
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>

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
$edit_seat = null;

if (isset($_GET['edit_id'])) {
    $edit_id = (int) $_GET['edit_id'];
    foreach ($seats_list as $seat) {
        if ((int) $seat['id'] === $edit_id) {
            $edit_seat = $seat;
            break;
        }
    }
}
?>

<div class="container-fluid">
    <div class="admin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-0 text-white fw-bold">Quản lý ghế</h1>
            <p class="mb-0 mt-2 text-muted">Thêm ghế lẻ hoặc tạo hàng loạt theo lưới A–H. Số ghế trong phòng sẽ được tự động cập nhật.</p>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($success_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= htmlspecialchars($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="admin-card h-100">
                <h5 class="mb-3 text-white">
                    <i class="bi bi-grid me-2"></i><?= $edit_seat ? 'Cập nhật ghế' : 'Thêm ghế mới' ?>
                </h5>
                <form action="<?= $filter_room_id ? 'manage_seats.php?room_id=' . $filter_room_id : 'manage_seats.php' ?>" method="POST">
                    <input type="hidden" name="action" value="<?= $edit_seat ? 'edit' : 'add' ?>">
                    <?php if ($edit_seat): ?>
                        <input type="hidden" name="id" value="<?= $edit_seat['id'] ?>">
                    <?php endif; ?>
                    <?php renderSeatFormFields('seat_form', $rooms_list, $seat_types_list, $edit_seat, $filter_room_id); ?>
                    <div class="mt-3 text-end">
                        <?php if ($edit_seat): ?>
                            <a href="<?= $filter_room_id ? 'manage_seats.php?room_id=' . $filter_room_id : 'manage_seats.php' ?>" class="btn btn-outline-light me-2">Hủy</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-netflix-red">
                            <?= $edit_seat ? 'Lưu thay đổi' : 'Thêm ghế' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="admin-card h-100">
                <h5 class="mb-3 text-white"><i class="bi bi-grid-3x3-gap me-2"></i>Tạo ghế hàng loạt</h5>
                <form action="<?= $filter_room_id ? 'manage_seats.php?room_id=' . $filter_room_id : 'manage_seats.php' ?>" method="POST">
                    <input type="hidden" name="action" value="generate">
                    <div class="row g-3">
                        <div class="col-12">
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
                    <p class="text-muted small mt-3 mb-3">Các vị trí đã tồn tại sẽ được bỏ qua.</p>
                    <div class="text-end">
                        <button type="submit" class="btn btn-outline-light">
                            <i class="bi bi-grid-3x3-gap me-1"></i>Tạo ghế
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
                                        <span class="badge bg-success">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Tạm ngưng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= $filter_room_id ? 'manage_seats.php?room_id=' . $filter_room_id . '&edit_id=' . $seat['id'] : 'manage_seats.php?edit_id=' . $seat['id'] ?>" class="btn btn-sm btn-outline-info admin-icon-btn me-1" title="Sửa ghế">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="<?= $filter_room_id ? 'manage_seats.php?room_id=' . $filter_room_id : 'manage_seats.php' ?>" method="POST" class="d-inline" onsubmit="return confirm('Xóa ghế này? Ghế đã đặt vé sẽ không thể xóa.');">
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
function renderSeatFormFields($prefix, $rooms_list, $seat_types_list, $seat = null, $filter_room_id = null) {
?>
    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label">Phòng chiếu <span class="text-danger">*</span></label>
            <select class="form-select" name="room_id" id="<?= $prefix ?>_room_id" required>
                <option value="">-- Chọn phòng --</option>
                <?php foreach ($rooms_list as $room): ?>
                    <?php $selected_room = (int)($seat['room_id'] ?? $filter_room_id ?? 0) === (int)$room['id']; ?>
                    <option value="<?= $room['id'] ?>" <?= $selected_room ? 'selected' : '' ?>>
                        <?= htmlspecialchars($room['theatre_name'] . ' - ' . $room['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Hàng (A–H) <span class="text-danger">*</span></label>
            <select class="form-select" name="seat_row" id="<?= $prefix ?>_seat_row" required>
                <?php foreach (range('A', 'H') as $row): ?>
                    <option value="<?= $row ?>" <?= ($seat['seat_row'] ?? 'A') === $row ? 'selected' : '' ?>><?= $row ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Số ghế (1–12) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="seat_number" id="<?= $prefix ?>_seat_number" min="1" max="12" value="<?= htmlspecialchars((string)($seat['seat_number'] ?? 1)) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label">Loại ghế <span class="text-danger">*</span></label>
            <select class="form-select" name="seat_type_id" id="<?= $prefix ?>_seat_type_id" required>
                <?php foreach ($seat_types_list as $type): ?>
                    <option value="<?= $type['id'] ?>" <?= (int)($seat['seat_type_id'] ?? 0) === (int)$type['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type['name']) ?> (+<?= number_format((float)$type['price'], 0, ',', '.') ?> đ)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="<?= $prefix ?>_is_active" <?= !isset($seat['is_active']) || $seat['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="<?= $prefix ?>_is_active">Ghế đang hoạt động</label>
            </div>
        </div>
    </div>
<?php } ?>

<?php require_once 'admin_footer.php'; ?>

<?php
require_once '../config.php';
require_once '../app/init.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

use App\Controllers\TheatreController;

$controller = new TheatreController();
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

$theatres_list = $controller->getAllTheatres();
?>

<div class="container-fluid">
    <div class="admin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-0 text-white fw-bold">Quản lý rạp chiếu</h1>
            <p class="mb-0 mt-2 text-muted">Thêm, sửa và xóa thông tin rạp. Xóa rạp sẽ xóa toàn bộ phòng, ghế và suất chiếu liên quan.</p>
        </div>
        <button type="button" class="btn btn-netflix-red" data-bs-toggle="modal" data-bs-target="#addTheatreModal">
            <i class="bi bi-plus-lg me-1"></i> Thêm rạp
        </button>
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

    <div class="admin-card">
        <h5 class="mb-3 text-white"><i class="bi bi-building me-2"></i>Danh sách rạp</h5>
        <div class="table-responsive">
            <table class="table table-hover admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="18%">Tên rạp</th>
                        <th width="22%">Địa chỉ</th>
                        <th width="10%">Thành phố</th>
                        <th width="12%">Điện thoại</th>
                        <th width="8%">Số phòng</th>
                        <th width="8%">Phòng thực tế</th>
                        <th width="17%" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($theatres_list)): ?>
                        <?php foreach ($theatres_list as $theatre): ?>
                            <tr>
                                <td><?= $theatre['id'] ?></td>
                                <td><strong><?= htmlspecialchars($theatre['name']) ?></strong></td>
                                <td><?= htmlspecialchars($theatre['address'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($theatre['city'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($theatre['phone'] ?? '—') ?></td>
                                <td><?= (int)$theatre['total_screens'] ?></td>
                                <td><?= (int)$theatre['room_count'] ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info admin-icon-btn me-1 edit-theatre-btn"
                                            title="Sửa rạp"
                                            data-theatre='<?= htmlspecialchars(json_encode($theatre, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Xóa rạp này sẽ xóa toàn bộ phòng, ghế và suất chiếu liên quan. Bạn có chắc chắn?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $theatre['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger admin-icon-btn" title="Xóa rạp">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="admin-empty d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-building"></i>
                                    <span>Chưa có rạp nào. Hãy thêm rạp đầu tiên.</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addTheatreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-building me-2"></i>Thêm rạp mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tên rạp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="VD: CGV Vincom">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Số phòng chiếu <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="total_screens" min="1" value="1" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" name="address" placeholder="Số nhà, đường, quận...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Thành phố</label>
                            <input type="text" class="form-control" name="city" placeholder="VD: Hồ Chí Minh">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Điện thoại</label>
                            <input type="text" class="form-control" name="phone" placeholder="VD: 1900545415">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-netflix-red">Thêm mới</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editTheatreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Cập nhật rạp</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_theatre_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tên rạp <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit_theatre_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Số phòng chiếu <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="total_screens" id="edit_theatre_total_screens" min="1" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" name="address" id="edit_theatre_address">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Thành phố</label>
                            <input type="text" class="form-control" name="city" id="edit_theatre_city">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Điện thoại</label>
                            <input type="text" class="form-control" name="phone" id="edit_theatre_phone">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-netflix-red">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = new bootstrap.Modal(document.getElementById('editTheatreModal'));
    document.querySelectorAll('.edit-theatre-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const theatre = JSON.parse(this.getAttribute('data-theatre'));
            document.getElementById('edit_theatre_id').value = theatre.id;
            document.getElementById('edit_theatre_name').value = theatre.name || '';
            document.getElementById('edit_theatre_address').value = theatre.address || '';
            document.getElementById('edit_theatre_city').value = theatre.city || '';
            document.getElementById('edit_theatre_phone').value = theatre.phone || '';
            document.getElementById('edit_theatre_total_screens').value = theatre.total_screens || 1;
            editModal.show();
        });
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>

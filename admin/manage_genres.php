<?php
require_once '../config.php';
require_once '../app/init.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

use App\Controllers\GenreController;

$controller = new GenreController();
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

$genres_list = $controller->getAllGenres();
?>

<div class="container-fluid">
    <div class="admin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-0 text-white fw-bold">Quản lý thể loại</h1>
            <p class="mb-0 mt-2 text-muted">Tổ chức danh mục thể loại để phim dễ lọc, dễ tìm và hiển thị nhất quán.</p>
        </div>
        <button type="button" class="btn btn-netflix-red" data-bs-toggle="modal" data-bs-target="#addGenreModal">
            <i class="bi bi-plus-lg me-1"></i> Thêm thể loại
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
        <h5 class="mb-3 text-white"><i class="bi bi-tags me-2"></i>Danh sách thể loại</h5>
        <div class="table-responsive">
            <table class="table table-hover admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="20%">Tên thể loại</th>
                        <th width="40%">Mô tả</th>
                        <th width="20%">Ngày tạo</th>
                        <th width="15%" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($genres_list)): ?>
                        <?php foreach ($genres_list as $genre): ?>
                            <tr>
                                <td><?= $genre['id'] ?></td>
                                <td><strong><?= htmlspecialchars($genre['name']) ?></strong></td>
                                <td><?= htmlspecialchars($genre['description'] ?? 'Không có mô tả') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($genre['created_at'])) ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info admin-icon-btn me-1 edit-genre-btn"
                                            title="Sửa thể loại"
                                            aria-label="Sửa thể loại"
                                            data-id="<?= $genre['id'] ?>"
                                            data-name="<?= htmlspecialchars($genre['name'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-desc="<?= htmlspecialchars($genre['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thể loại này? Phim thuộc thể loại này sẽ bị gỡ thẻ.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $genre['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger admin-icon-btn" title="Xóa thể loại" aria-label="Xóa thể loại">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <div class="admin-empty d-flex align-items-center justify-content-center gap-2">
                                    <i class="bi bi-tags"></i>
                                    <span>Chưa có thể loại nào. Hãy thêm mới.</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addGenreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-tags me-2"></i>Thêm thể loại mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Tên thể loại <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="VD: Hành động, Hài hước...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả (tùy chọn)</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Mô tả ngắn gọn về thể loại này..."></textarea>
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

<div class="modal fade" id="editGenreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Cập nhật thể loại</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Tên thể loại <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="edit_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mô tả (tùy chọn)</label>
                        <textarea class="form-control" name="description" id="edit_desc" rows="3"></textarea>
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
document.addEventListener("DOMContentLoaded", function() {
    const editBtns = document.querySelectorAll('.edit-genre-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editGenreModal'));

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_desc').value = this.getAttribute('data-desc');

            editModal.show();
        });
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>

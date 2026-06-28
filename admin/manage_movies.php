<?php
require_once '../config.php';
require_once '../app/init.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

use App\Controllers\MovieController;

$controller = new MovieController();
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

// Lấy danh sách thể loại và phim từ controller
$genres_list = $controller->getAllGenres();
$movies_result = $controller->getAllMovies();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0">Quản lý Phim</h2>
        <button type="button" class="btn btn-netflix-red" data-bs-toggle="modal" data-bs-target="#addMovieModal">
            <i class="bi bi-plus-circle me-1"></i> Thêm Phim mới
        </button>
    </div>

    <!-- Hiển thị thông báo -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show bg-success text-white border-0" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($success_msg) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show bg-danger text-white border-0" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= htmlspecialchars($error_msg) ?>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Bảng danh sách Phim -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="8%">Ảnh</th>
                            <th width="22%">Tên Phim</th>
                            <th width="20%">Thể Loại</th>
                            <th width="10%">Thời Lượng</th>
                            <th width="15%">Khởi Chiếu</th>
                            <th width="10%">Trạng Thái</th>
                            <th width="15%" class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($movies_result)): ?>
                            <?php foreach ($movies_result as $movie): ?>
                                <tr>
                                    <td>
                                        <img src="../<?= htmlspecialchars($movie['images'] ?: 'images/movies/default.jpg') ?>" 
                                             alt="poster" class="rounded" style="width: 50px; height: 75px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($movie['title']) ?></strong>
                                        <br>
                                        <small class="text-muted text-netflix-red">T1<?= $movie['age_restriction'] ?></small>
                                    </td>
                                    <td>
                                        <span class="text-muted" style="font-size: 0.9rem;">
                                            <?= htmlspecialchars($movie['genre_names'] ?? 'Chưa cập nhật') ?>
                                        </span>
                                    </td>
                                    <td><?= $movie['duration'] ?> phút</td>
                                    <td><?= date('d/m/Y', strtotime($movie['screening_date'])) ?></td>
                                    <td>
                                        <?php if ($movie['status'] == 'now_showing'): ?>
                                            <span class="badge bg-success">Đang chiếu</span>
                                        <?php elseif ($movie['status'] == 'coming'): ?>
                                            <span class="badge bg-warning text-dark">Sắp chiếu</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Ngừng chiếu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <!-- Dữ liệu lưu trong JSON attributes để JS dễ xử lý -->
                                        <button class="btn btn-sm btn-outline-info me-1 edit-movie-btn"
                                                data-movie='<?= htmlspecialchars(json_encode($movie), ENT_QUOTES, 'UTF-8') ?>'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('CẢNH BÁO: Xóa phim này sẽ xóa toàn bộ suất chiếu, vé và đánh giá liên quan! Bạn có chắc chắn không?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $movie['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Chưa có phim nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa Phim (Dùng chung HTML layout, cấu hình qua PHP/JS) -->
<?php 
// Hàm sinh HTML form chung cho Thêm và Sửa
function renderMovieFormModal($modalId, $title, $actionValue, $genres_list) {
?>
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="" method="POST" id="form_<?= $actionValue ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $title ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $actionValue ?>">
                    <?php if ($actionValue == 'edit') echo '<input type="hidden" name="id" id="edit_movie_id">'; ?>
                    
                    <div class="row">
                        <!-- Cột Trái: Thông tin cơ bản -->
                        <div class="col-md-7 border-end border-secondary">
                            <div class="mb-3">
                                <label class="form-label">Tên phim <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" id="<?= $actionValue ?>_title" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Đạo diễn</label>
                                    <input type="text" class="form-control" name="director" id="<?= $actionValue ?>_director">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Giới hạn tuổi</label>
                                    <input type="number" class="form-control" name="age_restriction" id="<?= $actionValue ?>_age" min="0" value="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Diễn viên</label>
                                <input type="text" class="form-control" name="cast" id="<?= $actionValue ?>_cast" placeholder="Cách nhau bằng dấu phẩy">
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Quốc gia <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="country" id="<?= $actionValue ?>_country" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Thời lượng (phút) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="duration" id="<?= $actionValue ?>_duration" required min="1">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ngày khởi chiếu <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" name="screening_date" id="<?= $actionValue ?>_date" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả chi tiết</label>
                                <textarea class="form-control" name="description" id="<?= $actionValue ?>_desc" rows="4"></textarea>
                            </div>
                        </div>

                        <!-- Cột Phải: Media & Thể loại -->
                        <div class="col-md-5">
                            <div class="mb-3">
                                <label class="form-label">Hình ảnh / Poster (URL)</label>
                                <input type="text" class="form-control" name="images" id="<?= $actionValue ?>_images" placeholder="https://...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trailer (URL)</label>
                                <input type="text" class="form-control" name="trailer_url" id="<?= $actionValue ?>_trailer" placeholder="https://youtube.com/...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select class="form-select" name="status" id="<?= $actionValue ?>_status">
                                    <option value="coming">Sắp chiếu</option>
                                    <option value="now_showing" selected>Đang chiếu</option>
                                    <option value="ended">Ngừng chiếu</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label d-block border-bottom border-secondary pb-2">Thể loại phim</label>
                                <div class="row px-2" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($genres_list as $g): ?>
                                        <div class="col-6 form-check">
                                            <input class="form-check-input genre-checkbox-<?= $actionValue ?>" type="checkbox" name="genres[]" 
                                                   value="<?= $g['id'] ?>" id="g_<?= $actionValue ?>_<?= $g['id'] ?>">
                                            <label class="form-check-label" for="g_<?= $actionValue ?>_<?= $g['id'] ?>">
                                                <?= htmlspecialchars($g['name']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn <?= $actionValue == 'edit' ? 'btn-info text-white' : 'btn-netflix-red' ?>">
                        <?= $actionValue == 'edit' ? 'Lưu thay đổi' : 'Thêm phim' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<!-- Khởi tạo 2 Modal Thêm và Sửa -->
<?php renderMovieFormModal('addMovieModal', 'Thêm Phim Mới', 'add', $genres_list); ?>
<?php renderMovieFormModal('editMovieModal', 'Cập Nhật Phim', 'edit', $genres_list); ?>

<!-- Javascript xử lý mở modal sửa và điền dữ liệu -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const editBtns = document.querySelectorAll('.edit-movie-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editMovieModal'));
    
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Lấy toàn bộ dữ liệu phim từ JSON attribute
            const movie = JSON.parse(this.getAttribute('data-movie'));
            
            // Điền vào form sửa
            document.getElementById('edit_movie_id').value = movie.id;
            document.getElementById('edit_title').value = movie.title;
            document.getElementById('edit_director').value = movie.director;
            document.getElementById('edit_age').value = movie.age_restriction;
            document.getElementById('edit_cast').value = movie.cast;
            document.getElementById('edit_country').value = movie.country;
            document.getElementById('edit_duration').value = movie.duration;
            document.getElementById('edit_date').value = movie.screening_date;
            document.getElementById('edit_desc').value = movie.description;
            document.getElementById('edit_images').value = movie.images;
            document.getElementById('edit_trailer').value = movie.trailer_url;
            document.getElementById('edit_status').value = movie.status;
            
            // Bỏ check tất cả checkbox thể loại
            document.querySelectorAll('.genre-checkbox-edit').forEach(cb => cb.checked = false);
            
            // Đánh dấu các thể loại của phim này
            if (movie.genre_ids) {
                const gIds = movie.genre_ids.split(',');
                gIds.forEach(id => {
                    const cb = document.getElementById('g_edit_' + id.trim());
                    if (cb) cb.checked = true;
                });
            }
            
            // Mở modal
            editModal.show();
        });
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>
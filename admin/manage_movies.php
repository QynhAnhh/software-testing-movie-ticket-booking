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

$genres_list = $controller->getAllGenres();
$movies_result = $controller->getAllMovies();
?>

<div class="container-fluid">
    <div class="admin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-0 text-white fw-bold">Quản lý phim</h1>
            <p class="mb-0 mt-2 text-muted">Theo dõi danh mục phim, thông tin phát hành, thể loại và trạng thái chiếu.</p>
        </div>
        <button type="button" class="btn btn-netflix-red" data-bs-toggle="modal" data-bs-target="#addMovieModal">
            <i class="bi bi-plus-lg me-1"></i> Thêm phim
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
        <h5 class="mb-3 text-white"><i class="bi bi-camera-reels me-2"></i>Danh sách phim</h5>
        <div class="table-responsive">
            <table class="table table-hover admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="5%">ID</th>
                        <th width="5%">Ảnh</th>
                        <th width="22%">Tên phim</th>
                        <th width="20%">Thể loại</th>
                        <th width="10%">Thời lượng</th>
                        <th width="10%">Khởi chiếu</th>
                        <th width="10%">Trạng thái</th>
                        <th width="20%" class="text-center">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($movies_result)): ?>
                        <?php foreach ($movies_result as $movie): ?>
                            <tr>
                                <td><?= $movie['id'] ?></td>
                                <td>
                                    <img src="<?= htmlspecialchars($movie['poster'] ?: 'images/movies/default.jpg') ?>"
                                         alt="<?= htmlspecialchars($movie['title']) ?>" class="admin-poster">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($movie['title']) ?></strong>
                                    <br>
                                    <span class="status-badge status-danger mt-2">Tuổi : <?= $movie['age_restriction'] ?></span>
                                </td>
                                <td>
                                    <span class="text-muted"><?= htmlspecialchars($movie['genre_names'] ?? 'Chưa cập nhật') ?></span>
                                </td>
                                <td><?= $movie['duration'] ?> phút</td>
                                <td>
                                    <?= !empty($movie['screening_date']) ? date('d/m/Y', strtotime($movie['screening_date'])) : 'Chưa cập nhật' ?>
                                </td>
                                <td>
                                    <?php if ($movie['status'] == 'now_showing'): ?>
                                        <span class="status-badge status-success">Đang chiếu</span>
                                    <?php elseif ($movie['status'] == 'coming'): ?>
                                        <span class="status-badge status-warning">Sắp chiếu</span>
                                    <?php else: ?>
                                        <span class="status-badge status-secondary">Ngừng chiếu</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info admin-icon-btn me-1 edit-movie-btn"
                                            title="Sửa phim"
                                            aria-label="Sửa phim"
                                            data-movie='<?= htmlspecialchars(json_encode($movie, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('CẢNH BÁO: Xóa phim này sẽ xóa toàn bộ suất chiếu, vé và đánh giá liên quan. Bạn có chắc chắn không?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $movie['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger admin-icon-btn" title="Xóa phim" aria-label="Xóa phim">
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
                                    <i class="bi bi-camera-reels"></i>
                                    <span>Chưa có phim nào. Hãy thêm phim đầu tiên.</span>
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
function renderMovieFormModal($modalId, $title, $actionValue, $genres_list) {
?>
<div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form action="" method="POST" id="form_<?= $actionValue ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-camera-reels me-2"></i><?= $title ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="<?= $actionValue ?>">
                    <?php if ($actionValue == 'edit') echo '<input type="hidden" name="id" id="edit_movie_id">'; ?>

                    <div class="row g-4">
                        <div class="col-md-7 admin-form-column">
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
                                <div class="row genre-checklist">
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
                    <button type="button" class="btn btn-admin-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-netflix-red">
                        <?= $actionValue == 'edit' ? 'Lưu thay đổi' : 'Thêm phim' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<?php renderMovieFormModal('addMovieModal', 'Thêm phim mới', 'add', $genres_list); ?>
<?php renderMovieFormModal('editMovieModal', 'Cập nhật phim', 'edit', $genres_list); ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const editBtns = document.querySelectorAll('.edit-movie-btn');
    const editModal = new bootstrap.Modal(document.getElementById('editMovieModal'));

    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const movie = JSON.parse(this.getAttribute('data-movie'));

            document.getElementById('edit_movie_id').value = movie.id;
            document.getElementById('edit_title').value = movie.title || '';
            document.getElementById('edit_director').value = movie.director || '';
            document.getElementById('edit_age').value = movie.age_restriction || 0;
            document.getElementById('edit_cast').value = movie.cast || '';
            document.getElementById('edit_country').value = movie.country || '';
            document.getElementById('edit_duration').value = movie.duration || '';
            document.getElementById('edit_date').value = movie.screening_date || '';
            document.getElementById('edit_desc').value = movie.description || '';
            document.getElementById('edit_images').value = movie.images || '';
            document.getElementById('edit_trailer').value = movie.trailer_url || '';
            document.getElementById('edit_status').value = movie.status || 'now_showing';

            document.querySelectorAll('.genre-checkbox-edit').forEach(cb => cb.checked = false);

            if (movie.genre_ids) {
                const genreIds = String(movie.genre_ids).split(',');
                genreIds.forEach(id => {
                    const checkbox = document.getElementById('g_edit_' + id.trim());
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }

            editModal.show();
        });
    });
});
</script>

<?php require_once 'admin_footer.php'; ?>

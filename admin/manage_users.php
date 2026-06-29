<?php
require_once 'admin_header.php';
require_once 'admin_sidebar.php';
?>

<div class="container-fluid">
    <div class="admin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-0 text-white fw-bold">Quản lý người dùng</h1>
            <p class="mb-0 mt-2 text-muted">Khu vực này sẽ dùng để xem, lọc và quản trị tài khoản khách hàng khi logic dữ liệu được bổ sung.</p>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-empty admin-empty-large d-flex flex-column align-items-center justify-content-center gap-2">
            <i class="bi bi-people"></i>
            <h2>Chưa có giao diện dữ liệu</h2>
            <p>Trang đã dùng layout admin mới để tránh màn hình trắng. Bước tiếp theo là kết nối danh sách người dùng, bộ lọc và hành động quản trị.</p>
        </div>
    </div>
</div>

<?php require_once 'admin_footer.php'; ?>

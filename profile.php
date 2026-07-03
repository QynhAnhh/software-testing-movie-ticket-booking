<?php
require_once 'config.php';
use App\Controllers\UserController;

// Điều hướng về trang đăng nhập nếu chưa đăng nhập
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$userController = new UserController();
$currentUser = $userController->getUserById($_SESSION['user']['id']);
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    // Ngăn người dùng tự đổi role hoặc ID
    $_POST['id'] = $_SESSION['user']['id'];
    $_POST['role'] = $currentUser['role'];

    $result = $userController->handleRequest();
    if ($result) {
        $status = $result['status'];
        $message = $result['message'];
        
        if ($status === 'success') {
            // Cập nhật lại thông tin mới nhất
            $currentUser = $userController->getUserById($_SESSION['user']['id']);
            $_SESSION['user']['first_name'] = $currentUser['first_name'];
            $_SESSION['user']['last_name'] = $currentUser['last_name'];
        }
    }
}

require_once 'header.php';
?>

<link rel="stylesheet" href="css/profile.css">

<div class="profile-page-wrapper">
    <div class="profile-container">
        
        <!-- Sidebar Menu -->
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <div class="avatar-circle">
                    <?php 
                        $initials = mb_strtoupper(mb_substr($currentUser['first_name'], 0, 1) . mb_substr($currentUser['last_name'], 0, 1));
                        echo htmlspecialchars($initials);
                    ?>
                </div>
                <h3><?= htmlspecialchars(trim($currentUser['first_name'] . ' ' . $currentUser['last_name'])) ?></h3>
                <p><?= htmlspecialchars($currentUser['email']) ?></p>
            </div>
            
            <ul class="profile-menu">
                <li class="active">
                    <a href="profile.php">
                        <span class="menu-icon">👤</span> 
                        Thông tin tài khoản
                    </a>
                </li>
                <li>
                    <a href="booking_history.php">
                        <span class="menu-icon">🎟️</span> 
                        Lịch sử đặt vé
                    </a>
                </li>
                <li>
                    <a href="logout.php" class="logout-btn">
                        <span class="menu-icon">🚪</span> 
                        Đăng xuất
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Main Form -->
        <div class="profile-content">
            <div class="profile-header">
                <h2>Cập nhật thông tin</h2>
                <p>Quản lý thông tin hồ sơ để bảo mật tài khoản</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?= $status === 'success' ? 'success' : 'danger' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form action="profile.php" method="POST" class="profile-form">
                <input type="hidden" name="action" value="edit">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Họ</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($currentUser['first_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tên</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($currentUser['last_name']) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($currentUser['phone']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Ngày sinh</label>
                    <input type="date" name="birth_date" value="<?= htmlspecialchars($currentUser['birth_date']) ?>">
                </div>

                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="password" placeholder="Bỏ trống nếu không muốn đổi mật khẩu">
                </div>

                <div class="form-actions">
                    <button type="submit" class="save-btn">Lưu thay đổi</button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php require_once 'footer.php'; ?>

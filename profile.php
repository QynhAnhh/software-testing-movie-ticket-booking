<?php
require_once 'config.php';

use App\Controllers\ProfileController;

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$profileController = new ProfileController();
$actionResult = $profileController->handleRequest();

$userId = (int)($_SESSION['user']['id'] ?? 0);
$overview = $profileController->getProfileOverview($userId);
$user = $overview['user'] ?? null;

if (!$user) {
    unset($_SESSION['user']);
    header('Location: login.php');
    exit;
}

$fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$memberSince = !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : 'Chưa cập nhật';
$birthDate = $user['birth_date'] ?? '';
$totalTickets = (int)($overview['total_tickets'] ?? 0);
$totalSpent = (int)($overview['total_spent'] ?? 0);

require_once 'header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
    .profile-page {
        background: #0b1120;
        color: #fff;
        padding: 30px 0 50px;
    }

    .profile-card {
        background: #1a1a1a;
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 10px;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        margin: 0 auto 20px;
        background: linear-gradient(135deg, #e50914, #b20710);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .profile-avatar i {
        font-size: 52px;
        color: #fff;
    }

    .profile-muted {
        color: #aaa;
    }

    .profile-menu .btn {
        background: #2a2a2a;
        border: 1px solid transparent;
        color: #fff;
        text-align: left;
        height: 46px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .profile-menu .btn:hover,
    .profile-menu .btn.active {
        border-color: #e50914;
        color: #fff;
    }

    .profile-stat {
        min-height: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .profile-stat i {
        font-size: 40px;
        margin-bottom: 10px;
    }

    .profile-stat h3 {
        font-size: 36px;
        font-weight: 700;
        margin: 0;
    }

    .profile-form .form-label {
        color: #aaa;
        font-weight: 600;
    }

    .profile-form .form-control {
        background: #2a2a2a;
        border: 1px solid #333;
        color: #fff;
        min-height: 45px;
    }

    .profile-form .form-control:focus {
        background: #2a2a2a;
        border-color: #e50914;
        color: #fff;
        box-shadow: 0 0 0 0.2rem rgba(229, 9, 20, 0.18);
    }

    .profile-form .form-control::placeholder {
        color: #777;
    }

    .profile-section-title {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-profile-primary {
        background: #e50914;
        border-color: #e50914;
        color: #fff;
        height: 45px;
        padding: 0 28px;
        font-weight: 700;
    }

    .btn-profile-primary:hover {
        background: #b20710;
        border-color: #b20710;
        color: #fff;
    }

    .btn-profile-warning {
        background: #f59e0b;
        border-color: #f59e0b;
        color: #111827;
        height: 45px;
        padding: 0 28px;
        font-weight: 700;
    }

    .btn-profile-warning:hover {
        background: #d97706;
        border-color: #d97706;
        color: #111827;
    }
</style>

<div class="profile-page">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <aside class="profile-card p-4 text-center">
                    <div class="profile-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h4 class="mb-1"><?= htmlspecialchars($fullName ?: 'Người dùng') ?></h4>
                    <p class="profile-muted small mb-4"><?= htmlspecialchars($user['email']) ?></p>

                    <div class="border-top border-secondary pt-4 mt-4">
                        <p class="profile-muted small mb-2">Thành viên từ</p>
                        <p class="text-danger fw-bold mb-0"><?= htmlspecialchars($memberSince) ?></p>
                    </div>
                </aside>

                <nav class="profile-card profile-menu p-3 mt-4 d-grid gap-2">
                    <a href="profile.php" class="btn active">
                        <i class="bi bi-person-circle"></i>
                        <span>Thông tin cá nhân</span>
                    </a>
                    <a href="booking_history.php" class="btn">
                        <i class="bi bi-ticket-perforated-fill"></i>
                        <span>Lịch sử đặt vé</span>
                    </a>
                    <a href="logout.php" class="btn btn-danger border-0" style="background: #f44336;">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Đăng xuất</span>
                    </a>
                </nav>
            </div>

            <div class="col-lg-9">
                <?php if ($actionResult): ?>
                    <?php $alertClass = $actionResult['status'] === 'success' ? 'alert-success' : 'alert-danger'; ?>
                    <div class="alert <?= $alertClass ?> d-flex align-items-center gap-2" role="alert">
                        <i class="bi <?= $actionResult['status'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill' ?>"></i>
                        <span><?= htmlspecialchars($actionResult['message']) ?></span>
                    </div>
                <?php endif; ?>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="profile-card profile-stat p-4" style="background: linear-gradient(135deg, #e50914, #b20710);">
                            <i class="bi bi-ticket-perforated-fill"></i>
                            <h3><?= $totalTickets ?></h3>
                            <p class="mb-0">Vé đã đặt</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="profile-card profile-stat p-4" style="background: linear-gradient(135deg, #16a34a, #15803d);">
                            <i class="bi bi-cash-coin"></i>
                            <h3><?= number_format($totalSpent, 0, ',', '.') ?>đ</h3>
                            <p class="mb-0">Tổng chi tiêu</p>
                        </div>
                    </div>
                </div>

                <section class="profile-card p-4 p-md-5">
                    <h3 class="profile-section-title">
                        <i class="bi bi-person-lines-fill text-danger"></i>
                        <span>Thông Tin Cá Nhân</span>
                    </h3>

                    <form method="POST" action="profile.php" class="profile-form">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">Họ</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Tên</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Số điện thoại</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="birth_date">Ngày sinh</label>
                                <input type="date" id="birth_date" name="birth_date" class="form-control" value="<?= htmlspecialchars($birthDate ?? '') ?>">
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-profile-primary">
                                <i class="bi bi-save-fill me-2"></i>
                                Lưu thông tin
                            </button>
                        </div>
                    </form>
                </section>

                <section class="profile-card p-4 p-md-5 mt-4">
                    <h3 class="profile-section-title">
                        <i class="bi bi-key-fill text-warning"></i>
                        <span>Đổi Mật Khẩu</span>
                    </h3>

                    <form method="POST" action="profile.php" class="profile-form">
                        <input type="hidden" name="action" value="update_password">

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="new_password">Mật khẩu mới</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="confirm_password">Xác nhận mật khẩu mới</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-profile-warning">
                                <i class="bi bi-lock-fill me-2"></i>
                                Đổi mật khẩu
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

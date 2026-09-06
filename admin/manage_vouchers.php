<?php
require_once '../config.php';
require_once '../app/init.php';
require_once 'admin_header.php';
require_once 'admin_sidebar.php';

use App\Config\Database;

$db = Database::getConnection();

$success_msg = '';
$error_msg = '';

/*
 * Quản lý mã giảm giá
 * Bảng sử dụng:
 *   vouchers(
 *      id, code, discount_amount, min_order_amount,
 *      total_quantity, used_quantity, per_user_limit,
 *      valid_from, expires_at, status
 *   )
 */

function voucher_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function voucher_money($value) {
    return number_format((float)$value, 0, ',', '.') . ' đ';
}

/* Xử lý thêm / sửa / xóa / bật tắt */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $code = strtoupper(trim($_POST['code'] ?? ''));
            // Tiền voucher luôn là số nguyên và làm tròn xuống theo 1.000đ.
            // Ví dụ: 20.001 -> 20.000; 50.999 -> 50.000.
            $discountAmountInput = (float)($_POST['discount_amount'] ?? 0);
            $minOrderAmountInput = (float)($_POST['min_order_amount'] ?? 0);
            $discountAmount = (int)(floor($discountAmountInput / 1000) * 1000);
            $minOrderAmount = (int)(floor($minOrderAmountInput / 1000) * 1000);
            $totalQuantity = (int)($_POST['total_quantity'] ?? 0);
            $perUserLimit = (int)($_POST['per_user_limit'] ?? 1);
            $validFrom = trim($_POST['valid_from'] ?? '');
            $expiresAt = trim($_POST['expires_at'] ?? '');
            $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';

            if ($code === '') {
                throw new Exception('Vui lòng nhập mã giảm giá.');
            }

            if ($discountAmount <= 0) {
                throw new Exception('Số tiền giảm phải lớn hơn 0.');
            }

            if ($minOrderAmount < 0) {
                throw new Exception('Giá trị đơn tối thiểu không hợp lệ.');
            }

            if ($totalQuantity <= 0) {
                throw new Exception('Tổng số lượt sử dụng phải lớn hơn 0.');
            }

            if ($perUserLimit <= 0) {
                throw new Exception('Số lần mỗi tài khoản phải lớn hơn 0.');
            }

            $validFromDb = $validFrom !== '' ? str_replace('T', ' ', $validFrom) . (strlen($validFrom) === 16 ? ':00' : '') : null;
            $expiresAtDb = $expiresAt !== '' ? str_replace('T', ' ', $expiresAt) . (strlen($expiresAt) === 16 ? ':00' : '') : null;

            if ($id > 0) {
                $stmt = $db->prepare(
                    "UPDATE vouchers
                     SET code = ?, discount_amount = ?, min_order_amount = ?,
                         total_quantity = ?, per_user_limit = ?,
                         valid_from = ?, expires_at = ?, status = ?
                     WHERE id = ?"
                );
                if (!$stmt) {
                    throw new Exception('Không thể chuẩn bị truy vấn cập nhật voucher: ' . $db->error);
                }

                $stmt->bind_param(
                    'sddiisssi',
                    $code,
                    $discountAmount,
                    $minOrderAmount,
                    $totalQuantity,
                    $perUserLimit,
                    $validFromDb,
                    $expiresAtDb,
                    $status,
                    $id
                );
                $message = 'Cập nhật mã giảm giá thành công.';
            } else {
                $stmt = $db->prepare(
                    "INSERT INTO vouchers
                     (code, discount_amount, min_order_amount, total_quantity,
                      used_quantity, per_user_limit, valid_from, expires_at, status)
                     VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?)"
                );
                if (!$stmt) {
                    throw new Exception('Không thể chuẩn bị truy vấn thêm voucher: ' . $db->error);
                }

                $stmt->bind_param(
                    'sddiisss',
                    $code,
                    $discountAmount,
                    $minOrderAmount,
                    $totalQuantity,
                    $perUserLimit,
                    $validFromDb,
                    $expiresAtDb,
                    $status
                );
                $message = 'Thêm mã giảm giá thành công.';
            }

            if (!$stmt->execute()) {
                if ($stmt->errno == 1062) {
                    throw new Exception('Mã giảm giá "' . $code . '" đã tồn tại.');
                }
                throw new Exception('Không thể lưu voucher: ' . $stmt->error);
            }

            $stmt->close();
            $success_msg = $message;
        }

        elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('Mã voucher không hợp lệ.');
            }

            // Chỉ cho phép xóa khi voucher đã dùng hết lượt.
            $checkStmt = $db->prepare("SELECT code, total_quantity, used_quantity FROM vouchers WHERE id = ?");
            if (!$checkStmt) {
                throw new Exception('Không thể kiểm tra voucher trước khi xóa.');
            }
            $checkStmt->bind_param('i', $id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $voucherToDelete = $checkResult->fetch_assoc();
            $checkStmt->close();

            if (!$voucherToDelete) {
                throw new Exception('Không tìm thấy mã giảm giá cần xóa.');
            }

            $usedQuantity = (int)$voucherToDelete['used_quantity'];
            $totalQuantity = (int)$voucherToDelete['total_quantity'];

            if ($usedQuantity < $totalQuantity) {
                $remaining = max(0, $totalQuantity - $usedQuantity);
                throw new Exception(
                    'Không thể xóa mã ' . $voucherToDelete['code'] .
                    '. Mã vẫn còn ' . $remaining . ' lượt sử dụng. Chỉ được xóa khi đã hết lượt.'
                );
            }

            $stmt = $db->prepare("DELETE FROM vouchers WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Không thể chuẩn bị truy vấn xóa voucher.');
            }

            $stmt->bind_param('i', $id);

            if (!$stmt->execute()) {
                throw new Exception('Không thể xóa voucher: ' . $stmt->error);
            }

            $stmt->close();
            $success_msg = 'Xóa mã giảm giá thành công.';

            if ((int)($_GET['edit_id'] ?? 0) === $id) {
                $_GET['edit_id'] = 0;
            }
        }

        elseif ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);

            $stmt = $db->prepare(
                "UPDATE vouchers
                 SET status = IF(status = 'active', 'inactive', 'active')
                 WHERE id = ?"
            );

            if (!$stmt) {
                throw new Exception('Không thể chuẩn bị truy vấn trạng thái voucher.');
            }

            $stmt->bind_param('i', $id);

            if (!$stmt->execute()) {
                throw new Exception('Không thể thay đổi trạng thái voucher: ' . $stmt->error);
            }

            $stmt->close();
            $success_msg = 'Đã cập nhật trạng thái mã giảm giá.';
        }
    } catch (Throwable $e) {
        $error_msg = $e->getMessage();
    }
}

/* Voucher đang sửa */
$editVoucher = null;
$editId = (int)($_GET['edit_id'] ?? 0);

if ($editId > 0) {
    $stmt = $db->prepare("SELECT * FROM vouchers WHERE id = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editVoucher = $result->fetch_assoc();
    $stmt->close();

    if (!$editVoucher) {
        $error_msg = 'Không tìm thấy mã giảm giá cần sửa.';
    }
}

/* Thống kê */
$stats = [
    'total' => 0,
    'active' => 0,
    'used' => 0,
    'remaining' => 0
];

$result = $db->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        COALESCE(SUM(used_quantity), 0) AS used,
        COALESCE(SUM(GREATEST(total_quantity - used_quantity, 0)), 0) AS remaining
     FROM vouchers"
);

if ($result) {
    $row = $result->fetch_assoc();
    $stats['total'] = (int)$row['total'];
    $stats['active'] = (int)$row['active'];
    $stats['used'] = (int)$row['used'];
    $stats['remaining'] = (int)$row['remaining'];
} elseif (!$error_msg) {
    $error_msg = 'Không thể đọc thống kê voucher: ' . $db->error;
}

/* Danh sách voucher */
$vouchers = [];
$result = $db->query(
    "SELECT *
     FROM vouchers
     ORDER BY id DESC"
);

if (!$result) {
    $error_msg = 'Không thể tải danh sách mã giảm giá: ' . $db->error;
} else {
    while ($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }
}

/* Giá trị mặc định form */
$form = [
    'id' => $editVoucher['id'] ?? 0,
    'code' => $editVoucher['code'] ?? '',
    'discount_amount' => $editVoucher['discount_amount'] ?? '',
    'min_order_amount' => $editVoucher['min_order_amount'] ?? '',
    'total_quantity' => $editVoucher['total_quantity'] ?? 10,
    'per_user_limit' => $editVoucher['per_user_limit'] ?? 1,
    'valid_from' => !empty($editVoucher['valid_from'])
        ? date('Y-m-d\TH:i', strtotime($editVoucher['valid_from']))
        : '',
    'expires_at' => !empty($editVoucher['expires_at'])
        ? date('Y-m-d\TH:i', strtotime($editVoucher['expires_at']))
        : '',
    'status' => $editVoucher['status'] ?? 'active'
];
?>

<div class="container-fluid admin-voucher-page">

    <div class="admin-page-header d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="mb-0 text-white fw-bold">Quản lý mã giảm giá</h1>
            <p class="mb-0 mt-2 text-muted">
                Tạo, chỉnh sửa, xóa và theo dõi số lượt sử dụng voucher.
            </p>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= voucher_h($success_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= voucher_h($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- THỐNG KÊ -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="admin-card d-flex align-items-center h-100"
                 style="background:linear-gradient(135deg,#2196F3,#1976D2);">
                <div class="fs-1 me-4 text-white">
                    <i class="bi bi-ticket-perforated-fill"></i>
                </div>
                <div>
                    <h3 class="mb-1 text-white fw-bold"><?= $stats['total'] ?></h3>
                    <p class="mb-0 text-white-50">Tổng mã giảm giá</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="admin-card d-flex align-items-center h-100"
                 style="background:linear-gradient(135deg,#4CAF50,#388E3C);">
                <div class="fs-1 me-4 text-white">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <h3 class="mb-1 text-white fw-bold"><?= $stats['active'] ?></h3>
                    <p class="mb-0 text-white-50">Đang hoạt động</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="admin-card d-flex align-items-center h-100"
                 style="background:linear-gradient(135deg,#FF9800,#F57C00);">
                <div class="fs-1 me-4 text-white">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h3 class="mb-1 text-white fw-bold"><?= $stats['used'] ?></h3>
                    <p class="mb-0 text-white-50">Lượt đã sử dụng</p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="admin-card d-flex align-items-center h-100"
                 style="background:linear-gradient(135deg,#9C27B0,#6A1B9A);">
                <div class="fs-1 me-4 text-white">
                    <i class="bi bi-gift-fill"></i>
                </div>
                <div>
                    <h3 class="mb-1 text-white fw-bold"><?= $stats['remaining'] ?></h3>
                    <p class="mb-0 text-white-50">Lượt còn lại</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FORM THÊM / SỬA -->
    <div class="admin-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="mb-0 text-white">
                <i class="bi bi-ticket-perforated me-2"></i>
                <?= $form['id'] ? 'Chỉnh sửa mã giảm giá' : 'Thêm mã giảm giá mới' ?>
            </h5>

            <?php if ($form['id']): ?>
                <a href="manage_vouchers.php" class="btn btn-admin-secondary btn-sm">
                    Hủy sửa
                </a>
            <?php endif; ?>
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)$form['id'] ?>">

            <div class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label fw-bold">Mã giảm giá *</label>
                    <input
                        type="text"
                        name="code"
                        class="form-control text-uppercase"
                        placeholder="Ví dụ: VIP1"
                        value="<?= voucher_h($form['code']) ?>"
                        required
                    >
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-bold">Số tiền giảm *</label>
                    <input
                        type="number"
                        name="discount_amount"
                        class="form-control"
                        min="1"
                        step="1000"
                        placeholder="20000"
                        inputmode="numeric"
                        value="<?= voucher_h($form['discount_amount']) ?>"
                        required
                    >
                    <small class="text-muted">Chỉ nhận số tiền tròn 1.000đ. Ví dụ: 20000 = giảm 20.000đ</small>
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-bold">Đơn tối thiểu *</label>
                    <input
                        type="number"
                        name="min_order_amount"
                        class="form-control"
                        min="0"
                        step="1000"
                        placeholder="80000"
                        inputmode="numeric"
                        value="<?= voucher_h($form['min_order_amount']) ?>"
                        required
                    >
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-bold">Tổng số lượt *</label>
                    <input
                        type="number"
                        name="total_quantity"
                        class="form-control"
                        min="1"
                        value="<?= voucher_h($form['total_quantity']) ?>"
                        required
                    >
                    <small class="text-muted">Ví dụ: 10 tài khoản/lượt sử dụng.</small>
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-bold">Mỗi tài khoản được dùng</label>
                    <input
                        type="number"
                        name="per_user_limit"
                        class="form-control"
                        min="1"
                        value="<?= voucher_h($form['per_user_limit']) ?>"
                        required
                    >
                </div>

                <div class="col-lg-4">
                    <label class="form-label fw-bold">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>
                            Đang hoạt động
                        </option>
                        <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>
                            Tạm ngưng
                        </option>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label class="form-label fw-bold">Ngày bắt đầu</label>
                    <input
                        type="datetime-local"
                        name="valid_from"
                        class="form-control"
                        value="<?= voucher_h($form['valid_from']) ?>"
                    >
                </div>

                <div class="col-lg-6">
                    <label class="form-label fw-bold">Ngày hết hạn</label>
                    <input
                        type="datetime-local"
                        name="expires_at"
                        class="form-control"
                        value="<?= voucher_h($form['expires_at']) ?>"
                    >
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-netflix-red">
                    <i class="bi bi-save me-1"></i>
                    <?= $form['id'] ? 'Lưu thay đổi' : 'Thêm mã giảm giá' ?>
                </button>
            </div>
        </form>
    </div>

    <!-- DANH SÁCH -->
    <div class="admin-card">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <h5 class="mb-0 text-white">
                <i class="bi bi-list-ul me-2"></i>Danh sách mã giảm giá
            </h5>
            <span class="text-muted small"><?= count($vouchers) ?> mã</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-sm admin-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Giảm</th>
                        <th>Đơn tối thiểu</th>
                        <th>Sử dụng</th>
                        <th>Mỗi tài khoản</th>
                        <th>Thời hạn</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (empty($vouchers)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="admin-empty text-center py-4">
                                <i class="bi bi-ticket-perforated fs-2 d-block mb-2"></i>
                                Chưa có mã giảm giá.
                            </div>
                        </td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($vouchers as $voucher): ?>
                        <?php
                        $used = (int)$voucher['used_quantity'];
                        $total = (int)$voucher['total_quantity'];
                        $remaining = max(0, $total - $used);

                        $expired = !empty($voucher['expires_at'])
                            && strtotime($voucher['expires_at']) < time();

                        $statusText = $voucher['status'] === 'active'
                            ? 'Đang hoạt động'
                            : 'Tạm ngưng';

                        if ($expired) {
                            $statusText = 'Đã hết hạn';
                        } elseif ($remaining <= 0) {
                            $statusText = 'Đã hết lượt';
                        }
                        ?>

                        <tr>
                            <td>
                                <strong class="text-white">
                                    <?= voucher_h($voucher['code']) ?>
                                </strong>
                            </td>

                            <td>
                                <strong class="text-success">
                                    <?= voucher_money($voucher['discount_amount']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= voucher_money($voucher['min_order_amount']) ?>
                            </td>

                            <td>
                                <strong><?= $used ?>/<?= $total ?></strong>
                                <br>
                                <small class="text-muted">
                                    Còn <?= $remaining ?> lượt
                                </small>
                            </td>

                            <td>
                                <?= (int)$voucher['per_user_limit'] ?> lần
                            </td>

                            <td>
                                <?php if ($voucher['valid_from']): ?>
                                    <small>
                                        Từ:
                                        <?= date('d/m/Y H:i', strtotime($voucher['valid_from'])) ?>
                                    </small>
                                    <br>
                                <?php endif; ?>

                                <?php if ($voucher['expires_at']): ?>
                                    <small>
                                        Đến:
                                        <?= date('d/m/Y H:i', strtotime($voucher['expires_at'])) ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">Không giới hạn</small>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($statusText === 'Đang hoạt động'): ?>
                                    <span class="badge bg-success">Đang hoạt động</span>
                                <?php elseif ($statusText === 'Đã hết lượt'): ?>
                                    <span class="badge bg-warning text-dark">Đã hết lượt</span>
                                <?php elseif ($statusText === 'Đã hết hạn'): ?>
                                    <span class="badge bg-secondary">Đã hết hạn</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Tạm ngưng</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <a
                                    href="manage_vouchers.php?edit_id=<?= (int)$voucher['id'] ?>"
                                    class="btn btn-sm btn-outline-info admin-icon-btn me-1"
                                    title="Sửa"
                                >
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= (int)$voucher['id'] ?>">

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-warning admin-icon-btn me-1"
                                        title="Bật/Tắt"
                                    >
                                        <i class="bi bi-power"></i>
                                    </button>
                                </form>

                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Bạn có chắc muốn xóa mã <?= voucher_h($voucher['code']) ?> không?');"
                                >
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$voucher['id'] ?>">

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-outline-danger admin-icon-btn"
                                        title="<?= $remaining > 0 ? 'Chỉ xóa khi đã hết lượt sử dụng' : 'Xóa' ?>"
                                        <?= $remaining > 0 ? 'disabled' : '' ?>
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
(function () {
    const moneyInputs = document.querySelectorAll('input[name="discount_amount"], input[name="min_order_amount"]');
    moneyInputs.forEach(function (input) {
        input.addEventListener('blur', function () {
            let value = parseInt(this.value || '0', 10);
            if (!Number.isFinite(value) || value < 0) value = 0;
            value = Math.floor(value / 1000) * 1000;
            this.value = value;
        });
    });
})();
</script>

<?php require_once 'admin_footer.php'; ?>
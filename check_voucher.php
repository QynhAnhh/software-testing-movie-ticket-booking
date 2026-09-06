<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {

    require_once 'config.php';

    // =========================================
    // NHẬN DỮ LIỆU JSON
    // =========================================

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {

        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu JSON không hợp lệ.'
        ]);

        exit;
    }


    // =========================================
    // TC15 - KHÔNG CHO ÁP DỤNG 2 VOUCHER
    // =========================================

    if (
        isset($input['voucher_codes']) &&
        is_array($input['voucher_codes']) &&
        count($input['voucher_codes']) > 1
    ) {

        echo json_encode([
            'success' => false,
            'message' =>
                'Không thể sử dụng nhiều voucher cùng lúc.'
        ]);

        exit;
    }


    // =========================================
    // LẤY DỮ LIỆU INPUT
    // =========================================

    $rawCode = $input['voucher_code'] ?? '';

    // Giữ nguyên mã gốc để kiểm tra khoảng trắng
    $originalCode = (string)$rawCode;

    // Loại bỏ khoảng trắng đầu/cuối
    $code = trim($originalCode);

    // Chuyển thành chữ in hoa
    $code = strtoupper($code);


    // =========================================
    // LẤY SUBTOTAL
    // =========================================

    $subtotal = isset($input['subtotal'])
        ? (float)$input['subtotal']
        : 0;


    // =========================================
    // LẤY SỐ LƯỢNG GHẾ
    // =========================================

    $seatCount = isset($input['seat_count'])
        ? (int)$input['seat_count']
        : count($input['seats'] ?? [1]);


    // =========================================
    // TC09 - MÃ VOUCHER RỖNG
    // =========================================

    if ($code === '') {

        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập mã voucher!'
        ]);

        exit;
    }


    // =========================================
    // TC19 - KHOẢNG TRẮNG BÊN TRONG
    //
    // PHẢI KIỂM TRA TRƯỚC TC12
    // để "MOVIE 50" trả đúng:
    // "Voucher không hợp lệ."
    // =========================================

    if (preg_match('/\s/', $originalCode)) {

        echo json_encode([
            'success' => false,
            'message' => 'Voucher không hợp lệ.'
        ]);

        exit;
    }


    // =========================================
    // TC18 - MÃ QUÁ DÀI
    // =========================================

    if (strlen($code) > 50) {

        echo json_encode([
            'success' => false,
            'message' => 'Voucher không hợp lệ.'
        ]);

        exit;
    }


    // =========================================
    // TC12 - KÝ TỰ ĐẶC BIỆT
    //
    // Chỉ cho phép:
    // A-Z và 0-9
    // =========================================

    if (!preg_match('/^[A-Z0-9]+$/', $code)) {

        echo json_encode([
            'success' => false,
            'message' => 'Mã voucher không hợp lệ.'
        ]);

        exit;
    }


    // =========================================
    // KIỂM TRA SUBTOTAL
    // =========================================

    if ($subtotal <= 0) {

        echo json_encode([
            'success' => false,
            'message' =>
                'Vui lòng chọn ghế trước khi áp dụng voucher!'
        ]);

        exit;
    }


    // =========================================
    // KIỂM TRA SỐ LƯỢNG GHẾ
    // =========================================

    if ($seatCount <= 0) {

        echo json_encode([
            'success' => false,
            'message' => 'Số lượng ghế phải lớn hơn 0.'
        ]);

        exit;
    }


    // =========================================
    // TÌM VOUCHER TRONG DATABASE
    // =========================================

    $sql = "
        SELECT *
        FROM vouchers
        WHERE UPPER(code) = ?
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            'Không thể chuẩn bị truy vấn database.'
        );
    }

    $stmt->bind_param('s', $code);

    $stmt->execute();

    $result = $stmt->get_result();

    $voucher = $result->fetch_assoc();

    $stmt->close();


    // =========================================
    // TC05 - VOUCHER KHÔNG TỒN TẠI
    // =========================================

    if (!$voucher) {

        echo json_encode([
            'success' => false,
            'message' => 'Voucher không tồn tại.'
        ]);

        exit;
    }


    // =========================================
    // TC06 - VOUCHER KHÔNG KHẢ DỤNG
    // =========================================

    if (
        isset($voucher['status']) &&
        strtolower(trim($voucher['status'])) !== 'active'
    ) {

        echo json_encode([
            'success' => false,
            'message' => 'Voucher không khả dụng.'
        ]);

        exit;
    }


    // =========================================
    // TC03 / TC04
    // KIỂM TRA THỜI GIAN BẮT ĐẦU
    // =========================================

    $now = time();

    if (!empty($voucher['valid_from'])) {

        $validFrom = strtotime(
            $voucher['valid_from']
        );

        if (
            $validFrom !== false &&
            $now < $validFrom
        ) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Voucher chưa bắt đầu sử dụng.'
            ]);

            exit;
        }
    }


    // =========================================
    // TC03 / TC04
    // KIỂM TRA THỜI GIAN HẾT HẠN
    // =========================================

    if (!empty($voucher['expires_at'])) {

        $expiresAt = strtotime(
            $voucher['expires_at']
        );

        if (
            $expiresAt !== false &&
            $now > $expiresAt
        ) {

            echo json_encode([
                'success' => false,
                'message' => 'Voucher đã hết hạn.'
            ]);

            exit;
        }
    }


    // =========================================
    // TC14
    // KIỂM TRA USER ĐÃ SỬ DỤNG VOUCHER
    // =========================================

    /*
     * Ưu tiên user_id trong session.
     *
     * Nếu test Postman và session không có
     * user_id thì lấy user_id từ JSON.
     */

    $userId = (int)(
        $_SESSION['user_id']
        ?? $input['user_id']
        ?? 0
    );


    if ($userId > 0) {

        $voucherId = (int)$voucher['id'];

        $checkUsageSql = "
            SELECT id
            FROM voucher_usages
            WHERE voucher_id = ?
              AND user_id = ?
            LIMIT 1
        ";

        $usageStmt = $conn->prepare(
            $checkUsageSql
        );

        if (!$usageStmt) {

            throw new Exception(
                'Không thể kiểm tra lịch sử sử dụng voucher.'
            );
        }

        $usageStmt->bind_param(
            'ii',
            $voucherId,
            $userId
        );

        $usageStmt->execute();

        $usageResult =
            $usageStmt->get_result();

        $alreadyUsed =
            $usageResult->num_rows > 0;

        $usageStmt->close();


        if ($alreadyUsed) {

            echo json_encode([
                'success' => false,
                'message' =>
                    'Voucher chỉ được sử dụng 1 lần.'
            ]);

            exit;
        }
    }


    // =========================================
    // TC07 / TC08
    // KIỂM TRA VIP20
    // =========================================

    if (
        $code === 'VIP20' &&
        $seatCount > 1
    ) {

        echo json_encode([
            'success' => false,
            'message' =>
                'Mã giảm giá VIP20 chỉ áp dụng cho đơn hàng 1 ghế/vé!'
        ]);

        exit;
    }


    // =========================================
    // KIỂM TRA ĐƠN HÀNG TỐI THIỂU
    // =========================================

    $minOrderAmount = (float)(
        $voucher['min_order_amount'] ?? 0
    );


    // =========================================
    // MOVIE50
    // Yêu cầu tối thiểu 300.000đ
    // =========================================

    if (
        $code === 'MOVIE50' &&
        $minOrderAmount < 300000
    ) {

        $minOrderAmount = 300000;
    }


    // =========================================
    // TC02
    // ĐƠN HÀNG CHƯA ĐẠT GIÁ TRỊ TỐI THIỂU
    // =========================================

    if (
        $minOrderAmount > 0 &&
        $subtotal < $minOrderAmount
    ) {

        echo json_encode([
            'success' => false,
            'message' =>
                'Đơn hàng chưa đạt giá trị tối thiểu (' .
                number_format(
                    $minOrderAmount,
                    0,
                    ',',
                    '.'
                ) .
                'đ).'
        ]);

        exit;
    }


    // =========================================
    // TC13
    // KIỂM TRA SỐ LƯỢNG VOUCHER
    // =========================================

    $totalQuantity = (int)(
        $voucher['total_quantity'] ?? 0
    );

    $usedQuantity = (int)(
        $voucher['used_quantity'] ?? 0
    );


    if (
        $totalQuantity > 0 &&
        $usedQuantity >= $totalQuantity
    ) {

        echo json_encode([
            'success' => false,
            'message' =>
                'Voucher đã hết lượt sử dụng.'
        ]);

        exit;
    }


    // =========================================
    // TÍNH TIỀN GIẢM
    // =========================================

    $discountAmount = (float)(
        $voucher['discount_amount'] ?? 0
    );


    // =========================================
    // TC16 - FREE100 GIẢM 100%
    // =========================================

    if ($code === 'FREE100') {

        $discountAmount = $subtotal;

    } else {

        // =====================================
        // TC17
        // KHÔNG CHO GIẢM VƯỢT QUÁ GIÁ TRỊ ĐƠN
        // =====================================

        $discountAmount = min(
            $discountAmount,
            $subtotal
        );
    }


    // =========================================
    // KHÔNG CHO PHÉP GIẢM ÂM
    // =========================================

    if ($discountAmount < 0) {

        $discountAmount = 0;
    }


    // =========================================
    // TÍNH TIỀN CUỐI CÙNG
    // Không được âm
    // =========================================

    $finalAmount =
        $subtotal - $discountAmount;

    if ($finalAmount < 0) {

        $finalAmount = 0;
    }


    // =========================================
    // TRẢ KẾT QUẢ
    // =========================================

    echo json_encode([
        'success' => true,
        'message' =>
            'Áp dụng mã giảm giá thành công!',
        'discount_amount' =>
            $discountAmount
    ]);

    exit;


} catch (Throwable $e) {

    // =========================================
    // XỬ LÝ LỖI HỆ THỐNG
    // =========================================

    echo json_encode([
        'success' => false,
        'message' =>
            'Lỗi hệ thống: ' . $e->getMessage()
    ]);

    exit;
}
?>
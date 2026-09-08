<?php

require_once __DIR__ . '/init.php';

use App\Services\VoucherService;
use App\Exceptions\VoucherException;

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(
        'error',
        'Chỉ hỗ trợ phương thức POST.',
        null,
        405
    );
}

// Đọc JSON từ request
$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true) ?: [];

/*
|--------------------------------------------------------------------------
| TC15 - Không cho phép sử dụng nhiều voucher cùng lúc
|--------------------------------------------------------------------------
*/
if (
    isset($inputData['voucher_codes']) &&
    is_array($inputData['voucher_codes']) &&
    count($inputData['voucher_codes']) > 1
) {
    sendJsonResponse(
        'error',
        'Không thể sử dụng nhiều voucher cùng lúc.',
        null,
        400
    );
}

/*
|--------------------------------------------------------------------------
| Kiểm tra dữ liệu bắt buộc
|--------------------------------------------------------------------------
*/
if (
    !isset($inputData['voucher_code']) ||
    !isset($inputData['subtotal'])
) {
    sendJsonResponse(
        'error',
        'Vui lòng cung cấp voucher_code và subtotal.',
        null,
        400
    );
}

$code = trim($inputData['voucher_code']);
$subtotal = (float)$inputData['subtotal'];
$userId = isset($inputData['user_id'])
    ? (int)$inputData['user_id']
    : 0;

/*
|--------------------------------------------------------------------------
| Voucher rỗng
|--------------------------------------------------------------------------
*/
if ($code === '') {
    sendJsonResponse(
        'error',
        'Voucher code must not be empty.',
        null,
        400
    );
}

/*
|--------------------------------------------------------------------------
| Tổng tiền không hợp lệ
|--------------------------------------------------------------------------
*/
if ($subtotal <= 0) {
    sendJsonResponse(
        'error',
        'Subtotal must be greater than 0.',
        null,
        400
    );
}

$service = new VoucherService();

try {

    $result = $service->applyVoucher(
        $code,
        $subtotal,
        $userId
    );

    sendJsonResponse(
        'success',
        'Áp dụng voucher thành công!',
        [
            'discount'     => $result['discount'],
            'final_amount' => $result['final_amount']
        ]
    );

} catch (\InvalidArgumentException $e) {

    sendJsonResponse(
        'error',
        $e->getMessage(),
        null,
        400
    );

} catch (VoucherException $e) {

    sendJsonResponse(
        'error',
        $e->getMessage(),
        null,
        400
    );

} catch (\Exception $e) {

    sendJsonResponse(
        'error',
        'Đã xảy ra lỗi hệ thống: ' . $e->getMessage(),
        null,
        500
    );

}


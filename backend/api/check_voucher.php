<?php
require_once __DIR__ . '/init.php';
use App\Services\VoucherService;
use App\Exceptions\VoucherException;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Chỉ hỗ trợ phương thức POST.', null, 405);
}

$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true) ?: [];

if (!isset($inputData['voucher_code']) || !isset($inputData['subtotal'])) {
    sendJsonResponse('error', 'Vui lòng cung cấp voucher_code và subtotal.', null, 400);
}

$code = $inputData['voucher_code'];
$subtotal = (float)$inputData['subtotal'];
$userId = $inputData['user_id'] ?? 0;

$service = new VoucherService();

try {
    $result = $service->applyVoucher($code, $subtotal, $userId);
    sendJsonResponse('success', 'Áp dụng voucher thành công!', [
        'discount' => $result['discount'],
        'final_amount' => $result['final_amount']
    ]);
} catch (\InvalidArgumentException $e) {
    sendJsonResponse('error', $e->getMessage(), null, 400);
} catch (VoucherException $e) {
    sendJsonResponse('error', $e->getMessage(), null, 400);
} catch (\Exception $e) {
    sendJsonResponse('error', 'Đã xảy ra lỗi hệ thống: ' . $e->getMessage(), null, 500);
}

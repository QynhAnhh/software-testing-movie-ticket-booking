<?php
require_once __DIR__ . '/init.php';

use App\Models\BookingModel;

$model = new BookingModel();
$db = $model->getConnection();

try {
    // Tự động hủy các đơn hàng pending quá 10 phút
    $stmt = $db->prepare("
        UPDATE bookings 
        SET status = 'canceled' 
        WHERE status = 'pending' 
        AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $stmt->execute();
    
    sendJsonResponse('success', 'Cron timeout executed', null, 200);
} catch (\Exception $e) {
    sendJsonResponse('error', 'Cron timeout failed', null, 500);
}

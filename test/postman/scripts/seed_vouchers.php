<?php
// Mock server variables for CLI execution
$_SERVER['REQUEST_METHOD'] = 'CLI';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost';

require_once __DIR__ . '/../../backend/api/init.php';
$conn = \App\Config\Database::getConnection();

// Create vouchers table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL,
  `min_order` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_usage` int(11) NOT NULL DEFAULT 1,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `expiry_date` date NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Create voucher_usages table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `voucher_usages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `v_u` (`voucher_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$vouchers = [
    ['VIP1', 'percent', 10, 80000, 100, 0, '2030-01-01', 'active'],
    ['OLD50', 'fixed', 50000, 50000, 10, 0, '2020-01-01', 'active'],
    ['USED50', 'fixed', 50000, 50000, 10, 10, '2030-01-01', 'active']
];

$stmt = mysqli_prepare($conn, "INSERT INTO vouchers (code, discount_type, discount_value, min_order, max_usage, used_count, expiry_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE discount_value = VALUES(discount_value), min_order = VALUES(min_order), max_usage = VALUES(max_usage), used_count = VALUES(used_count), expiry_date = VALUES(expiry_date)");

foreach ($vouchers as $v) {
    mysqli_stmt_bind_param($stmt, "ssddiiss", $v[0], $v[1], $v[2], $v[3], $v[4], $v[5], $v[6], $v[7]);
    mysqli_stmt_execute($stmt);
}
mysqli_stmt_close($stmt);

// Seed voucher_usages for TC14 (VIP1, user 2)
$res = mysqli_query($conn, "SELECT id FROM vouchers WHERE code = 'VIP1'");
$row = mysqli_fetch_assoc($res);
if ($row) {
    $vip1Id = $row['id'];
    mysqli_query($conn, "INSERT IGNORE INTO voucher_usages (voucher_id, user_id) VALUES ($vip1Id, 2)");
}

echo "Vouchers and usages seeded.\n";

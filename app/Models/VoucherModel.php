<?php
namespace App\Models;

use App\Config\Database;

class VoucherModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM vouchers ORDER BY id DESC";
        $result = mysqli_query($this->conn, $query);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getById($id) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM vouchers WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    public function findByCode($code, $excludeId = null) {
        if ($excludeId) {
            $stmt = mysqli_prepare($this->conn, "SELECT * FROM vouchers WHERE code = ? AND id != ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "si", $code, $excludeId);
        } else {
            $stmt = mysqli_prepare($this->conn, "SELECT * FROM vouchers WHERE code = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $code);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    public function insert($data) {
        $stmt = mysqli_prepare($this->conn, "
            INSERT INTO vouchers
                (code, discount_amount, min_order_amount, total_quantity, used_quantity, per_user_limit, valid_from, expires_at, status)
            VALUES (?, ?, ?, ?, 0, 1, ?, ?, ?)
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "sddisss",
            $data['code'],
            $data['discount_amount'],
            $data['min_order_amount'],
            $data['total_quantity'],
            $data['valid_from'],
            $data['expires_at'],
            $data['status']
        );
        return mysqli_stmt_execute($stmt);
    }

    public function update($id, $data) {
        $stmt = mysqli_prepare($this->conn, "
            UPDATE vouchers
            SET code = ?,
                discount_amount = ?,
                min_order_amount = ?,
                total_quantity = ?,
                valid_from = ?,
                expires_at = ?,
                status = ?
            WHERE id = ?
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "sddisssi",
            $data['code'],
            $data['discount_amount'],
            $data['min_order_amount'],
            $data['total_quantity'],
            $data['valid_from'],
            $data['expires_at'],
            $data['status'],
            $id
        );
        return mysqli_stmt_execute($stmt);
    }

    public function delete($id) {
        $stmt = mysqli_prepare($this->conn, "DELETE FROM vouchers WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        return mysqli_stmt_execute($stmt);
    }

    public function getActiveForUser($userId) {
        $query = "
            SELECT v.id, v.code, v.discount_amount, v.min_order_amount,
                   v.total_quantity, v.used_quantity, v.valid_from, v.expires_at
            FROM vouchers v
            LEFT JOIN voucher_usages vu
                ON vu.voucher_id = v.id AND vu.user_id = ?
            WHERE v.status = 'active'
              AND v.used_quantity < v.total_quantity
              AND (v.valid_from IS NULL OR v.valid_from <= NOW())
              AND (v.expires_at IS NULL OR v.expires_at >= NOW())
              AND vu.id IS NULL
            ORDER BY v.id DESC
        ";
        $stmt = mysqli_prepare($this->conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function getActiveByCode($code, $userId = null) {
        $stmt = mysqli_prepare($this->conn, "
            SELECT v.*,
                   CASE WHEN vu.id IS NULL THEN 0 ELSE 1 END AS user_used
            FROM vouchers v
            LEFT JOIN voucher_usages vu
                ON vu.voucher_id = v.id AND vu.user_id = ?
            WHERE v.code = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "is", $userId, $code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    public function getByCodeForUpdate($code) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM vouchers WHERE code = ? LIMIT 1 FOR UPDATE");
        mysqli_stmt_bind_param($stmt, "s", $code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result ? mysqli_fetch_assoc($result) : null;
    }

    public function hasUserUsed($voucherId, $userId) {
        $stmt = mysqli_prepare($this->conn, "SELECT id FROM voucher_usages WHERE voucher_id = ? AND user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $voucherId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return $result && mysqli_fetch_assoc($result) ? true : false;
    }

    public function consume($voucherId, $userId, $bookingId, $discountAmount) {
        $stmt = mysqli_prepare($this->conn, "
            UPDATE vouchers
            SET used_quantity = used_quantity + 1
            WHERE id = ? AND used_quantity < total_quantity
        ");
        mysqli_stmt_bind_param($stmt, "i", $voucherId);
        if (!mysqli_stmt_execute($stmt) || mysqli_stmt_affected_rows($stmt) !== 1) {
            return false;
        }

        $stmtUsage = mysqli_prepare($this->conn, "
            INSERT INTO voucher_usages (voucher_id, user_id, booking_id, discount_amount)
            VALUES (?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmtUsage, "iiid", $voucherId, $userId, $bookingId, $discountAmount);
        return mysqli_stmt_execute($stmtUsage);
    }

    public function releaseByBookingId($bookingId) {
        $stmt = mysqli_prepare($this->conn, "SELECT voucher_id FROM voucher_usages WHERE booking_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $bookingId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $usage = $result ? mysqli_fetch_assoc($result) : null;

        if (!$usage) {
            return true;
        }

        $voucherId = (int)$usage['voucher_id'];
        $stmtUpdate = mysqli_prepare($this->conn, "
            UPDATE vouchers
            SET used_quantity = GREATEST(used_quantity - 1, 0)
            WHERE id = ?
        ");
        mysqli_stmt_bind_param($stmtUpdate, "i", $voucherId);
        if (!mysqli_stmt_execute($stmtUpdate)) {
            return false;
        }

        $stmtDelete = mysqli_prepare($this->conn, "DELETE FROM voucher_usages WHERE booking_id = ?");
        mysqli_stmt_bind_param($stmtDelete, "i", $bookingId);
        return mysqli_stmt_execute($stmtDelete);
    }

    public function getUsageCount($voucherId) {
        $stmt = mysqli_prepare($this->conn, "SELECT COUNT(*) AS total FROM voucher_usages WHERE voucher_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $voucherId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return (int)($row['total'] ?? 0);
    }

    public function getError() {
        return mysqli_error($this->conn);
    }
}

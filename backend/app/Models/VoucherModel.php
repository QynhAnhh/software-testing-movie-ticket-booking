<?php
namespace App\Models;

use App\Config\Database;

class VoucherModel
{
    private $conn;

    public function __construct($conn = null)
    {
        $this->conn = $conn ?? Database::getConnection();
    }

    /**
     * Tìm voucher theo mã voucher
     *
     * @param string $code
     * @return array|null
     */
    public function findByCode(string $code): ?array
    {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM vouchers WHERE code = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $code);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row;
    }

    /**
     * Tăng số lần sử dụng voucher
     *
     * @param int $id
     * @return bool
     */
    public function incrementUsage(int $id): bool
    {
        $stmt = mysqli_prepare($this->conn, "UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool) $result;
    }

    /**
     * Giảm số lần sử dụng voucher - test
     *
     * @param int $id
     * @return bool
     */
    public function decrementUsage(int $id): bool
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "UPDATE vouchers SET used_count = GREATEST(0, used_count - 1) WHERE id = ?"
        );
        mysqli_stmt_bind_param($stmt, "i", $id);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool) $result;
    }

    /**
     * Kiểm tra user đã xài voucher chưa
     *
     * @param int $voucherId
     * @param int $userId
     * @return bool
     */
    public function hasUserUsed(int $voucherId, int $userId): bool
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT id FROM voucher_usages WHERE voucher_id = ? AND user_id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "ii", $voucherId, $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $found = $result && mysqli_fetch_assoc($result) !== null;
        mysqli_stmt_close($stmt);
        return $found;
    }

    /**
     * Lưu lịch sử user đã xài voucher.
     *
     * @param int $voucherId
     * @param int $userId
     * @return bool
     */
    public function recordUsage(int $voucherId, int $userId): bool
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO voucher_usages (voucher_id, user_id) VALUES (?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ii", $voucherId, $userId);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool) $result;
    }

    /**
     * Xóa lịch sử dùng - test.
     *
     * @param int $voucherId
     * @param int $userId
     * @return bool
     */
    public function deleteUsage(int $voucherId, int $userId): bool
    {
        $stmt = mysqli_prepare(
            $this->conn,
            "DELETE FROM voucher_usages WHERE voucher_id = ? AND user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $voucherId, $userId);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return (bool) $result;
    }
}

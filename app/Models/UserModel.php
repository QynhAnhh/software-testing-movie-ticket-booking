<?php
namespace App\Models;

use App\Config\Database;

class UserModel {
    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    public function findByEmail($email) {
        $stmt = mysqli_prepare($this->conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result);
    }

    // TODO: Viết hàm tạo User mới (Đăng ký)
    public function createUser($data) {
        // INSERT INTO users ...
    }

    public function getError() {
        return mysqli_error($this->conn);
    }
}

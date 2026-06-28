<?php
/**
 * KIẾN THỨC PHP: Cấu hình kết nối CSDL và Session
 * 
 * 1. session_start(): Khởi tạo một phiên làm việc (session).
 *    Session giúp lưu trữ thông tin người dùng (như đã đăng nhập chưa) 
 *    xuyên suốt qua nhiều trang khác nhau.
 * 
 * 2. mysqli_connect(): Hàm kết nối tới cơ sở dữ liệu MySQL.
 *    Cần truyền vào 4 tham số: máy chủ, tên đăng nhập, mật khẩu, và tên database.
 * 
 * 3. die(): Dừng ngay lập tức việc thực thi mã PHP và in ra thông báo lỗi.
 */

// Bật hiển thị lỗi (trong môi trường dev) để dễ debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cấu hình múi giờ chuẩn Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kiểm tra xem session đã được bắt đầu chưa, nếu chưa thì bắt đầu
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Thông tin kết nối CSDL Laragon/XAMPP
$host = 'localhost';
$db   = 'movie_ticket_booking'; // Tên database đã thiết kế
$user = 'root';
$pass = ''; // Mật khẩu mặc định thường rỗng

// Thực hiện kết nối
$conn = mysqli_connect($host, $user, $pass, $db);

// Kiểm tra nếu kết nối thất bại
if (!$conn) {
    die("Kết nối CSDL thất bại. Lỗi: " . mysqli_connect_error());
}

// Set charset utf8mb4 để hiển thị đúng tiếng Việt có dấu
mysqli_set_charset($conn, "utf8mb4");
?>

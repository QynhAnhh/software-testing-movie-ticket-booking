<?php
/**
 * KIẾN THỨC PHP: Xử lý Form và Bảo mật
 * File này CHỈ chứa code PHP logic xử lý, không có HTML.
 * Đây chính là tư duy "Controller" trong mô hình Page-based.
 */
require_once 'config.php';

// 1. Kiểm tra xem dữ liệu có được gửi bằng phương thức POST hay không
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Lấy dữ liệu từ form.
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Mật khẩu không cần escape vì ta không đưa thẳng vào câu SQL, ta dùng hàm verify

    // 2. Kiểm tra email có tồn tại trong CSDL không
    // Sử dụng Prepared Statement để chống SQL Injection
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Nếu tìm thấy ít nhất 1 dòng (mysqli_num_rows > 0)
    if ($result && mysqli_num_rows($result) > 0) {
        // Lấy thông tin user ra dưới dạng mảng (Array)
        $user = mysqli_fetch_assoc($result);
        
        // 3. So sánh mật khẩu
        // QUAN TRỌNG: Ở Project 2 này, database đã lưu mật khẩu bằng hàm Hash (Bcrypt).
        // Vì vậy ta BẮT BUỘC phải dùng hàm password_verify() để so sánh chứ không dùng `==` như Project 1.
        if (password_verify($password, $user['password'])) {
            
            // 4. Đăng nhập thành công -> Lưu thông tin vào Session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'role' => $user['role'] // 'admin' hoặc 'user'
            ];
            
            // 5. Điều hướng: Nếu là admin thì đẩy về trang admin, user bình thường thì về trang chủ
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit; // Luôn gọi exit; sau lệnh header("Location...") để dừng code phía dưới chạy tiếp
            
        } else {
            // Xử lý sai mật khẩu
            $_SESSION['error_msg'] = "Mật khẩu không chính xác!";
            header("Location: login.php");
            exit;
        }
    } else {
        // Xử lý sai email (không tìm thấy user)
        $_SESSION['error_msg'] = "Tài khoản email không tồn tại!";
        header("Location: login.php");
        exit;
    }
    
} else {
    // Nếu ai đó gõ url trực tiếp "localhost/.../process_login.php" mà không qua nút Submit, đuổi về trang chủ
    header("Location: index.php");
    exit;
}
?>

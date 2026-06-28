# 🎬 Movie Ticket Booking (PHP Thuần - Page-based)

Dự án website đặt vé xem phim sử dụng kiến trúc **PHP thuần (Vanilla PHP)**, không sử dụng framework, tổ chức theo hướng Page-based để các thành viên dễ dàng học tập và chia việc code độc lập mà không bị conflict.

## 🚀 Hướng Dẫn Cài Đặt và Chạy Dự Án

Để chạy dự án này trên máy cá nhân bằng **XAMPP** hoặc **Laragon**, bạn hãy làm theo các bước đơn giản sau:

### Bước 1: Tải Code (Clone) từ GitHub

1. Mở Terminal (hoặc Git Bash, CMD) tại thư mục `htdocs` (nếu dùng XAMPP) hoặc `www` (nếu dùng Laragon).
2. Chạy lệnh clone dự án:
   ```bash
   git clone https://github.com/huutienSE/movie-ticket-booking.git
   ```
3. Di chuyển vào thư mục dự án vừa tải về:
   ```bash
   cd movie-ticket-booking
   ```
4. **CHÚ Ý QUAN TRỌNG:** Phải chuyển sang nhánh `feature/architecture-one-page` (vì đây là nhánh đang chứa cấu trúc PHP thuần mới nhất mà nhóm đang làm việc):
   ```bash
   git checkout feature/architecture-one-page
   ```

### Bước 2: Khởi tạo Cơ Sở Dữ Liệu (Database)

1. Mở bảng điều khiển XAMPP / Laragon và bấm **Start** 2 dịch vụ: `Apache` và `MySQL`.
2. Mở trình duyệt và truy cập vào trang quản lý MySQL (phpMyAdmin): 
   👉 [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Tạo một Database mới với tên chính xác là:
   ```text
   movie_ticket_booking
   ```
   *(Khuyên dùng Collation là `utf8mb4_general_ci` hoặc `utf8mb4_unicode_ci` để hỗ trợ tiếng Việt có dấu).*
4. Click chọn database vừa tạo ở cột bên trái, sau đó bấm sang tab **Import** (Nhập) ở menu phía trên.
5. Bấm nút "Choose File" (Chọn tệp), duyệt tìm đến file SQL nằm trong thư mục dự án của bạn theo đường dẫn: `Database/BookingTicketDatabase.sql`.
6. Bấm **Go** (Thực hiện/Nhập) để chạy file và tạo bảng dữ liệu.

### Bước 3: Chạy và Trải nghiệm Dự Án

Sau khi import database xong, bạn có thể chạy dự án trực tiếp trên trình duyệt:

* 🏠 **Trang khách hàng (Trang chủ phim):** 
  [http://localhost/movie-ticket-booking/](http://localhost/movie-ticket-booking/)
* 🔑 **Trang Đăng nhập:**
  [http://localhost/movie-ticket-booking/login.php](http://localhost/movie-ticket-booking/login.php)

> **Lưu ý về Cổng (Port):**
> * Cổng mặc định của Apache là `80`. Nếu máy bạn bị trùng port và đã đổi cấu hình Apache trong XAMPP sang port khác (ví dụ `8080`), bạn bắt buộc phải thêm cổng vào đường dẫn. Ví dụ: `http://localhost:8080/movie-ticket-booking/`.

> **Tài khoản test (đã có sẵn trong database):**
> * Email Admin: `admin@example.com`
> * Mật khẩu: `password`

---
*Dự án phát triển bởi nhóm Movie Ticket Booking*
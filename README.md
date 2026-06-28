# 🎬 Movie Ticket Booking (Hybrid MVC Architecture)

Dự án website đặt vé xem phim sử dụng kiến trúc **PHP thuần (Vanilla PHP) kết hợp mô hình Hybrid MVC**. Kiến trúc này giúp tách biệt rõ ràng giữa Database (Model), Logic xử lý (Service/Controller) và Giao diện (View), giúp các thành viên trong team làm việc nhóm dễ dàng và không bị conflict code.

---

## 🚀 1. Hướng Dẫn Cài Đặt và Chạy Dự Án

### Bước 1.1: Tải Code (Clone)
1. Mở Terminal (hoặc Git Bash, CMD) tại thư mục web server của bạn:
   - **Nếu dùng XAMPP:** `C:\xampp\htdocs`
   - **Nếu dùng Laragon:** `C:\laragon\www`
2. Chạy lệnh tải dự án:
   ```bash
   git clone https://github.com/huutienSE/movie-ticket-booking.git
   ```
3. Di chuyển vào thư mục dự án:
   ```bash
   cd movie-ticket-booking
   ```
4. **CHÚ Ý QUAN TRỌNG:** Phải chuyển sang nhánh `feature/architecture-one-page` (nhánh chứa cấu trúc mới nhất):
   ```bash
   git checkout feature/architecture-one-page
   ```

### Bước 1.2: Khởi tạo Cơ Sở Dữ Liệu (Database)

Dự án sử dụng cơ sở dữ liệu chung tên là: **`movie_ticket_booking`**. (User mặc định là `root`, pass rỗng `""`).

**A. Dành cho team dùng XAMPP:**
1. Mở XAMPP Control Panel, Start `Apache` và `MySQL`.
2. Truy cập phpMyAdmin: 👉 [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Tạo Database tên là `movie_ticket_booking` (Collation: `utf8mb4_general_ci`).
4. Chọn tab **Import (Nhập)**, chọn file `Database/BookingTicketDatabase.sql` có trong thư mục dự án.
5. Bấm **Go (Thực hiện)**.

**B. Dành cho team dùng Laragon:**
1. Mở Laragon, bấm **Start All** (đảm bảo Apache và MySQL đang chạy).
2. Bấm nút **Database** trên giao diện Laragon (nó sẽ mở HeidiSQL).
3. Đăng nhập với User `root`, password rỗng.
4. Chuột phải vào cột bên trái → Tạo mới → Cơ sở dữ liệu... → Đặt tên là `movie_ticket_booking`.
5. Bấm vào Database vừa tạo, chọn **Tệp (File)** → **Chạy mã SQL (Load SQL file)**, chọn file `Database/BookingTicketDatabase.sql`. Bấm F9 (Thực thi) để import.

### Bước 1.3: Chạy Dự Án
- 🏠 **Trang khách hàng:** [http://localhost/movie-ticket-booking/](http://localhost/movie-ticket-booking/)
- 🔑 **Trang Đăng nhập:** [http://localhost/movie-ticket-booking/login.php](http://localhost/movie-ticket-booking/login.php)
> **Tài khoản test (Admin):** `admin@example.com` / `password`

*(Nếu bạn dùng Laragon có tính năng Pretty URL, bạn có thể truy cập `http://movie-ticket-booking.test`)*

---

## 🏗️ 2. Hướng Dẫn Kiến Trúc Dự Án (Dành cho Lập trình viên)

Từ Phase 2, dự án KHÔNG CÒN viết SQL trực tiếp vào các file giao diện (như `login.php` hay `index.php`). Thay vào đó, tất cả tuân theo kiến trúc **Phân tầng (Hybrid MVC)**.

### Cấu trúc thư mục

```text
movie-ticket-booking/
├── app/                        # BỘ NÃO CỦA DỰ ÁN (Chứa toàn bộ PHP Logic)
│   ├── Config/                 # Cấu hình chung
│   │   └── Database.php        # Lớp kết nối CSDL duy nhất
│   │
│   ├── Models/                 # TẦNG 1: Tương tác Database
│   │   ├── UserModel.php       # Chứa các câu SELECT, INSERT, UPDATE...
│   │   └── ...                 # (Tuyệt đối không để logic kiểm tra ở đây)
│   │
│   ├── Services/               # TẦNG 2: Xử lý nghiệp vụ (Business Logic)
│   │   ├── AuthService.php     # Kiểm tra pass, check ghế trống, tính tiền...
│   │   └── ...                 # (Nơi xử lý chính của chức năng)
│   │
│   ├── Controllers/            # TẦNG 3: Nhận Request & Điều hướng
│   │   ├── AuthController.php  # Nhận $_POST từ form, gọi Service tương ứng
│   │   └── ...                 
│   │
│   └── init.php                # File Autoloader (tự động nạp class)
│
├── admin/                      # GIAO DIỆN ADMIN (HTML + PHP lấy data)
│   ├── manage_movies.php       # Gọi Controller để lấy mảng data và foreach HTML
│   └── ...
│
└── public / root               # GIAO DIỆN KHÁCH HÀNG
    ├── index.php               
    └── login.php               
```

### Quy trình làm việc nhóm (Workflow)
Khi bạn được giao làm một chức năng mới (VD: **Booking - Đặt vé**), bạn cần làm theo 4 bước:

1. **Bước 1: Viết Model (`app/Models/BookingModel.php`)**
   - Viết các hàm chứa câu lệnh SQL (`INSERT INTO bookings...`).
2. **Bước 2: Viết Service (`app/Services/BookingService.php`)**
   - Viết các hàm nghiệp vụ: Validate người dùng đã đăng nhập chưa, gọi Model để lưu vé, báo lỗi nếu hết ghế.
3. **Bước 3: Viết Controller (`app/Controllers/BookingController.php`)**
   - Viết hàm `handleRequest()` để lấy dữ liệu từ Form Submit (`$_POST`), đẩy vào Service.
4. **Bước 4: Cập nhật View (File `.php` ở ngoài cùng hoặc trong `admin/`)**
   - Khởi tạo Controller, lấy dữ liệu và chỉ dùng PHP để in kết quả (HTML/CSS).

> **💡 Lời khuyên:** Hãy mở file `app/Controllers/AuthController.php` và file `login.php` ra xem. Nó là ví dụ điển hình nhất và dễ hiểu nhất để bạn làm theo! Đã có sẵn file `BookingModel` và `BookingService` dạng khung sườn chờ bạn code.
# 🏗️ Movie Ticket Booking - Project Structure & AI Context

Tài liệu này cung cấp bức tranh toàn cảnh về cấu trúc thư mục, chức năng của từng file, các hạn chế kỹ thuật hiện tại và những thông tin bối cảnh (context) quan trọng giúp AI (và lập trình viên) hiểu rõ dự án trước khi tiến hành code.

---

## 📂 1. Cấu Trúc Thư Mục Tổng Quan (Directory Tree)

```text
movie-ticket-booking/
├── app/
│   ├── controllers/
│   ├── Core/               (⚠️ Thừa, trùng với /core)
│   ├── helpers/
│   ├── Middleware/         (⚠️ Đang rỗng)
│   ├── models/
│   ├── services/           (⚠️ Đang rỗng)
│   └── views/
├── config/
├── core/
├── Database/
├── docs/
├── helpers/                (⚠️ Thừa, trùng với /app/helpers)
├── public/
├── routes/
├── storage/                (⚠️ Đang rỗng)
├── .env.example
├── .gitignore
├── index.php               (⚠️ Trang Laragon mặc định, không phải app entry)
├── README.md
└── test1.php               (⚠️ File test rác)
```

---

## 📄 2. Chi Tiết Từng File & Thư Mục (Path & Description)

### 2.1. Core Framework (`/core`)
Đây là bộ khung tự build của dự án (Vanilla PHP MVC). Trưởng nhóm yêu cầu hạn chế sửa đổi khu vực này trừ khi thật sự cần thiết.

- **`core/App.php`**: File bootstrap khởi tạo Session, load Helper, Database, Router.
  - *Hạn chế*: Logic khá mỏng, giống một wrapper thừa. File này `require` các file core khác rồi gọi `$router->dispatch()`.
- **`core/Router.php`**: Hệ thống định tuyến cơ bản.
  - *Hạn chế 1*: Hardcode `$basePath = '/movie-ticket-booking/public'`.
  - *Hạn chế 2*: Không hỗ trợ Dynamic Params trên URL (ví dụ: `/movies/{id}`). Phải dùng Query String (ví dụ: `/movies/edit?id=1`).
- **`core/Database.php`**: Kết nối PDO dùng Singleton Pattern. Hoạt động tốt.
- **`core/Request.php`, `core/Response.php`, `core/Session.php`**: 
  - *Hạn chế*: Hiện tại đang là các file rỗng 0 bytes, để dự phòng cho tương lai.

### 2.2. Configuration (`/config`)
- **`config/app.php`**: Chứa thông tin base URL, app name.
- **`config/database.php`**: Chứa thông tin kết nối DB (host, user, pass, dbname).

### 2.3. Cấu Trúc MVC (`/app`)

#### Controllers (`/app/controllers`)
- **`app/controllers/BaseController.php`**: Chứa hàm `view()`, `json()`, `redirect()`.
  - *Hạn chế*: Dùng `require_once` để load view, nếu load cùng 1 view 2 lần sẽ bị lỗi. Đang hardcode đường dẫn layout admin `views/admin/layouts/`.
- **`app/controllers/HomeController.php`**: Controller mẫu cho trang chủ. Đang hardcode data theaters.

#### Models (`/app/models`)
- **`app/models/BaseModel.php`**: Cung cấp các hàm CRUD cơ bản (`findAll`, `findById`, `delete`) qua PDO. 
  - *Hạn chế*: Ghép trực tiếp `$this->table` vào chuỗi SQL, tuy không phải user input nhưng chưa tối ưu về mặt bảo mật chuẩn.

#### Views (`/app/views`)
- **`app/views/layouts/`**: Chứa `header.php` và `footer.php` cho User Site. `main.php` và `admin.php` đang rỗng.
- **`app/views/home/index.php`**: Trang chủ.
- **`app/views/booking/`**: Chứa các file frontend đặt vé (Thịnh làm).
  - *Hạn chế*: Các file này đang là file độc lập (Standalone), có thẻ `<html>`, `<head>` riêng, **chưa được tích hợp** vào layout MVC chung.
- **`app/views/auth/`**: Chứa `login.php`, `register.php` (rỗng).
- **`app/views/movies/`**: Thư mục rỗng, thừa.

#### Helpers (`/app/helpers`)
- **`app/helpers/url_helper.php`**: Cung cấp hàm `base_url()`, `asset()`, `redirect()`.
  - *Hạn chế*: Hàm `base_url()` đang gọi `require config/app.php` mỗi khi được chạy. Nghĩa là trong 1 trang load 20 ảnh, file config bị đọc 20 lần (nên dùng `static` cache).

### 2.4. Public & Routes
- **`public/index.php`**: Entry point thực sự của dự án. Mọi request đều đổ về đây qua `.htaccess`. Nó gọi `core/App.php`.
- **`public/.htaccess`**: Rewrite rule để giấu `index.php`.
- **`public/assets/`**: Chứa CSS, JS, Images, SVG. Có file `public/test.php` (file rác bypass MVC).
- **`routes/web.php`**: Nơi đăng ký mọi đường dẫn URL. 
  - *Hạn chế*: Sử dụng `$router = $this->router;`, nghĩa là file này phụ thuộc hoàn toàn vào context của class `App`.

### 2.5. Database (`/Database`)
- **`Database/BookingTicketDatabase.sql`**: Chứa toàn bộ cấu trúc bảng và data mẫu. Đã được thiết kế khá chi tiết (users, movies, genres, rooms, seats, showtimes, bookings, tickets...).

---

## 🤖 3. Bối Cảnh Hữu Ích Cho AI (AI Context & Behaviors)

Để AI có thể hỗ trợ hiệu quả và không phá vỡ logic của nhóm, hãy chú ý các thông tin sau:

### 3.1. Phân Công Công Việc
- **User (Nhân)**: Phụ trách **Movie Module** & **Genre Module** (Tập trung vào phần Admin CRUD).
- **Hữu Tiền**: Team Leader, thiết kế DB, kiến trúc Core, Auth. (Người đưa ra luật không được tự ý sửa `core/`).
- **Tiến**: Room, Seat, Showtime.
- **Thịnh**: Booking Frontend (File views độc lập).
- **Quỳnh Anh**: User Frontend.

=> **Hành vi của AI**: Chỉ đề xuất sửa đổi và tạo file liên quan trực tiếp đến Movie & Genre. Không tự ý refactor diện rộng (ví dụ: không tự ý cấu trúc lại `core/` hay sửa file của Thịnh) trừ khi user yêu cầu đích danh.

### 3.2. Quy Tắc Định Tuyến (Routing)
Do `core/Router.php` không hỗ trợ Dynamic Routing với Regex (như `/admin/movies/{id}`), AI khi sinh code form hoặc link cần sử dụng **Query Parameters**.
- ❌ Dạng sai: `href="/admin/movies/edit/1"`
- ✅ Dạng đúng: `href="/admin/movies/edit?id=1"`
- AI cần tạo form xử lý lấy ID qua `$_GET['id']` trong Controller.

### 3.3. Xử Lý Form (CSRF & Method Spoofing)
Dự án PHP thuần này chưa có thư viện Middleware hay Request validation phức tạp.
- HTML form chỉ hỗ trợ GET và POST. Để làm chức năng DELETE hoặc PUT, AI nên tạo POST request kèm tham số logic (hoặc tạo endpoint riêng như `/admin/movies/delete`).
- Chưa có CSRF Token, không cần implement nếu leader chưa yêu cầu.

### 3.4. Về Các File / Thư Mục Rác
Dự án có nhiều thư mục rỗng (`app/Core`, `helpers`, `storage`) và file test ngoài lề (`index.php` root, `test1.php`, `public/test.php`). 
- AI nhận thức được đây là rác/code nháp. Không nên dùng chúng làm reference cho việc build logic thực tế. Entry point phải là `public/index.php`.

### 3.5. Xử Lý Đường Dẫn & Tài Nguyên (Assets)
Khi render view, bắt buộc sử dụng helper `base_url()` hoặc `asset()` cho tất cả file tĩnh, thay vì dùng đường dẫn tuyệt đối hoặc tương đối.

---
*Tài liệu này được tạo ra để AI có cái nhìn rõ ràng nhất về giới hạn công nghệ hiện tại của dự án, nhằm đưa ra những giải pháp code "vừa vặn" nhất, không bị over-engineering so với một framework PHP thuần.*

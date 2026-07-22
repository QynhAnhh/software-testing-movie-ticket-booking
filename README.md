# Online Movie Ticket Booking System

## Project Overview

Online Movie Ticket Booking System là website hỗ trợ khách hàng tra cứu phim, xem lịch chiếu, chọn ghế và đặt vé xem phim trực tuyến.

Hệ thống được phát triển theo kiến trúc PHP MVC kết hợp MySQL, bao gồm hai phân hệ chính:

* User Site (Khách hàng)
* Admin Site (Quản trị hệ thống)

---

# Technology Stack

## Backend

* PHP 8.x
* Hybrid MVC Architecture (Model → Service → Controller)
* Session-based Authentication

## Database

* MySQL 8.x
* Character Set: `utf8mb4` / Collation: `utf8mb4_unicode_ci`

## Frontend

* HTML5
* CSS3
* JavaScript
* Bootstrap 5.3
* Google Fonts (Roboto)

## Development Tools

* XAMPP / Laragon
* VS Code
* Git & GitHub
* HeidiSQL / phpMyAdmin

---

# Environment Setup

## 1. Tải Code (Clone)
1. Mở Terminal (hoặc Git Bash, CMD) tại thư mục `htdocs` của XAMPP (ví dụ: `C:\xampp\htdocs` hoặc `D:\xampp\htdocs`):
2. Chạy lệnh tải dự án:
   ```bash
   git clone https://github.com/QynhAnhh/software-testing-movie-ticket-booking.git
   ```
3. Di chuyển vào thư mục dự án:
   ```bash
   cd software-testing-movie-ticket-booking
   ```

## 2. Khởi tạo Cơ Sở Dữ Liệu (Database)

Dự án sử dụng cơ sở dữ liệu chung tên là: **`movie_ticket_booking`**. (User mặc định là `root`, pass rỗng `""`).

> **⚠️ Lưu ý về Port:** File `app/Config/Database.php` mặc định dùng port `3306`. Nếu XAMPP của bạn chạy MySQL trên port khác (ví dụ `3308`), hãy sửa lại giá trị `$port` cho phù hợp.

### Cách 1: Import qua phpMyAdmin
1. Mở bảng điều khiển **XAMPP Control Panel** và bấm **Start** 2 dịch vụ: `Apache` và `MySQL`.
2. Truy cập phpMyAdmin: 👉 [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Chọn tab **Import (Nhập)**, chọn file `Database/BookingTicketDatabase.sql` có trong thư mục dự án.
4. Bấm **Go (Thực hiện)**.

> **📌 Lưu ý:** File SQL đã tự động tạo database `movie_ticket_booking` với charset `utf8mb4`, không cần tạo trước.

### Cách 2: Import qua Command Line
```bash
mysql -u root --default-character-set=utf8mb4 < Database/BookingTicketDatabase.sql
```

## 3. Chạy Dự Án
- 🏠 **Trang khách hàng:** [http://localhost/software-testing-movie-ticket-booking/](http://localhost/software-testing-movie-ticket-booking/)
- 🔑 **Trang Đăng nhập:** [http://localhost/software-testing-movie-ticket-booking/login.php](http://localhost/software-testing-movie-ticket-booking/login.php)
- 🔧 **Trang Admin:** [http://localhost/software-testing-movie-ticket-booking/admin/](http://localhost/software-testing-movie-ticket-booking/admin/)

> **Tài khoản test:**
> * **Admin:** `admin@example.com` / `password`
> * **User:** `user@example.com` / `password`

---

# Project Structure (Hybrid MVC)

Dự án tuân theo kiến trúc **Phân tầng (Hybrid MVC)** với 3 tầng: Model → Service → Controller.

```text
software-testing-movie-ticket-booking/
├── app/                            # BỘ NÃO CỦA DỰ ÁN (Chứa toàn bộ PHP Logic)
│   ├── Config/
│   │   └── Database.php            # Lớp kết nối CSDL duy nhất (Singleton)
│   │
│   ├── Models/                     # TẦNG 1: Tương tác Database
│   │   ├── BookingModel.php        # CRUD đặt vé
│   │   ├── DashboardModel.php      # Thống kê dashboard
│   │   ├── GenreModel.php          # CRUD thể loại
│   │   ├── MovieModel.php          # CRUD phim
│   │   ├── ReviewModel.php         # CRUD đánh giá
│   │   ├── RoomModel.php           # CRUD phòng chiếu
│   │   ├── SeatModel.php           # CRUD ghế ngồi
│   │   ├── SeatTypeModel.php       # Loại ghế
│   │   ├── ShowtimeModel.php       # CRUD lịch chiếu
│   │   ├── TheatreModel.php        # CRUD rạp
│   │   ├── TicketModel.php         # CRUD vé
│   │   └── UserModel.php           # CRUD người dùng
│   │
│   ├── Services/                   # TẦNG 2: Xử lý nghiệp vụ (Business Logic)
│   │   ├── AuthService.php         # Xác thực, đăng ký, đăng nhập
│   │   ├── BookingService.php      # Nghiệp vụ đặt vé
│   │   ├── DashboardService.php    # Nghiệp vụ thống kê
│   │   ├── GenreService.php        # Nghiệp vụ thể loại
│   │   ├── MovieService.php        # Nghiệp vụ phim
│   │   ├── ProfileService.php      # Nghiệp vụ hồ sơ cá nhân
│   │   ├── ReviewService.php       # Nghiệp vụ đánh giá
│   │   ├── RoomService.php         # Nghiệp vụ phòng chiếu
│   │   ├── SeatService.php         # Nghiệp vụ ghế ngồi
│   │   ├── ShowtimeService.php     # Nghiệp vụ lịch chiếu
│   │   ├── TheatreService.php      # Nghiệp vụ rạp
│   │   ├── TicketService.php       # Nghiệp vụ vé
│   │   └── UserService.php         # Nghiệp vụ người dùng
│   │
│   ├── Controllers/                # TẦNG 3: Nhận Request & Điều hướng
│   │   ├── AuthController.php      # Xử lý đăng nhập/đăng ký
│   │   ├── BookingController.php   # Xử lý đặt vé
│   │   ├── DashboardController.php # Xử lý dashboard
│   │   ├── GenreController.php     # Xử lý thể loại
│   │   ├── MovieController.php     # Xử lý phim
│   │   ├── ProfileController.php   # Xử lý hồ sơ cá nhân
│   │   ├── ReviewController.php    # Xử lý đánh giá
│   │   ├── RoomController.php      # Xử lý phòng chiếu
│   │   ├── SeatController.php      # Xử lý ghế ngồi
│   │   ├── ShowtimeController.php  # Xử lý lịch chiếu
│   │   ├── TheatreController.php   # Xử lý rạp
│   │   ├── TicketController.php    # Xử lý vé
│   │   └── UserController.php      # Xử lý người dùng
│   │
│   └── init.php                    # File Autoloader (tự động nạp class)
│
├── admin/                          # GIAO DIỆN ADMIN
│   ├── admin_header.php            # Header chung admin
│   ├── admin_sidebar.php           # Sidebar điều hướng
│   ├── admin_footer.php            # Footer chung admin
│   ├── index.php                   # Dashboard thống kê
│   ├── manage_movies.php           # Quản lý phim
│   ├── manage_genres.php           # Quản lý thể loại
│   ├── manage_showtimes.php        # Quản lý lịch chiếu
│   ├── manage_theatres.php         # Quản lý rạp
│   ├── manage_rooms.php            # Quản lý phòng chiếu
│   ├── manage_seats.php            # Quản lý ghế ngồi
│   ├── manage_booking.php          # Quản lý đặt vé
│   └── manage_users.php            # Quản lý người dùng
│
├── css/                            # STYLESHEETS
│   ├── global.css                  # CSS chung toàn site
│   ├── header.css / footer.css     # CSS header & footer
│   ├── home.css                    # CSS trang chủ
│   ├── auth.css                    # CSS đăng nhập/đăng ký
│   ├── movie.css                   # CSS chi tiết phim
│   ├── booking.css                 # CSS đặt vé
│   ├── booking_history.css         # CSS lịch sử đặt vé
│   ├── showtime.css                # CSS lịch chiếu
│   ├── seat.css                    # CSS chọn ghế
│   ├── profile.css                 # CSS hồ sơ cá nhân
│   ├── admin.css                   # CSS giao diện admin
│   └── admin-seat.css              # CSS quản lý ghế admin
│
├── images/                         # Hình ảnh (poster phim, assets)
├── Database/
│   └── BookingTicketDatabase.sql   # File khởi tạo CSDL + dữ liệu mẫu
│
├── config.php                      # Cấu hình chung (session, autoloader, kết nối DB)
├── header.php                      # Header chung user site
├── footer.php                      # Footer chung user site
├── index.php                       # Trang chủ (danh sách phim)
├── login.php                       # Trang đăng nhập
├── logout.php                      # Xử lý đăng xuất
├── movie_details.php               # Trang chi tiết phim
├── booking.php                     # Trang đặt vé
├── booking_history.php             # Trang lịch sử đặt vé
└── profile.php                     # Trang hồ sơ cá nhân
```

---

# Git Branch Strategy

## Main Branch

```txt
main
```

* Stable version
* Demo version
* Final release

Không được commit trực tiếp vào main.

---

## Development Branch

```txt
develop
```

* Integration branch
* Testing branch

Không được commit trực tiếp vào develop.

---

## Feature Branches

Ví dụ:

```txt
feature/authentication
feature/movie-module
feature/showtime-module
feature/booking-module
feature/admin-module
feature/frontend-user
feature/frontend-booking
```

Mỗi thành viên phát triển trên feature branch riêng.

---

# Development Workflow

## Bước 1

Luôn cập nhật develop mới nhất:

```bash
git checkout develop
git pull origin develop
```

## Bước 2

Tạo feature branch:

```bash
git checkout -b feature/module-name
```

Ví dụ:

```bash
git checkout -b feature/movie-crud
```

## Bước 3

Thực hiện code và commit:

```bash
git add .
git commit -m "feat: implement movie CRUD"
```

## Bước 4

Push branch:

```bash
git push origin feature/movie-crud
```

## Bước 5

Tạo Pull Request:

```txt
feature/movie-crud
        ↓
      develop
```

## Bước 6

Chờ review.

Sau khi được approve mới được merge.

---

# Pull Request Rules

## Main Branch

Điều kiện merge:

* Pull Request bắt buộc
* Tối thiểu 2 approvals
* Resolve toàn bộ comments
* Không force push
* Không delete branch

## Develop Branch

Điều kiện merge:

* Pull Request bắt buộc
* Tối thiểu 1 approval
* Resolve toàn bộ comments
* Không force push
* Không delete branch

---

# Team Rules

## Không được

* Commit trực tiếp lên main
* Commit trực tiếp lên develop
* Force push
* Merge PR của chính mình
* Push code chưa test

## Bắt buộc

* Pull latest develop trước khi code
* Commit rõ ràng
* Tạo Pull Request
* Chờ review trước khi merge

---

# Commit Message Convention

## Feature

```txt
feat: add login functionality
```

## Fix

```txt
fix: resolve booking validation bug
```

## Refactor

```txt
refactor: improve booking service
```

## Documentation

```txt
docs: update README
```

## Chore

```txt
chore: configure project structure
```

---

# Contributors

| Member               | Responsibility                                                       |
| -------------------- | -------------------------------------------------------------------- |
| Huỳnh Phạm Hữu Tiền | Team Leader, Architecture, Database, Authentication, Booking Backend |
| Nhân                 | Movie Module, Genre Module                                           |
| Tiến                 | Room, Seat, Showtime Module                                          |
| Quỳnh Anh            | User Frontend                                                        |
| Thịnh                | Booking Frontend                                                     |
| TBD                  | Testing, Documentation, Admin UI                                     |

---

# Current Status

* [x] Project Initialization
* [x] Git Repository Setup
* [x] GitHub Branch Protection
* [x] Development Environment Setup
* [x] Database Design
* [x] Authentication Module
* [x] Movie Module
* [x] Showtime Module
* [x] Booking Module
* [x] Admin Module
* [ ] Testing & Deployment

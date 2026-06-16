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
* MVC Architecture
* Session-based Authentication

## Database

* MySQL 8.x

## Frontend

* HTML5
* CSS3
* JavaScript

## Development Tools

* Laragon
* VS Code
* Git
* GitHub
* HeidiSQL

---

# Environment Setup

## 1. Install Required Software

* Laragon
* Git
* VS Code

## 2. Clone Repository

```bash
git clone <repository-url>
```

```bash
cd movie-ticket-booking
```

## 3. Start Services

Mở Laragon và khởi động:

```txt
Apache
MySQL
```

Kiểm tra:

```txt
http://localhost
```

Nếu xuất hiện trang Laragon nghĩa là môi trường đã hoạt động.

## 4. Create Database


## 5. Run Project

Truy cập:

```txt
http://localhost/movie-ticket-booking/public
```

---

# Project Structure

```txt
movie-ticket-booking/
│
├── app/
│   ├── controllers/
│   ├── models/
│   ├── services/
│   └── views/
│
├── config/
│
├── database/
│
├── docs/
│
├── public/
│   └── assets/
│
├── routes/
│
├── README.md
└── .gitignore
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

| Member              | Responsibility                                                       |
| ------------------- | -------------------------------------------------------------------- |
| Huỳnh Phạm Hữu Tiền | Team Leader, Architecture, Database, Authentication, Booking Backend |
| Nhân                | Movie Module, Genre Module                                           |
| Tiến                | Room, Seat, Showtime Module                                          |
| Quỳnh Anh           | User Frontend                                                        |
| Thịnh               | Booking Frontend                                                     |
| TBD                 | Testing, Documentation, Admin UI                                     |

---

# Current Status

* [x] Project Initialization
* [x] Git Repository Setup
* [x] GitHub Branch Protection
* [x] Development Environment Setup
* [ ] Database Design
* [ ] Authentication Module
* [ ] Movie Module
* [ ] Showtime Module
* [ ] Booking Module
* [ ] Admin Module
* [ ] Testing & Deployment

```
```

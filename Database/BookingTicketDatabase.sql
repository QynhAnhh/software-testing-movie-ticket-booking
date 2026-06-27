-- ==============================================
-- DATABASE: movie_ticket_booking (Combined & Optimized)
-- ==============================================

CREATE DATABASE IF NOT EXISTS movie_ticket_booking;
USE movie_ticket_booking;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Bảng users
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    birth_date DATE NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Bảng genres
DROP TABLE IF EXISTS genres;
CREATE TABLE genres (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Bảng movies
DROP TABLE IF EXISTS movies;
CREATE TABLE movies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    age_restriction INT DEFAULT 0,
    country VARCHAR(50) NOT NULL,
    duration INT NOT NULL,
    screening_date DATE NOT NULL,
    poster VARCHAR(255) NULL,
    trailer_url VARCHAR(255) NULL,
    status ENUM('coming', 'now_showing', 'ended') DEFAULT 'coming',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Bảng movie_genre
DROP TABLE IF EXISTS movie_genre;
CREATE TABLE movie_genre (
    movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
);

-- 5. Bảng movie_images (Thêm từ DB bạn bè: Nhiều ảnh cho 1 phim)
DROP TABLE IF EXISTS movie_images;
CREATE TABLE movie_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- 6. Bảng reviews (Thêm từ DB bạn bè: Đánh giá phim)
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK(rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- 7. Bảng rooms
DROP TABLE IF EXISTS rooms;
CREATE TABLE rooms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    total_seats INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 8. Bảng seat_types (Thêm từ DB bạn bè: Loại ghế + Giá)
DROP TABLE IF EXISTS seat_types;
CREATE TABLE seat_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL UNIQUE, -- VD: REGULAR, VIP, COUPLE
    price DECIMAL(10,2) NOT NULL DEFAULT 0, -- Giá phụ thu của loại ghế
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 9. Bảng seats (Đã sửa lại để liên kết với seat_types)
DROP TABLE IF EXISTS seats;
CREATE TABLE seats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    room_id INT NOT NULL,
    seat_row CHAR(1) NOT NULL CHECK (seat_row BETWEEN 'A' AND 'H'),
    seat_number INT NOT NULL CHECK (seat_number BETWEEN 1 AND 12),
    seat_type_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE (room_id, seat_row, seat_number),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_type_id) REFERENCES seat_types(id) ON DELETE CASCADE
);

-- 10. Bảng showtimes
DROP TABLE IF EXISTS showtimes;
CREATE TABLE showtimes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    movie_id INT NOT NULL,
    room_id INT NOT NULL,
    show_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    base_price DECIMAL(10,2) NOT NULL DEFAULT 80000,
    status ENUM('active', 'canceled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (room_id, show_date, start_time),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    INDEX idx_showdate (show_date),
    INDEX idx_movie (movie_id)
);

-- 11. Bảng bookings
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    booking_code VARCHAR(20) NOT NULL UNIQUE,
    total_price DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'momo', 'vnpay', 'bank_transfer') DEFAULT 'cash',
    status ENUM('pending', 'paid', 'canceled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_booking_code (booking_code),
    INDEX idx_status (status)
);

-- 12. Bảng tickets (Kết hợp booking_seats và lấy ưu điểm của tickets: có showtime_id riêng)
DROP TABLE IF EXISTS tickets;
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    showtime_id INT NOT NULL,
    seat_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (showtime_id, seat_id), -- Một ghế chỉ bán 1 lần trong 1 suất chiếu
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================
-- DỮ LIỆU MẪU (SEEDER)
-- ==============================================

-- Thêm Users
INSERT INTO users (first_name, last_name, email, password, phone, role) VALUES 
('Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'admin'),
('John', 'Doe', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321', 'user');

-- Thêm Thể loại
INSERT INTO genres (name, description) VALUES 
('Hành động', 'Phim có nhiều cảnh đánh nhau, rượt đuổi.'),
('Khoa học viễn tưởng', 'Phim về tương lai, công nghệ cao, không gian.'),
('Hài hước', 'Phim mang tính chất giải trí, gây cười.');

-- Thêm Phim
INSERT INTO movies (title, description, age_restriction, country, duration, screening_date, status) VALUES 
('Avengers: Endgame', 'Trận chiến cuối cùng của các siêu anh hùng.', 13, 'Mỹ', 181, '2023-05-01', 'now_showing'),
('Doraemon: Nobita', 'Cuộc phiêu lưu của Nobita và Doraemon.', 0, 'Nhật Bản', 105, '2023-06-01', 'coming');

-- Thêm Chi tiết Thể loại Phim
INSERT INTO movie_genre (movie_id, genre_id) VALUES 
(1, 1), (1, 2), -- Avengers: Hành động, Viễn tưởng
(2, 3);         -- Doraemon: Hài hước

-- Thêm Ảnh Phim
INSERT INTO movie_images (movie_id, image_url) VALUES 
(1, 'avengers_endgame_1.jpg'),
(1, 'avengers_endgame_2.jpg'),
(2, 'doraemon_1.jpg');

-- Thêm Đánh giá Phim
INSERT INTO reviews (user_id, movie_id, rating, comment) VALUES 
(2, 1, 5, 'Phim quá hay, kỹ xảo đỉnh cao!'),
(2, 2, 4, 'Phim hài hước, giải trí tốt.');

-- Thêm Phòng chiếu
INSERT INTO rooms (name, total_seats) VALUES 
('Phòng 1', 20),
('Phòng 2', 20);

-- Thêm Loại ghế
INSERT INTO seat_types (name, price) VALUES 
('REGULAR', 0),    -- Không phụ thu
('VIP', 20000),    -- Phụ thu 20k
('COUPLE', 50000); -- Phụ thu 50k

-- Thêm Ghế (Cho Phòng 1: 2 hàng A, B, mỗi hàng 5 ghế)
INSERT INTO seats (room_id, seat_row, seat_number, seat_type_id) VALUES 
(1, 'A', 1, 1), (1, 'A', 2, 1), (1, 'A', 3, 1), (1, 'A', 4, 1), (1, 'A', 5, 1), -- A là REGULAR (id=1)
(1, 'B', 1, 2), (1, 'B', 2, 2), (1, 'B', 3, 2), (1, 'B', 4, 2), (1, 'B', 5, 2); -- B là VIP (id=2)

-- Thêm Suất chiếu
INSERT INTO showtimes (movie_id, room_id, show_date, start_time, end_time, base_price) VALUES 
(1, 1, CURRENT_DATE, '19:00:00', '22:00:00', 90000),
(2, 2, CURRENT_DATE, '18:00:00', '20:00:00', 80000);

-- Thêm Đặt vé
INSERT INTO bookings (user_id, booking_code, total_price, payment_method, status) VALUES 
(2, 'BK20230501001', 200000, 'momo', 'paid');

-- Thêm Vé (Khách đặt ghế A1 - Thường, B1 - VIP cho suất chiếu 1)
-- Giá vé = Giá cơ bản suất chiếu (90k) + Giá loại ghế
INSERT INTO tickets (booking_id, showtime_id, seat_id, price) VALUES 
(1, 1, 1, 90000),   -- A1 REGULAR: 90k + 0 = 90k
(1, 1, 6, 110000);  -- B1 VIP: 90k + 20k = 110k

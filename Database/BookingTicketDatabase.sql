-- ==============================================
-- DATABASE: movie_ticket_booking (Combined & Optimized)
-- ==============================================

DROP
DATABASE IF EXISTS movie_ticket_booking;
CREATE
DATABASE IF NOT EXISTS movie_ticket_booking;
USE
movie_ticket_booking;

SET
FOREIGN_KEY_CHECKS = 0;

-- 1. Bảng users
DROP TABLE IF EXISTS users;
CREATE TABLE users
(
    id             INT PRIMARY KEY AUTO_INCREMENT,
    first_name     VARCHAR(50)  NOT NULL,
    last_name      VARCHAR(50)  NOT NULL,
    email          VARCHAR(100) NOT NULL UNIQUE,
    password       VARCHAR(255) NOT NULL,
    phone          VARCHAR(20)  NOT NULL UNIQUE,
    birth_date     DATE NULL,
    role           ENUM ('admin', 'user') DEFAULT 'user',
    remember_token VARCHAR(100) NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Bảng genres
DROP TABLE IF EXISTS genres;
CREATE TABLE genres
(
    id          INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Bảng movies
DROP TABLE IF EXISTS movies;
CREATE TABLE movies
(
    id              INT PRIMARY KEY AUTO_INCREMENT,
    title           VARCHAR(200) NOT NULL,
    description     TEXT NULL,
    age_restriction INT       DEFAULT 0,
    country         VARCHAR(50)  NOT NULL,
    duration        INT          NOT NULL,
    screening_date  DATE         NOT NULL,
    poster          VARCHAR(255) NULL,
    trailer_url     VARCHAR(255) NULL,
    status          ENUM ('coming', 'now_showing', 'ended') DEFAULT 'coming',
    is_active       BOOLEAN   DEFAULT TRUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Bảng movie_genre
DROP TABLE IF EXISTS movie_genre;
CREATE TABLE movie_genre
(
    movie_id INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    FOREIGN KEY (movie_id) REFERENCES movies (id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres (id) ON DELETE CASCADE
);

-- 5. Bảng movie_images (Thêm từ DB bạn bè: Nhiều ảnh cho 1 phim)
DROP TABLE IF EXISTS movie_images;
CREATE TABLE movie_images
(
    id         INT PRIMARY KEY AUTO_INCREMENT,
    movie_id   INT          NOT NULL,
    image_url  VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies (id) ON DELETE CASCADE
);

-- 6. Bảng reviews (Thêm từ DB bạn bè: Đánh giá phim)
DROP TABLE IF EXISTS reviews;
CREATE TABLE reviews
(
    id         INT PRIMARY KEY AUTO_INCREMENT,
    user_id    INT     NOT NULL,
    movie_id   INT     NOT NULL,
    rating     TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment    TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies (id) ON DELETE CASCADE
);

-- 7. Bảng rooms
DROP TABLE IF EXISTS rooms;
CREATE TABLE rooms
(
    id          INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL UNIQUE,
    total_seats INT         NOT NULL,
    is_active   BOOLEAN   DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 8. Bảng seat_types (Thêm từ DB bạn bè: Loại ghế + Giá)
DROP TABLE IF EXISTS seat_types;
CREATE TABLE seat_types
(
    id         INT PRIMARY KEY AUTO_INCREMENT,
    name       VARCHAR(30)    NOT NULL UNIQUE,    -- VD: REGULAR, VIP, COUPLE
    price      DECIMAL(10, 2) NOT NULL DEFAULT 0, -- Giá phụ thu của loại ghế
    created_at TIMESTAMP               DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP               DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 9. Bảng seats (Đã sửa lại để liên kết với seat_types)
DROP TABLE IF EXISTS seats;
CREATE TABLE seats
(
    id           INT PRIMARY KEY AUTO_INCREMENT,
    room_id      INT     NOT NULL,
    seat_row     CHAR(1) NOT NULL CHECK (seat_row BETWEEN 'A' AND 'H'),
    seat_number  INT     NOT NULL CHECK (seat_number BETWEEN 1 AND 12),
    seat_type_id INT     NOT NULL,
    is_active    BOOLEAN DEFAULT TRUE,
    UNIQUE (room_id, seat_row, seat_number),
    FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE,
    FOREIGN KEY (seat_type_id) REFERENCES seat_types (id) ON DELETE CASCADE
);

-- 10. Bảng showtimes
DROP TABLE IF EXISTS showtimes;
CREATE TABLE showtimes
(
    id         INT PRIMARY KEY AUTO_INCREMENT,
    movie_id   INT            NOT NULL,
    room_id    INT            NOT NULL,
    show_date  DATE           NOT NULL,
    start_time TIME           NOT NULL,
    end_time   TIME           NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL DEFAULT 80000,
    status     ENUM ('active', 'canceled') DEFAULT 'active',
    created_at TIMESTAMP               DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP               DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (room_id, show_date, start_time),
    FOREIGN KEY (movie_id) REFERENCES movies (id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE,
    INDEX      idx_showdate (show_date),
    INDEX      idx_movie (movie_id)
);

-- 11. Bảng bookings
DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings
(
    id             INT PRIMARY KEY AUTO_INCREMENT,
    user_id        INT            NOT NULL,
    booking_code   VARCHAR(20)    NOT NULL DEFAULT 'TEMP',
    total_price    DECIMAL(10, 2) NOT NULL,
    payment_method ENUM ('cash', 'momo', 'vnpay', 'bank_transfer') DEFAULT 'cash',
    status         ENUM ('pending', 'paid', 'canceled')            DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX          idx_booking_code (booking_code),
    INDEX          idx_status (status)
);
-- 12. Bảng tickets (Kết hợp booking_seats và lấy ưu điểm của tickets: có showtime_id riêng)
DROP TABLE IF EXISTS tickets;
CREATE TABLE tickets
(
    id          INT PRIMARY KEY AUTO_INCREMENT,
    booking_id  INT            NOT NULL,
    showtime_id INT            NOT NULL,
    seat_id     INT            NOT NULL,
    price       DECIMAL(10, 2) NOT NULL,
    status      ENUM ('booked', 'used', 'canceled')            DEFAULT 'booked',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (showtime_id, seat_id), -- Một ghế chỉ bán 1 lần trong 1 suất chiếu
    FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes (id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats (id) ON DELETE CASCADE
);

SET
FOREIGN_KEY_CHECKS = 1;

DROP TRIGGER IF EXISTS trg_booking_code;
DELIMITER $$
CREATE TRIGGER trg_booking_code
    BEFORE INSERT
    ON bookings
    FOR EACH ROW
BEGIN
    IF NEW.booking_code IS NULL OR NEW.booking_code = '' THEN
        SET NEW.booking_code =
            CONCAT(
                'BK-',
                UPPER(LEFT(REPLACE(UUID(), '-', ''), 8))
            );
END IF;
END$$
DELIMITER ;

INSERT INTO users (first_name, last_name, email, password, phone, role)
VALUES ('Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0123456789', 'admin'),
       ('John', 'Doe', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0987654321',
        'user'),
       ('Nguyễn', 'Văn An', 'nguyenvanan@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0912345678', 'user'),
       ('Trần', 'Thị Bình', 'tranthibinh@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0923456789', 'user'),
       ('Lê', 'Hoàng Cường', 'lehoangcuong@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        '0934567890', 'user');

INSERT INTO genres (name, description)
VALUES ('Hành động', 'Phim có nhiều cảnh đánh nhau, rượt đuổi.'),
       ('Khoa học viễn tưởng', 'Phim về tương lai, công nghệ cao, không gian.'),
       ('Hài hước', 'Phim mang tính chất giải trí, gây cười.'),
       ('Kinh dị', 'Phim có nhiều tình tiết gây sợ hãi.'),
       ('Tình cảm', 'Phim nhẹ nhàng có yếu tố gây xúc động.'),
       ('Tam lý', 'Phim có nhiều yếu tố suy luận và tình tiết suy ngẫm.'),
       ('Hoạt hình', 'Phim dành cho thiếu nhi và gia đình.');

INSERT INTO movies (title, description, age_restriction, country, duration, screening_date, status)
VALUES ('Avengers: Endgame',
        'Sau sự kiện hủy diệt tàn khốc, vũ trụ chìm trong cảnh hoang tàn. Với sự trợ giúp của những đồng minh còn sống sót, biệt đội siêu anh hùng Avengers tập hợp một lần nữa để đảo ngược hành động của Thanos và khôi phục lại trật tự của vũ trụ.',
        13, 'Mỹ', 181, '2023-05-01', 'now_showing'),
       ('Doraemon: Nobita và bản giao hưởng địa cầu',
        'TÁC PHẨM KỶ NIỆM 90 NĂM FUJIKO F FUJIO Chuẩn bị cho buổi hòa nhạc ở trường, Nobita đang tập thổi sáo - nhạc cụ mà cậu dở tệ. Thích thú trước nốt "No" lạc quẻ của Nobita, Micca - cô bé bí ẩn đã mời Doraemon, Nobita cùng nhóm bạn đến "Farre" - Cung điện âm nhạc tọa lạc trên một hành tinh nơi âm nhạc sẽ hóa thành năng lượng. Nhằm cứu cung điện này, Micca đang tìm kiếm "virtuoso" - bậc thầy âm nhạc sẽ cùng mình biểu diễn! Với bảo bối thần kì "chứng chỉ chuyên viên âm nhạc", Doraemon và các bạn đã chọn nhạc cụ, cùng Micca hòa tấu, từng bước khôi phục cung điện. Tuy nhiên, một vật thể sống đáng sợ sẽ xóa số âm nhạc khỏi thế giới đang đến gần, Trái Đất đang rơi vào nguy hiểm... ! Liệu những người bạn nhỏ có thể cứu được "tương lai âm nhạc" và cả địa cầu này?',
        0, 'Nhật Bản', 105, '2023-06-01', 'coming'),
       ('Minions & Quái Vật',
        'Minions & Quái Vật là câu chuyện vừa náo loạn vừa ngớ ngẩn nhưng “hoàn toàn có thật” về cách Minions chinh phục Hollywood, trở thành ngôi sao điện ảnh, rồi mất tất cả, vô tình thả quái vật ra khắp thế giới và sau đó phải cùng nhau hợp sức để cứu lấy hành tinh khỏi chính mớ hỗn loạn mà mình tạo ra.',
        0, 'Hoa Kỳ', 90, '2026-07-01', 'coming'),
       ('Câu Chuyện Đồ Chơi 5',
        'Các món đồ chơi đã trở lại trong Toy Story 5 của Disney và Pixar, và lần này sẽ là cuộc đối đầu giữa đồ chơi và công nghệ. Buzz, Woody, Jessie cùng cả nhóm sẽ phải đối mặt với thử thách khó khăn hơn gấp bội khi chạm trán một mối đe dọa hoàn toàn mới đối với niềm vui vui chơi.',
        0, 'Hoa Kỳ', 102, '2026-06-19', 'now_showing'),
       ('Tên Cậu Là Gì?',
        'Bộ phim kể về Mitsuha – nữ sinh trung học sống ở một thị trấn nhỏ của vùng Itomori. Luôn chán chường với cuộc sống tẻ nhạt ở vùng thôn quê, Mitsuha ao ước kiếp sau được làm một anh chàng đẹp trai sống ở thủ đô Tokyo sôi động. Trong khi đó ở Tokyo, anh chàng Taki rất hài lòng với cuộc sống và công việc làm thêm ở một nhà hàng Italy sau giờ học. Tuy vậy, hằng đêm cậu vẫn mơ thấy mình trong cơ thể một cô gái thôn quê. Đến một hôm khi sự kiện nghìn năm có một là Sao Chổi tiến gần tới Trái đất, Taki và Mitsuha bỗng bị hoán đổi cơ thể. Cứ cách một ngày, Taki lại trở thành Mitsuha khám phá cuộc sống vùng quê và ngược lại, Mitsuha làm anh chàng nam sinh Tokyo háo hức với cuộc sống nơi đô thị ồn ào. Cứ thế, câu chuyện của Mitsuha và Taki diễn ra dẫn dắt khán giả đến những tình huống đặc biệt, dù cả hai chưa bao giờ gặp mặt hay thậm chí là biết tên của nhau.',
        13, 'Nhật Bản', 110, '2026-06-05', 'now_showing'),
       ('Bầy Xác Sống',
        'Nội dung xoay quanh một hội nghị công nghệ sinh học trong tòa nhà lớn thì bất ngờ xảy ra sự cố rò rỉ virus đột biến. Chính quyền lập tức phong tỏa toàn bộ khu vực, khiến những người còn sống bị mắc kẹt bên trong cùng các sinh vật nhiễm bệnh đang tiến hóa liên tục.',
        18, 'Hàn Quốc', 122, '2026-06-12', 'now_showing'),
       ('Tạm Biệt Gohan',
        'Suốt mười năm đằng đẵng, chú chó hoang lông trắng với chiếc mũi đỏ mang tên GOHAN cứ thế phiêu dạt giữa cuộc đời, ôm trọn những ký ức chẳng thể phai nhòa. Đó là sự ấm áp bình lặng bên người chủ đầu tiên – một kỹ sư ô tô người Nhật sắp sửa nghỉ hưu. Là những ngày tháng rộn ràng bên người chủ thứ hai – cô giúp việc trẻ người Miến Điện làm việc tại trạm cứu hộ thú cưng. Và cuối cùng, là những bài học thầm lặng chú dạy cho người chủ hiện tại – một sinh viên mỹ thuật, người lần đầu tiên trong đời học cách định nghĩa thế nào là tình yêu. Một câu chuyện về thời gian, về những cuộc hội ngộ và chia ly, và về một chú chó ghi nhớ tất cả.',
        8, 'Hàn Quốc', 140, '2026-06-25', 'now_showing'),
       ('Lớp Học Ám Sát: Giờ Của Chúng Ta',
        'Phim điện ảnh phiên bản hoàn toàn mới của “Lớp Học Ám Sát” nhân dịp kỷ niệm 10 năm ra mắt! Một sinh vật mang vận tốc Mach 20 đe dọa hủy diệt Trái Đất nhưng lại trở thành một thầy giáo? Một lớp học bị coi là "phế thải" bỗng chốc trở thành hy vọng cuối cùng của nhân loại? Những câu chuyện mới toanh chưa từng được kể trên màn ảnh sẽ mang đến cho fan hâm mộ những thước phim bùng nổ cùng ký ức rực rỡ nhất về thầy Koro và tập thể lớp 3-E',
        13, 'Nhật Bản', 86, '2026-06-05', 'ended'),
       ('Lầu Chú Hoả',
        'Để câu view, một nhóm streamer livestream khám phá Lầu Chú Hỏa, dinh thự bỏ hoang gắn với truyền thuyết về con ma nhà họ Hứa. Nhưng ngay từ những phút đầu, mọi thứ đã vượt khỏi tầm kiểm soát. Hiện tượng siêu nhiên liên tiếp xảy ra, kéo cả nhóm vào vòng xoáy ám ảnh không lối thoát. Buổi livestream nhanh chóng biến thành nơi “tạo nghiệp – trả nghiệp”, khi từng người phải trả giá cho lòng tham và sự báng bổ trước linh hồn oan khuất của cô tiểu thư họ Hứa.',
        18, 'Việt Nam', 94, '2026-06-12', 'ended'),
       ('Supergirl',
        'Supergirl – bom tấn mới nhất từ DC Studios – sẽ chính thức đổ bộ các rạp chiếu toàn cầu vào mùa hè này, với Milly Alcock đảm nhận vai kép Supergirl/Kara Zor-El. Khi một kẻ thù bất ngờ và tàn nhẫn giáng đòn ngay tại nơi cô gọi là nhà, Kara Zor-El – hay còn được biết đến với cái tên Supergirl – buộc phải bắt tay với một đồng minh không ai ngờ tới, bắt đầu chuyến hành trình xuyên dải ngân hà đầy sử thi, nơi vừa là cuộc trả thù, vừa là hành trình đi tìm công lý.',
        16, 'Hoa Kỳ', 108, '2026-06-26', 'now_showing');


INSERT INTO movie_genre (movie_id, genre_id)
VALUES (1, 1),
       (1, 2),
       (2, 3),
       (2, 7),
       (3, 2),
       (3, 3),
       (3, 7),
       (4, 2),
       (4, 3),
       (4, 7),
       (5, 2),
       (5, 5),
       (5, 7),
       (6, 1),
       (6, 2),
       (6, 4),
       (7, 5),
       (7, 6),
       (8, 1),
       (8, 2),
       (8, 7),
       (9, 2),
       (9, 4),
       (10, 2),
       (10, 1);

INSERT INTO movie_images (movie_id, image_url)
VALUES (1, 'assets/movieImage/Avengers_Endgame.jpg'),
       (2, 'assets/movieImage/DoraemonBangGiaoHuongDiaCau.jpg'),
       (3, 'assets/movieImage/MinionsVaQuaiVat.jpg'),
       (4, 'assets/movieImage/CauChuyenDoChoi5.jpg'),
       (5, 'assets/movieImage/TenCauLaGi.png'),
       (6, 'assets/movieImage/BayXacSong.jpg'),
       (7, 'assets/movieImage/TamBietGohan.jpg'),
       (8, 'assets/movieImage/LopHocAmSat.jpg'),
       (9, 'assets/movieImage/LauChuHoa.jpg'),
       (10, 'assets/movieImage/SuperGirl.jpg');


INSERT INTO reviews (user_id, movie_id, rating, comment)
VALUES (2, 1, 5, 'Một cái kết hoàn hảo cho Infinity Saga, cảm xúc từ đầu đến cuối.'),
       (2, 1, 5, 'Kỹ xảo đỉnh cao, xứng đáng xem lại nhiều lần.'),

       (2, 2, 5, 'Nội dung ý nghĩa, âm nhạc rất hay và phù hợp với mọi lứa tuổi.'),
       (2, 2, 4, 'Phim nhẹ nhàng, rất thích hợp để xem cùng gia đình.'),

       (2, 3, 4, 'Hài hước, nhiều phân cảnh khiến cả rạp cười nghiêng ngả.'),
       (2, 3, 5, 'Minions vẫn đáng yêu như mọi khi, rất đáng xem.'),

       (2, 4, 5, 'Tuổi thơ quay trở lại, Pixar chưa bao giờ làm mình thất vọng.'),
       (2, 4, 4, 'Nội dung cảm động, hình ảnh đẹp.'),

       (2, 5, 5, 'Một trong những bộ anime hay nhất mình từng xem.'),
       (2, 5, 5, 'Âm nhạc và hình ảnh quá xuất sắc, cốt truyện cuốn hút.'),

       (2, 6, 4, 'Nhiều tình tiết bất ngờ, không khí hồi hộp xuyên suốt.'),
       (2, 6, 3, 'Có vài đoạn hơi dài nhưng nhìn chung vẫn rất ổn.'),

       (2, 7, 5, 'Một bộ phim cực kỳ cảm động dành cho những người yêu động vật.'),
       (2, 7, 4, 'Cốt truyện nhẹ nhàng nhưng để lại nhiều suy ngẫm.'),

       (2, 8, 5, 'Fan Assassination Classroom chắc chắn sẽ rất thích bộ phim này.'),
       (2, 8, 4, 'Nhiều cảnh hành động đẹp mắt và đầy cảm xúc.'),

       (2, 9, 4, 'Không khí kinh dị khá tốt, jumpscare hợp lý.'),
       (2, 9, 3, 'Nội dung ổn nhưng phần kết chưa thật sự thuyết phục.'),

       (2, 10, 5, 'Mong chờ nhất trong năm, hiệu ứng hình ảnh rất đẹp.'),
       (2, 10, 4, 'Diễn xuất tốt, các pha hành động mãn nhãn.');


INSERT INTO rooms (name, total_seats)
VALUES ('Phòng 1', 40),
       ('Phòng 2', 40);


INSERT INTO seat_types (name, price)
VALUES ('REGULAR', 0), -- Không phụ thu
       ('VIP', 20000), -- Phụ thu 20k
       ('COUPLE', 50000);


INSERT INTO seats (room_id, seat_row, seat_number, seat_type_id)
VALUES (1, 'A', 1, 1),
       (1, 'A', 2, 1),
       (1, 'A', 3, 1),
       (1, 'A', 4, 1),
       (1, 'A', 5, 1),
       (1, 'B', 1, 1),
       (1, 'B', 2, 1),
       (1, 'B', 3, 1),
       (1, 'B', 4, 1),
       (1, 'B', 5, 1),
       (1, 'C', 1, 1),
       (1, 'C', 2, 1),
       (1, 'C', 3, 1),
       (1, 'C', 4, 1),
       (1, 'C', 5, 1),
       (1, 'D', 1, 2),
       (1, 'D', 2, 2),
       (1, 'D', 3, 2),
       (1, 'D', 4, 2),
       (1, 'D', 5, 2),
       (1, 'E', 1, 2),
       (1, 'E', 2, 2),
       (1, 'E', 3, 2),
       (1, 'E', 4, 2),
       (1, 'E', 5, 2),
       (1, 'F', 1, 2),
       (1, 'F', 2, 2),
       (1, 'F', 3, 2),
       (1, 'F', 4, 2),
       (1, 'F', 5, 2),
       (1, 'G', 1, 2),
       (1, 'G', 2, 2),
       (1, 'G', 3, 2),
       (1, 'G', 4, 2),
       (1, 'G', 5, 2),
       (1, 'H', 1, 3),
       (1, 'H', 2, 3),
       (1, 'H', 3, 3),
       (1, 'H', 4, 3),
       (1, 'H', 5, 3),

       (2, 'A', 1, 1),
       (2, 'A', 2, 1),
       (2, 'A', 3, 1),
       (2, 'A', 4, 1),
       (2, 'A', 5, 1),
       (2, 'B', 1, 1),
       (2, 'B', 2, 1),
       (2, 'B', 3, 1),
       (2, 'B', 4, 1),
       (2, 'B', 5, 1),
       (2, 'C', 1, 1),
       (2, 'C', 2, 1),
       (2, 'C', 3, 1),
       (2, 'C', 4, 1),
       (2, 'C', 5, 1),
       (2, 'D', 1, 2),
       (2, 'D', 2, 2),
       (2, 'D', 3, 2),
       (2, 'D', 4, 2),
       (2, 'D', 5, 2),
       (2, 'E', 1, 2),
       (2, 'E', 2, 2),
       (2, 'E', 3, 2),
       (2, 'E', 4, 2),
       (2, 'E', 5, 2),
       (2, 'F', 1, 2),
       (2, 'F', 2, 2),
       (2, 'F', 3, 2),
       (2, 'F', 4, 2),
       (2, 'F', 5, 2),
       (2, 'G', 1, 2),
       (2, 'G', 2, 2),
       (2, 'G', 3, 2),
       (2, 'G', 4, 2),
       (2, 'G', 5, 2),
       (2, 'H', 1, 3),
       (2, 'H', 2, 3),
       (2, 'H', 3, 3),
       (2, 'H', 4, 3),
       (2, 'H', 5, 3);


INSERT INTO showtimes
(movie_id, room_id, show_date, start_time, end_time, base_price)
VALUES (1, 1, '2026-07-01', '09:00:00', '12:01:00', 90000),
       (1, 2, '2026-07-01', '19:00:00', '22:01:00', 90000),
       (2, 1, '2026-07-01', '13:00:00', '14:45:00', 80000),
       (3, 2, '2026-07-02', '09:30:00', '11:00:00', 85000),
       (4, 1, '2026-07-02', '15:00:00', '16:42:00', 90000),
       (5, 2, '2026-07-02', '19:00:00', '20:50:00', 90000),
       (6, 1, '2026-07-03', '20:30:00', '22:32:00', 100000),
       (7, 2, '2026-07-03', '10:00:00', '12:20:00', 85000),
       (8, 1, '2026-07-04', '08:30:00', '09:56:00', 80000),
       (10, 2, '2026-07-04', '19:30:00', '21:18:00', 100000);


INSERT INTO bookings (user_id, total_price, payment_method, status)
VALUES (3, 200000, 'momo', 'paid'),
       (3, 290000, 'vnpay', 'paid'),
       (3, 170000, 'cash', 'pending'),
       (4, 310000, 'bank_transfer', 'paid'),
       (4, 180000, 'momo', 'paid'),
       (4, 420000, 'vnpay', 'paid'),
       (5, 255000, 'cash', 'paid'),
       (5, 200000, 'momo', 'canceled'),
       (5, 340000, 'bank_transfer', 'paid');



INSERT INTO tickets (booking_id, showtime_id, seat_id, price)
VALUES (2, 1, 2, 90000),
       (2, 1, 7, 110000),
       (3, 2, 3, 90000),
       (3, 2, 8, 110000),
       (3, 2, 9, 110000),
       (4, 3, 11, 80000),
       (4, 3, 12, 80000),
       (5, 1, 4, 90000),
       (5, 1, 5, 90000),
       (5, 1, 13, 90000),
       (6, 4, 16, 105000),
       (6, 4, 17, 105000),
       (7, 10, 51, 100000),
       (7, 10, 52, 100000),
       (7, 10, 56, 120000),
       (7, 10, 57, 120000),
       (8, 7, 41, 90000),
       (8, 7, 42, 90000),
       (8, 7, 46, 110000),
       (9, 5, 43, 90000),
       (9, 5, 47, 110000);
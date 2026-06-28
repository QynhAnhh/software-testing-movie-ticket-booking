<?php
/**
 * KIẾN THỨC PHP: Gộp giao diện và Truy vấn CSDL
 * 
 * Ở đây ta gọi `header.php`. Bởi vì trong `header.php` đã có `require_once 'config.php'`, 
 * nên ở trang index này ta có thể dùng biến $conn (kết nối CSDL) mà không cần gọi lại config.php
 */
require_once 'header.php';

/**
 * KIẾN THỨC PHP & SQL: Truy vấn dữ liệu từ MySQL
 * 
 * 1. Viết câu lệnh SQL: SELECT để lấy dữ liệu. 
 *    JOIN bảng movies với movie_images để lấy poster.
 * 
 * 2. mysqli_query(): Gửi câu lệnh SQL tới database để thực thi.
 *    Kết quả trả về được lưu vào biến $result_movies.
 */
$query_movies = "
    SELECT * FROM movies 
    WHERE status = 'now_showing' AND is_active = 1
";
$result_movies = mysqli_query($conn, $query_movies);
?>

<!-- BANNER -->
<div class="banner-section">
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            <!-- Đã cập nhật đường dẫn ảnh banner -->
            <div class="swiper-slide">
                <img src="images/tonghop-banner.jpg" alt="Banner 1">
            </div>
            <div class="swiper-slide">
                <img src="images/minions&quaivat-banner.jpg" alt="Banner 2">
            </div>
            <div class="swiper-slide">
                <img src="images/supergirl-banner.png" alt="Banner 3">
            </div>
            <div class="swiper-slide">
                <img src="images/muave-banner.png" alt="Banner 4">
            </div>
        </div>
        
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</div>

<!-- DANH SÁCH PHIM ĐANG CHIẾU -->
<div class="container main-content" style="min-height: 400px; padding: 40px 20px;">
    <h2>Phim Đang Chiếu</h2>
    
    <div class="movie-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <?php 
        /**
         * KIẾN THỨC PHP: Xử lý kết quả trả về từ database
         * 
         * mysqli_num_rows(): Đếm số lượng dòng dữ liệu trả về. Nếu > 0 nghĩa là có phim.
         */
        if ($result_movies && mysqli_num_rows($result_movies) > 0): 
            
            /**
             * mysqli_fetch_assoc(): Lấy từng dòng dữ liệu và chuyển thành một mảng kết hợp (Associative Array).
             * Vòng lặp while sẽ chạy cho đến khi lấy hết tất cả các dòng phim.
             */
            while ($movie = mysqli_fetch_assoc($result_movies)): 
        ?>
            <!-- Hiển thị từng bộ phim -->
            <div class="movie-card" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; padding-bottom: 15px; text-align: center; background: #fff;">
                
                <!-- htmlspecialchars() giúp bảo vệ chống lỗi hiển thị ký tự đặc biệt (XSS) -->
                <!-- Sử dụng toán tử Elvis (?:) nếu không có ảnh thì lấy ảnh default -->
                <img src="<?php echo htmlspecialchars($movie['images'] ?: 'images/movies/default.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                     style="width: 100%; height: 350px; object-fit: cover;">
                
                <h3 style="font-size: 18px; margin: 15px 10px; height: 45px; overflow: hidden;"><?php echo htmlspecialchars($movie['title']); ?></h3>
                
                <a href="movie_details.php?id=<?php echo $movie['id']; ?>" 
                   style="display: inline-block; padding: 10px 20px; background: #e50914; color: #fff; text-decoration: none; border-radius: 5px;">
                   Mua Vé
                </a>
            </div>
        <?php 
            endwhile; 
        else: 
        ?>
            <p>Hiện không có phim nào đang chiếu.</p>
        <?php endif; ?>
    </div>
</div>

<!-- SCRIPTS SWIPER -->
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
<script>
    var swiper = new Swiper(".mySwiper", {
        spaceBetween: 0,
        centeredSlides: true,
        loop: true,
        autoplay: {
            delay: 3500,
            disableOnInteraction: false,
        },
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
        },
    });
</script>

<?php
/**
 * Chèn footer ở cuối trang
 */
require_once 'footer.php';
?>

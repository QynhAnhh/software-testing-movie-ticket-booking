<?php
/**
 * KIẾN THỨC PHP: Gộp giao diện và Truy vấn CSDL
 * 
 * Ở đây ta gọi `header.php`. Bởi vì trong `header.php` đã có `require_once 'config.php'`, 
 * nên ở trang index này ta có thể dùng biến $conn (kết nối CSDL) mà không cần gọi lại config.php
 */
require_once 'header.php';

use App\Controllers\MovieController;

$movieController = new MovieController();
$now_showing = $movieController->getNowShowingMovies();
$upcoming = $movieController->getUpcomingMovies();
?>

<div style="padding: 20px;">
    <h1>TRANG CHỦ - MÔ PHỎNG FRONTEND (TEST FLOW)</h1>
    
    <hr>

    <h2>1. Phim Đang Chiếu (Now Showing)</h2>
    <ul>
        <?php if (!empty($now_showing)): ?>
            <?php foreach ($now_showing as $movie): ?>
                <li style="margin-bottom: 10px;">
                    <strong><?php echo htmlspecialchars($movie['title']); ?></strong> 
                    - Thời lượng: <?php echo $movie['duration']; ?> phút
                    <br>
                    <a href="movie_details.php?id=<?php echo $movie['id']; ?>" style="color: blue; text-decoration: underline;">
                        Xem chi tiết & Đặt vé
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>Chưa có phim đang chiếu.</li>
        <?php endif; ?>
    </ul>

    <hr>

    <h2>2. Phim Sắp Chiếu (Upcoming)</h2>
    <ul>
        <?php if (!empty($upcoming)): ?>
            <?php foreach ($upcoming as $movie): ?>
                <li style="margin-bottom: 10px;">
                    <strong><?php echo htmlspecialchars($movie['title']); ?></strong> 
                    - Khởi chiếu: <?php echo $movie['screening_date']; ?>
                    <br>
                    <a href="movie_details.php?id=<?php echo $movie['id']; ?>" style="color: blue; text-decoration: underline;">
                        Xem chi tiết
                    </a>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>Chưa có phim sắp chiếu.</li>
        <?php endif; ?>
    </ul>
</div>

<?php
require_once 'footer.php';
?>

<?php
require_once __DIR__ . '/../layouts/header.php'; // Điều chỉnh đường dẫn header cho đúng với thư mục của em

// 1. KẾT NỐI DATABASE BẰNG PDO
$host = '127.0.0.1';
$port = '3308';
$dbname = 'movie_ticket_booking';
$username = 'root';
$password = '123456'; 

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// 2. HÀM TRUY VẤN LẤY PHIM THEO TRẠNG THÁI
function getMoviesByStatus($pdo, $status) {
    $sql = "
        SELECT m.id, m.title, m.age_restriction, m.duration, m.country, 
               mi.image_url as poster,
               GROUP_CONCAT(g.name SEPARATOR ', ') as genres
        FROM movies m
        LEFT JOIN movie_images mi ON m.id = mi.movie_id
        LEFT JOIN movie_genre mg ON m.id = mg.movie_id
        LEFT JOIN genres g ON mg.genre_id = g.id
        WHERE m.status = :status AND m.is_active = 1
        GROUP BY m.id
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$nowShowingMovies = getMoviesByStatus($pdo, 'now_showing');
$comingSoonMovies = getMoviesByStatus($pdo, 'coming');

// Hàm format nhãn độ tuổi và text mô tả
function getAgeDescription($age) {
    if ($age == 0) {
        return ['label' => 'P', 'text' => 'P: Phim dành cho mọi lứa tuổi', 'color' => '#22c55e'];
    }
    return [
        'label' => 'T' . $age, 
        'text' => "T$age: Phim dành cho khán giả từ đủ $age tuổi trở lên ($age+)", 
        'color' => ($age >= 18 ? '#ef4444' : '#eab308')
    ];
}
?>

<div class="movie-page-container">
    <div class="movie-tabs">
        <button class="tab-btn active" onclick="openTab('now_showing', this)">PHIM ĐANG CHIẾU</button>
        <button class="tab-btn" onclick="openTab('coming_soon', this)">PHIM SẮP CHIẾU</button>
    </div>

    <h1 class="page-main-title" id="main-title">PHIM ĐANG CHIẾU</h1>

    <div id="now_showing" class="tab-content active">
        <div class="movie-grid">
            <?php foreach ($nowShowingMovies as $movie): ?>
                <?php 
                    $ageDesc = getAgeDescription($movie['age_restriction']);
                    $posterUrl = !empty($movie['poster']) ? $movie['poster'] : 'https://via.placeholder.com/300x450?text=No+Image';
                ?>
                <div class="movie-list-card">
                    <div class="card-poster">
                        <img src="<?= htmlspecialchars($posterUrl) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                    </div>
                    <div class="card-info">
                        <h2><?= htmlspecialchars($movie['title']) ?> (<?= $ageDesc['label'] ?>)</h2>
                        <ul class="info-tags">
                            <li>
                                <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/tag.svg" alt="Genre" class="icon-sm"> 
                                <?= htmlspecialchars($movie['genres'] ?? 'Đang cập nhật') ?>
                            </li>
                            <li>
                                <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/time.svg" alt="Duration" class="icon-sm"> 
                                <?= htmlspecialchars($movie['duration']) ?> phút
                            </li>
                            <li>
                                <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/world.svg" alt="Country" class="icon-sm"> 
                                <?= htmlspecialchars($movie['country']) ?>
                            </li>
                            <li class="age-warning" style="color: <?= $ageDesc['color'] ?>;">
                                <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <?= $ageDesc['text'] ?>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="coming_soon" class="tab-content">
        <div class="movie-grid">
            <?php foreach ($comingSoonMovies as $movie): ?>
                <?php 
                    $ageDesc = getAgeDescription($movie['age_restriction']);
                    $posterUrl = !empty($movie['poster']) ? $movie['poster'] : 'https://via.placeholder.com/300x450?text=No+Image';
                ?>
                <div class="movie-list-card">
                    <div class="card-poster">
                        <img src="<?= htmlspecialchars($posterUrl) ?>" alt="<?= htmlspecialchars($movie['title']) ?>">
                    </div>
                    <div class="card-info">
                        <h2><?= htmlspecialchars($movie['title']) ?> (<?= $ageDesc['label'] ?>)</h2>
                        <ul class="info-tags">
                            <li>
                                <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/tag.svg" alt="Genre" class="icon-sm"> 
                                <?= htmlspecialchars($movie['genres'] ?? 'Đang cập nhật') ?>
                            </li>
                            <li>
                                <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/time.svg" alt="Duration" class="icon-sm"> 
                                <?= htmlspecialchars($movie['duration']) ?> phút
                            </li>
                            <li>
                                <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/world.svg" alt="Country" class="icon-sm"> 
                                <?= htmlspecialchars($movie['country']) ?>
                            </li>
                            <img src="/MOVIE-TICKET-BOOKING/public/assets/svg/user.svg" alt="TagAge" class="icon-sm"> 
                                <?= htmlspecialchars($ageDesc['text']) ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Script để chuyển Tab
    function openTab(tabId, btnElement) {
        // 1. Ẩn tất cả tab content
        var contents = document.getElementsByClassName("tab-content");
        for (var i = 0; i < contents.length; i++) {
            contents[i].classList.remove("active");
        }
        // 2. Xóa class active ở tất cả các nút
        var btns = document.getElementsByClassName("tab-btn");
        for (var i = 0; i < btns.length; i++) {
            btns[i].classList.remove("active");
        }
        // 3. Hiển thị tab được chọn và đánh dấu nút
        document.getElementById(tabId).classList.add("active");
        btnElement.classList.add("active");
        // 4. Đổi tiêu đề chính
        document.getElementById("main-title").innerText = btnElement.innerText;
    }
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
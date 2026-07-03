<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="about-container terms-container">
    <h1 class="page-title">Về Chúng Tôi</h1>
    <p><strong>UTH Cinema</strong> là một rạp chiếu phim hiện đại vừa được thành lập với mong muốn mang đến cho khán giả những trải nghiệm điện ảnh chất lượng, nơi mỗi bộ phim không chỉ được xem mà còn được cảm nhận trọn vẹn.</p>
    
    <p>Với sứ mệnh <strong>kết nối con người thông qua những câu chuyện trên màn ảnh</strong>, UTH Cinema luôn hướng đến việc xây dựng một không gian giải trí thân thiện, hiện đại và truyền cảm hứng cho mọi lứa tuổi. Chúng tôi tin rằng điện ảnh có sức mạnh gắn kết cảm xúc, lan tỏa những giá trị tích cực và tạo nên những khoảnh khắc đáng nhớ bên gia đình, bạn bè và những người thân yêu.</p>
    
    <p>Ngay từ những ngày đầu hoạt động, UTH Cinema đã không ngừng đầu tư vào hệ thống phòng chiếu, công nghệ trình chiếu tiên tiến, âm thanh sống động cùng dịch vụ tận tâm nhằm mang đến trải nghiệm xem phim thoải mái và chân thực nhất. Bên cạnh việc cập nhật nhanh chóng các bộ phim bom tấn trong nước và quốc tế, chúng tôi cũng mong muốn góp phần quảng bá những tác phẩm điện ảnh chất lượng, đa dạng về thể loại và giàu giá trị nghệ thuật.</p>
    
    <p>Trong tương lai, UTH Cinema đặt mục tiêu trở thành một trong những điểm đến giải trí được yêu thích, không ngừng đổi mới để đáp ứng nhu cầu ngày càng cao của khán giả, đồng thời xây dựng một cộng đồng yêu điện ảnh năng động và văn minh.</p>
    
    <p class="slogan"><strong>UTH Cinema – Nơi mỗi thước phim là một hành trình cảm xúc.</strong></p>
</div>

<style>
.about-container {
    max-width: 900px;
    margin: 80px auto;
    padding: 0 20px;
    color: var(--text-light);
    background-color: transparent; /* Nền trong suốt để hiển thị nền xanh chủ đạo của body */
    border: none; /* Không khung viền */
    font-size: 1.15rem;
    line-height: 1.8;
}

.about-container p {
    margin-bottom: 25px;
    text-align: justify;
}

.about-container .slogan {
    text-align: center;
    font-size: 1.3rem;
    color: var(--primary-yellow);
    margin-top: 40px;
}

.terms-container h1.page-title {
    text-align: center;
    color: var(--primary-yellow);
    margin-bottom: 40px;
    font-size: 2.2rem;
}
</style>

<?php
require_once 'footer.php';
?>

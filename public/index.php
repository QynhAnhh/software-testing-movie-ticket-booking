<!-- http://localhost/MOVIE-TICKET-BOOKING/public/index.php -->
<?php

require_once __DIR__ . '/../app/views/layouts/header.php';
?>

<div class="banner-section">
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide">
                <img src="/movie-ticket-booking/public/assets/img/tonghop-banner.jpg" alt="Banner 1">
            </div>
            <div class="swiper-slide">
                <img src="/movie-ticket-booking/public/assets/img/minions&quaivat-banner.jpg" alt="Banner 2">
            </div>
            <div class="swiper-slide">
                <img src="/movie-ticket-booking/public/assets/img/supergirl-banner.png" alt="Banner 3">
            </div>
            <div class="swiper-slide">
                <img src="/movie-ticket-booking/public/assets/img/muave-banner.png" alt="Banner 3">
            </div>
        </div>
        
        <div class="swiper-button-next"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-pagination"></div>
    </div>
</div>

<div class="container main-content" style="min-height: 400px; padding: 40px 20px;">
    <h2>Phim Đang Chiếu</h2>
    </div>

<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>

<script>
    var swiper = new Swiper(".mySwiper", {
        spaceBetween: 0, // Đã chỉnh lại 0 để ảnh tràn viền mượt mà
        centeredSlides: true,
        loop: true, // Thêm loop để banner lặp lại liên tục
        autoplay: {
            delay: 3500, // Tăng thời gian delay để khách kịp đọc banner
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

require_once __DIR__ . '/../app/views/layouts/footer.php';
?>
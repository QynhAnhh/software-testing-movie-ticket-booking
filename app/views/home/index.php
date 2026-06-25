<div class="banner-section">
    <div class="swiper mySwiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide">
                <img src="/MOVIE-TICKET-BOOKING/public/assets/img/tonghop-banner.jpg" alt="Banner 1">
            </div>
            <div class="swiper-slide">
                <img src="/MOVIE-TICKET-BOOKING/public/assets/img/minions&quaivat-banner.jpg" alt="Banner 2">
            </div>
            <div class="swiper-slide">
                <img src="/MOVIE-TICKET-BOOKING/public/assets/img/supergirl-banner.png" alt="Banner 3">
            </div>
            <div class="swiper-slide">
                <img src="/MOVIE-TICKET-BOOKING/public/assets/img/muave-banner.png" alt="Banner 4">
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
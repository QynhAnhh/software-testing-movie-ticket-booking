<?php
require_once 'config.php';
require_once 'header.php';
?>

<div class="about-container terms-container">
    <h1 class="page-title">Hướng Dẫn Đặt Vé</h1>
    <p>Để mang đến trải nghiệm đặt vé nhanh chóng và thuận tiện, UTH Cinema cung cấp hệ thống đặt vé trực tuyến với quy trình đơn giản. Chỉ với vài bước, bạn có thể dễ dàng lựa chọn bộ phim yêu thích và đặt chỗ trước ngay tại nhà.</p>

    <h2>Bước 1: Đăng nhập hoặc đăng ký tài khoản</h2>
    <p>Truy cập website UTH Cinema và đăng nhập vào tài khoản của bạn. Nếu chưa có tài khoản, hãy đăng ký bằng email và các thông tin cá nhân cần thiết để sử dụng dịch vụ.</p>

    <h2>Bước 2: Chọn phim</h2>
    <p>Tại trang chủ hoặc mục <strong>Phim đang chiếu</strong>, chọn bộ phim mà bạn muốn xem. Bạn có thể xem trước nội dung, thời lượng, thể loại, và các thông tin liên quan trước khi đặt vé.</p>

    <h2>Bước 3: Chọn lịch chiếu</h2>
    <p>Lựa chọn ngày xem và suất chiếu phù hợp với lịch trình của bạn. Hệ thống sẽ hiển thị đầy đủ các khung giờ đang mở bán.</p>

    <h2>Bước 4: Chọn ghế ngồi</h2>
    <p>Quan sát sơ đồ phòng chiếu và chọn vị trí ghế mong muốn. Những ghế đã được đặt trước sẽ được đánh dấu và không thể lựa chọn.</p>

    <h2>Bước 5: Kiểm tra thông tin đặt vé</h2>
    <p>Xác nhận lại các thông tin bao gồm:</p>
    <ul>
        <li>Tên phim.</li>
        <li>Ngày và giờ chiếu.</li>
        <li>Phòng chiếu.</li>
        <li>Ghế đã chọn.</li>
        <li>Số lượng vé.</li>
        <li>Tổng số tiền cần thanh toán.</li>
    </ul>
    <p>Nếu mọi thông tin đều chính xác, tiếp tục sang bước thanh toán.</p>

    <h2>Bước 6: Thanh toán</h2>
    <p>Lựa chọn phương thức thanh toán phù hợp và hoàn tất giao dịch theo hướng dẫn của hệ thống. Sau khi thanh toán thành công, hệ thống sẽ tự động xác nhận đơn đặt vé.</p>

    <h2>Bước 7: Nhận vé điện tử</h2>
    <p>Vé điện tử sẽ được lưu trong tài khoản của bạn và đồng thời gửi đến email đã đăng ký. Khi đến rạp, chỉ cần xuất trình mã vé hoặc mã QR để nhân viên kiểm tra trước khi vào phòng chiếu.</p>

    <h2>Lưu ý</h2>
    <ul>
        <li>Kiểm tra kỹ thông tin phim, suất chiếu và ghế ngồi trước khi thanh toán.</li>
        <li>Đến rạp trước giờ chiếu khoảng <strong>15–30 phút</strong> để làm thủ tục và ổn định chỗ ngồi.</li>
        <li>Vé điện tử chỉ có giá trị cho đúng suất chiếu, ngày chiếu và ghế đã đặt.</li>
        <li>Không chia sẻ mã vé hoặc mã QR cho người khác để tránh bị sử dụng trái phép.</li>
        <li>Nếu gặp sự cố trong quá trình đặt vé hoặc thanh toán, vui lòng liên hệ bộ phận hỗ trợ của UTH Cinema để được trợ giúp kịp thời.</li>
    </ul>
</div>

<style>
.about-container {
    max-width: 900px;
    margin: 80px auto;
    padding: 0 20px;
    color: var(--text-light);
    background-color: transparent;
    border: none;
    font-size: 1.15rem;
    line-height: 1.8;
}

.about-container p {
    margin-bottom: 25px;
    text-align: justify;
}

.about-container ul {
    margin-bottom: 25px;
    padding-left: 20px;
}

.about-container ul li {
    margin-bottom: 10px;
    list-style-type: disc;
    text-align: justify;
}

.terms-container h1.page-title {
    text-align: center;
    color: var(--primary-yellow);
    margin-bottom: 40px;
    font-size: 2.2rem;
}

.terms-container h2 {
    color: var(--text-light);
    margin-top: 35px;
    margin-bottom: 15px;
    font-size: 1.4rem;
    border-left: 4px solid var(--primary-yellow);
    padding-left: 15px;
}
</style>

<?php
require_once 'footer.php';
?>

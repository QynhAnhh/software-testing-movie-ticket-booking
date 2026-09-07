Feature('User - Booking');

Scenario('Yêu cầu đăng nhập trước khi đặt vé', ({ I }) => {
    // Không đăng nhập mà truy cập thẳng vào trang booking (cần ID suất chiếu giả định)
    I.amOnPage('/booking.php?showtime_id=1');
    
    // Hệ thống nên bắt buộc redirect sang login
    I.seeInCurrentUrl('login.php');
});

Scenario('Luồng chọn ghế và thông tin đặt vé', ({ I }) => {
    // Luồng này phụ thuộc dữ liệu thật. 
    // Nếu có dữ liệu, codecept sẽ login và chọn ghế.
    // I.amOnPage('/login.php');
    // I.fillField('email', 'user@example.com'); ...
    // I.amOnPage('/booking.php?showtime_id=1');
    // I.seeElement('.seat');
    // I.click('.seat:not(.booked):first-child');
    // I.see('Thanh toán');
});

Scenario('Kiểm tra tính toán giá vé tự động (Price Calculation)', ({ I }) => {
    // I.amOnPage('/login.php');
    // ...
    // I.amOnPage('/booking.php?showtime_id=1');
    // I.click('.seat.regular:not(.booked):first-child');
    // I.click('.seat.vip:not(.booked):first-child');
    // Mức giá tổng (Total) phải bằng Giá Base + (Giá Base + VIP Surcharge)
    // I.seeElement('#total_price'); 
    // I.see('150.000đ', '#total_price'); // Giá trị giả định
});

Scenario('Mô phỏng 2 User đặt cùng 1 ghế (Double-booking)', ({ I }) => {
    // Session 1: User A
    // session('UserA', () => {
    //     I.amOnPage('/login.php');
    //     ...
    //     I.amOnPage('/booking.php?showtime_id=1');
    //     I.click('.seat[data-seat="A1"]');
    // });
    
    // Session 2: User B
    // session('UserB', () => {
    //     I.amOnPage('/login.php');
    //     ...
    //     I.amOnPage('/booking.php?showtime_id=1');
    //     I.click('.seat[data-seat="A1"]'); // Cùng click A1
    //     I.click('Thanh toán'); // B thanh toán trước
    // });

    // session('UserA', () => {
    //     I.click('Thanh toán'); // A thanh toán sau
    //     I.see('Ghế này đã được người khác đặt'); // Hệ thống phải báo lỗi
    // });
});

Scenario('Hủy thanh toán giữa chừng sinh ra hóa đơn Canceled', ({ I }) => {
    // I.amOnPage('/booking.php?showtime_id=1');
    // I.click('.seat.regular:not(.booked):first-child');
    // I.click('Thanh toán');
    
    // Giả lập hệ thống redirect qua cổng thanh toán Momo
    // I.seeInCurrentUrl('momo.vn/pay');
    
    // Bấm nút Hủy thanh toán hoặc tự quay lại callback URL
    // I.amOnPage('/payment_callback.php?status=failed');
    
    // Vào lịch sử kiểm tra đơn hàng có tồn tại nhưng bị Canceled
    // I.amOnPage('/booking_history.php');
    // I.see('Canceled', '.booking-status');
});

Scenario('Giải phóng ghế khi Timeout', ({ I }) => {
    // I.amOnPage('/booking.php?showtime_id=1');
    // I.click('.seat[data-seat="A2"]');
    // I.click('Thanh toán'); // Ghế A2 chuyển sang trạng thái "Hold"
    
    // Đợi 5 phút (có thể set config timeout ngắn lại trong DB test)
    // I.wait(300); // 300 giây
    
    // I.amOnPage('/booking.php?showtime_id=1');
    // Kiểm tra ghế A2 không còn class booked hay hold
    // I.dontSeeElement('.seat[data-seat="A2"].booked');
    // I.dontSeeElement('.seat[data-seat="A2"].hold');
});

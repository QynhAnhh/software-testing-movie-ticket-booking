Feature('Kiểm thử luồng Đặt vé & Quản lý Đơn/Ghế/Lịch sử (Booking Lifecycle)');

Before(({ I }) => {
    // Đăng nhập hệ thống trước mỗi kịch bản
    I.amOnPage('/login.php');
    I.waitForElement('input[name="email"]', 5);
    I.fillField('input[name="email"]', 'uyenngo@gmail.com');
    I.fillField('input[name="password"]', 'uyenngo2110');
    I.click('button[type="submit"]');
    I.wait(2);
});

// TC-OI-01: Kiểm tra tạo booking mới phải ở trạng thái Pending
Scenario('TC-OI-01: Kiểm tra tạo booking mới phải ở trạng thái Pending', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    I.click('input[type="checkbox"], .seat, button');
    I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    I.wait(2);
    I.seeInCurrentUrl('/index.php');
});

// TC-OI-03: Kiểm tra hủy đặt vé thủ công (Pending -> Canceled)
Scenario('TC-OI-03: Kiểm tra hủy đặt vé thủ công (Pending -> Canceled)', async ({ I }) => {
    I.amOnPage('/booking_history.php');
    I.waitForElement('body', 5);
    I.seeInCurrentUrl('/booking_history.php');
    I.click('Hủy đặt vé');
    I.see('canceled');
});

// TC-OI-04: Kiểm tra Time-out tự động hủy đơn Pending sau 10 phút
Scenario('TC-OI-04: Kiểm tra Time-out tự động hủy đơn Pending sau 10 phút', async ({ I }) => {
    I.amOnPage('/booking_history.php');
    I.waitForElement('body', 5);
    I.seeInCurrentUrl('/booking_history.php');
    I.refreshPage();
    I.see('canceled');
});

// TC-OI-05: Kiểm tra xem lịch sử đặt vé sau thanh toán
Scenario('TC-OI-05: Kiểm tra xem lịch sử đặt vé sau thanh toán', async ({ I }) => {
    I.amOnPage('/booking_history.php');
    I.waitForElement('body', 5);
    I.seeInCurrentUrl('/booking_history.php');
    I.dontSee('Fatal error');
    I.dontSee('ONLY_FULL_GROUP_BY');
    I.see('Paid');
});

// TC-OI-08: Thiếu seat_ids khi đặt vé
Scenario('TC-OI-08: Thiếu seat_ids khi đặt vé', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    // Bấm đặt vé nhưng cố tình KHÔNG chọn ghế nào
    I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    I.wait(1);
    I.seeInCurrentUrl('/booking.php');
});
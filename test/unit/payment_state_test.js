Feature('Kiểm thử luồng trạng thái đặt vé và thanh toán (State Transition)');

Before(({ I }) => {
    // Đăng nhập tài khoản trước mỗi kịch bản test
    I.amOnPage('/login.php');
    I.waitForElement('input[name="email"]', 5);
    I.fillField('input[name="email"]', 'uyenngo@gmail.com');
    I.fillField('input[name="password"]', 'uyenngo2110');
    I.click('button[type="submit"]');
    I.wait(2);
});

// TC-OI-01: Tạo đơn đặt vé mới ở trạng thái Pending
Scenario('TC-OI-01: Đặt vé mới khởi tạo trạng thái Pending', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    I.click('input[type="checkbox"], .seat, button');
    I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    I.wait(2);
    I.seeInCurrentUrl('/index.php');
});

// TC-OI-02: Kiểm tra chuyển trạng thái Pending -> Paid khi thanh toán thành công
Scenario('TC-OI-02: Kiểm tra chuyển trạng thái Pending -> Paid khi thanh toán thành công', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    I.click('input[type="checkbox"], .seat, button');
    I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    I.wait(2);
    I.amOnPage('/booking_history.php');
    I.see('Paid');
});

// TC-OI-03: Kiểm tra hủy đặt vé thủ công (Pending -> Canceled)
Scenario('TC-OI-03: Kiểm tra hủy đặt vé thủ công (Pending -> Canceled)', async ({ I }) => {
    I.amOnPage('/booking_history.php');
    I.waitForElement('body', 5);
    I.seeInCurrentUrl('/booking_history.php');
    I.click('Hủy đặt vé'); // Bấm nút hủy đặt vé thủ công
    I.see('canceled');    // Khẳng định trạng thái chuyển thành canceled
});

// TC-OI-04: Kiểm tra Time-out tự động hủy đơn Pending sau 10 phút
Scenario('TC-OI-04: Kiểm tra Time-out tự động hủy đơn Pending sau 10 phút', async ({ I }) => {
    I.amOnPage('/booking_history.php');
    I.waitForElement('body', 5);
    I.seeInCurrentUrl('/booking_history.php');
    I.refreshPage();       // Kích hoạt quét timeout tự động của backend
    I.see('canceled');    // Khẳng định đơn hàng treo quá hạn tự động chuyển canceled
});

// TC-OI-05: Kiểm tra xem lịch sử đặt vé sau khi thanh toán
Scenario('TC-OI-05: Kiểm tra xem lịch sử đặt vé sau khi thanh toán', async ({ I }) => {
    I.amOnPage('/booking_history.php');
    I.waitForElement('body', 5);
    I.seeInCurrentUrl('/booking_history.php');
    I.dontSee('Fatal error');
    I.dontSee('ONLY_FULL_GROUP_BY');
    I.see('Paid');
});

// TC-OI-11: Chặn thanh toán khi Session đăng nhập hết hạn đột ngột
Scenario('TC-OI-11: Chặn thanh toán khi Session đăng nhập hết hạn đột ngột', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    I.clearCookie();
    I.click('button[type="submit"], .btn-confirm');
    I.wait(1);
    I.seeInCurrentUrl('/index.php');
});
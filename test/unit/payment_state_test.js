Feature('Kiểm thử luồng thanh toán và xác thực phiên (Payment State Transition)');

Before(({ I }) => {
    // Đăng nhập tài khoản trước mỗi kịch bản test
    I.amOnPage('/login.php');
    I.waitForElement('input[name="email"]', 5);
    I.fillField('input[name="email"]', 'uyenngo@gmail.com');
    I.fillField('input[name="password"]', 'uyenngo2110');
    I.click('button[type="submit"]');
    I.wait(2);
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

// TC-OI-11: Chặn thanh toán khi Session đăng nhập hết hạn đột ngột
Scenario('TC-OI-11: Chặn thanh toán khi Session đăng nhập hết hạn đột ngột', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    I.clearCookie();
    I.click('button[type="submit"], .btn-confirm');
    I.wait(1);
    I.seeInCurrentUrl('/index.php');
});
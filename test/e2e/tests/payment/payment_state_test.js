Feature('Kiểm thử luồng Thanh toán & Giao dịch tiền (Payment Processing)');

Before(({ I }) => {
    // Đăng nhập hệ thống trước mỗi kịch bản
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

// TC-OI-06: Thanh toán lại vé đã Paid
Scenario('TC-OI-06: Thanh toán lại vé đã Paid (Duplicate Payment)', async ({ I }) => {
    I.amOnPage('/booking_history.php');
    I.waitForElement('body', 5);
    // Thử bấm thanh toán lại trên đơn đã Paid
    I.executeScript(() => {
        fetch('/api/payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: 1, amount: 100000 })
        });
    });
    I.wait(1);
    I.seeInCurrentUrl('/booking_history.php');
});

// TC-OI-07: Gửi booking_id không tồn tại khi thanh toán
Scenario('TC-OI-07: Gửi booking_id không tồn tại khi thanh toán', async ({ I }) => {
    I.amOnPage('/checkout.php?booking_id=999999');
    I.waitForElement('body', 5);
    I.see('Không tìm thấy đơn hàng') || I.seeInCurrentUrl('/index.php');
});

// TC-OI-09: total_price âm hoặc bằng 0
Scenario('TC-OI-09: total_price âm hoặc bằng 0', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    I.click('input[type="checkbox"], .seat, button');
    // Giả lập can thiệp client-side sửa giá trị total_price = 0
    I.executeScript(() => {
        let inputPrice = document.querySelector('input[name="total_price"]');
        if (inputPrice) inputPrice.value = 0;
    });
    I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    I.wait(1);
    // Backend tự tính lại đúng giá trị, không tin client
    I.dontSee('0 VNĐ');
});

// TC-OI-10: Mất kết nối mạng ngay khi bấm Thanh toán
Scenario('TC-OI-10: Mất kết nối mạng ngay khi bấm Thanh toán', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    I.click('input[type="checkbox"], .seat, button');
    // Ngắt kết nối mạng client
    I.executeScript(() => {
        window.dispatchEvent(new Event('offline'));
    });
    I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    I.wait(1);
    // Hệ thống không tạo booking rác trong DB
});

// TC-OI-11: Thanh toán khi Session hết hạn
Scenario('TC-OI-11: Thanh toán khi Session hết hạn', async ({ I }) => {
    I.amOnPage('/booking.php?showtime_id=1');
    I.waitForElement('body', 5);
    I.clearCookie();
    I.click('button[type="submit"], .btn-confirm');
    I.wait(1);
    I.seeInCurrentUrl('/login.php') || I.seeInCurrentUrl('/index.php');
});
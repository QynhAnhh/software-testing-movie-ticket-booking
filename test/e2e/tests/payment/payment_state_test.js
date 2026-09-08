Feature('Kiểm thử luồng Thanh toán & Giao dịch tiền (Payment Processing)');

// Định nghĩa helper local để tránh trùng lặp cấu trúc Before với booking_state_test.js
const performLoginSequence = async (I, userEmail, userPassword) => {
    /* Step 1: Điều hướng đến trang đăng nhập */
    await I.amOnPage('/login.php');
    await I.waitForElement('input[name="email"]', 5);

    /* Step 2: Nhập thông tin tài khoản người dùng */
    await I.fillField('input[name="email"]', userEmail);
    await I.fillField('input[name="password"]', userPassword);

    /* Step 3: Xác nhận đăng nhập */
    await I.click('button[type="submit"]');
    await I.wait(2);
};

Before(async ({ I }) => {
    // Thực hiện đăng nhập thông qua helper function
    await performLoginSequence(I, 'uyenngo@gmail.com', 'uyenngo2110');
});

// TC-OI-02: Kiểm tra chuyển trạng thái Pending -> Paid khi thanh toán thành công
Scenario('TC-OI-02: Kiểm tra chuyển trạng thái Pending -> Paid khi thanh toán thành công', async ({ I }) => {
    await I.amOnPage('/booking.php?showtime_id=1');
    await I.waitForElement('body', 5);
    
    // Chọn ghế và xác nhận đặt vé
    await I.click('input[type="checkbox"], .seat, button');
    await I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    await I.wait(2);

    // Kiểm tra lịch sử đặt vé có cập nhật trạng thái Paid
    await I.amOnPage('/booking_history.php');
    await I.see('Paid');
});

// TC-OI-06: Thanh toán lại vé đã Paid
Scenario('TC-OI-06: Thanh toán lại vé đã Paid (Duplicate Payment)', async ({ I }) => {
    await I.amOnPage('/booking_history.php');
    await I.waitForElement('body', 5);

    // Gửi request thanh toán trùng lặp trực tiếp từ phía client
    await I.executeScript(() => {
        return fetch('/api/payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: 1, amount: 100000 })
        });
    });
    
    await I.wait(1);
    await I.seeInCurrentUrl('/booking_history.php');
});

// TC-OI-07: Gửi booking_id không tồn tại khi thanh toán
Scenario('TC-OI-07: Gửi booking_id không tồn tại khi thanh toán', async ({ I }) => {
    const invalidBookingId = 999999;
    await I.amOnPage(`/checkout.php?booking_id=${invalidBookingId}`);
    await I.waitForElement('body', 5);

    // Kiểm tra thông báo lỗi hoặc chuyển hướng an toàn
    const hasErrorText = await I.see('Không tìm thấy đơn hàng');
    if (!hasErrorText) {
        await I.seeInCurrentUrl('/index.php');
    }
});

// TC-OI-09: total_price âm hoặc bằng 0
Scenario('TC-OI-09: total_price âm hoặc bằng 0', async ({ I }) => {
    await I.amOnPage('/booking.php?showtime_id=1');
    await I.waitForElement('body', 5);
    await I.click('input[type="checkbox"], .seat, button');

    // Thử nghiệm can thiệp giá trị total_price về 0 từ client DOM
    await I.executeScript(() => {
        const priceInputField = document.querySelector('input[name="total_price"]');
        if (priceInputField) {
            priceInputField.value = '0';
        }
    });

    await I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    await I.wait(1);
    await I.dontSee('0 VNĐ');
});

// TC-OI-10: Mất kết nối mạng ngay khi bấm Thanh toán
Scenario('TC-OI-10: Mất kết nối mạng ngay khi bấm Thanh toán', async ({ I }) => {
    await I.amOnPage('/booking.php?showtime_id=1');
    await I.waitForElement('body', 5);
    await I.click('input[type="checkbox"], .seat, button');

    // Giả lập sự kiện ngắt kết nối mạng trên browser
    await I.executeScript(() => {
        window.dispatchEvent(new Event('offline'));
    });

    await I.click('button[type="submit"], .btn-confirm, #btn-confirm');
    await I.wait(1);
});

// TC-OI-11: Thanh toán khi Session hết hạn
Scenario('TC-OI-11: Thanh toán khi Session hết hạn', async ({ I }) => {
    await I.amOnPage('/booking.php?showtime_id=1');
    await I.waitForElement('body', 5);

    // Xóa cookie để giả lập hết hạn session người dùng
    await I.clearCookie();

    await I.click('button[type="submit"], .btn-confirm');
    await I.wait(1);

    // Hệ thống phải đẩy về trang đăng nhập hoặc trang chủ
    await I.seeInCurrentUrl('/login.php');
});
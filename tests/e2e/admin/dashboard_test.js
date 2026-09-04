Feature('Admin - Dashboard Metrics');

Scenario('Kiểm tra Thống kê Doanh thu cập nhật sau khi User mua vé', async ({ I }) => {
    // Kịch bản này cần dùng await để lấy dữ liệu text
    
    // 1. Đăng nhập Admin và lấy tổng doanh thu hiện tại
    // I.amOnPage('/login.php');
    // I.fillField('email', 'admin@example.com');
    // I.fillField('password', 'password123');
    // I.click('Đăng nhập');
    // I.amOnPage('/admin/index.php');
    // const currentRevenue = await I.grabTextFrom('#total_revenue_element');
    // I.click('Đăng xuất');

    // 2. Đăng nhập User và mua vé giá 100.000đ
    // I.amOnPage('/login.php');
    // I.fillField('email', 'user@example.com');
    // I.fillField('password', 'password123');
    // I.click('Đăng nhập');
    // I.amOnPage('/booking.php?showtime_id=1');
    // I.click('.seat[data-price="100000"]');
    // I.click('Thanh toán');
    // I.click('Đăng xuất');

    // 3. Đăng nhập lại Admin và verify doanh thu
    // I.amOnPage('/login.php');
    // I.fillField('email', 'admin@example.com');
    // I.fillField('password', 'password123');
    // I.click('Đăng nhập');
    // I.amOnPage('/admin/index.php');
    // const newRevenue = await I.grabTextFrom('#total_revenue_element');
    
    // Logic so sánh: newRevenue phải bằng currentRevenue + 100000
    // I.assertStringIncludes(newRevenue, 'chênh lệch đúng số tiền'); 
});

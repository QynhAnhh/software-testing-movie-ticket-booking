Feature('User - Profile');

Scenario('Truy cập trang cá nhân yêu cầu đăng nhập', ({ I }) => {
    I.amOnPage('/profile.php');
    
    // Vì chưa đăng nhập, người dùng sẽ bị chuyển hướng
    I.seeInCurrentUrl('login.php');
});

Scenario('Truy cập trang lịch sử đặt vé yêu cầu đăng nhập', ({ I }) => {
    I.amOnPage('/booking_history.php');
    
    // Vì chưa đăng nhập, người dùng sẽ bị chuyển hướng
    I.seeInCurrentUrl('login.php');
});

Scenario('Người dùng cập nhật thông tin cá nhân', ({ I }) => {
    // I.amOnPage('/login.php');
    // ... logic đăng nhập ...
    // I.amOnPage('/profile.php');
    
    // I.fillField('first_name', 'Tên Mới');
    // I.fillField('phone', '0988888888');
    // I.click('Cập nhật', 'button[type=submit]');
    
    // Kiểm tra xem dữ liệu mới đã được lưu và hiển thị lại trên trang hay chưa
    // I.seeInField('first_name', 'Tên Mới');
    // I.seeInField('phone', '0988888888');
    // I.see('Cập nhật thành công');
});

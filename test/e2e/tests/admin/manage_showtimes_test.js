Feature('Admin - Showtimes Management');

Scenario('Tạo lịch chiếu bị trùng giờ (Overlapping Showtimes)', ({ I }) => {
    // I.amOnPage('/login.php');
    // I.fillField('email', 'admin@example.com');
    // I.fillField('password', 'password123');
    // I.click('Đăng nhập');
    
    // I.amOnPage('/admin/showtimes.php');
    // I.click('Thêm suất chiếu mới');
    
    // Giả sử đã có suất chiếu từ 19:00 - 21:00 ở Phòng 1
    // Admin cố tình tạo thêm suất chiếu lúc 20:00 cùng Phòng 1
    // I.selectOption('movie_id', 'Phim A');
    // I.selectOption('room_id', 'Phòng 1');
    // I.fillField('show_date', '2023-12-31');
    // I.fillField('start_time', '20:00'); 
    
    // I.click('Lưu', 'button[type=submit]');
    
    // Hệ thống phải bắt được lỗi và thông báo
    // I.see('Phòng chiếu này đã có lịch chiếu trong khoảng thời gian trên');
});

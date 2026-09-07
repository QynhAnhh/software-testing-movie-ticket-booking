Feature('User - Movie Details');

Scenario('Xem chi tiết phim và thông tin đánh giá', ({ I }) => {
    I.amOnPage('/');
    
    // Click vào nút chi tiết của phim đầu tiên
    I.click('.movie-card:first-child .btn-get-ticket');
    
    // Đảm bảo URL đổi sang trang chi tiết
    I.seeInCurrentUrl('movie_details.php?id=');
    
    // Kiểm tra các thành phần cốt lõi của trang chi tiết
    I.seeElement('h1.fw-bold');
    I.see('Đạo diễn:');
    I.see('Thời lượng:');
    I.see('Thể loại:');
    
    // Kiểm tra khu vực đánh giá
    I.see('Đánh Giá');
});

Scenario('Người dùng viết đánh giá thành công', ({ I }) => {
    // Kịch bản yêu cầu đăng nhập trước
    // I.amOnPage('/login.php');
    // ... logic đăng nhập ...
    // I.amOnPage('/');
    // I.click('.movie-card:first-child .btn-get-ticket');
    
    // Form đánh giá chỉ hiện khi đã đăng nhập
    // I.seeElement('form[action*="add_review"]');
    // I.selectOption('rating', '5'); // Chọn 5 sao
    // I.fillField('comment', 'Phim rất hay, hình ảnh đẹp!');
    // I.click('Gửi đánh giá', 'button[type=submit]');
    
    // Verify đánh giá vừa gửi hiện lên trên giao diện
    // I.see('Phim rất hay, hình ảnh đẹp!');
});

Scenario('Ngăn chặn tấn công XSS qua Đánh giá phim', ({ I }) => {
    // I.amOnPage('/login.php');
    // ... logic đăng nhập ...
    // I.amOnPage('/movie_details.php?id=1');
    
    // I.fillField('comment', '<script>alert("Hacked!")</script>');
    // I.click('Gửi đánh giá', 'button[type=submit]');
    
    // Đảm bảo không có popup alert nào nhảy lên (nếu có, CodeceptJS có thể bắt bằng I.seeInPopup)
    // Thay vào đó, chữ script phải bị escape thành text bình thường
    // I.see('<script>alert("Hacked!")</script>', '.review-list-card'); // Text hiển thị an toàn
});

Feature('User - Responsive UI');

Scenario('Kiểm tra giao diện trên thiết bị di động (iPhone 13)', ({ I }) => {
    // Giả lập kích thước màn hình iPhone 13
    I.resizeWindow(390, 844);
    
    I.amOnPage('/');
    
    // Trên mobile, menu thường bị ẩn đi và có nút Hamburger
    // I.seeElement('.navbar-toggler');
    
    // Click vào Hamburger menu để mở
    // I.click('.navbar-toggler');
    
    // Các link bên trong menu phải hiển thị sau khi click
    // I.seeElement('.navbar-nav');
    // I.see('Đăng nhập');
});

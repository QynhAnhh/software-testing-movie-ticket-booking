Feature('Admin - Movies Management');

Scenario('Chặn quyền truy cập nếu không phải Admin', ({ I }) => {
    // Truy cập thẳng mà không đăng nhập
    I.amOnPage('/admin/index.php');
    
    // Cần phải redirect ra ngoài hoặc báo lỗi
    I.seeInCurrentUrl('login.php');
});

Scenario('Hiển thị danh sách phim cho Admin', ({ I }) => {
    // I.amOnPage('/login.php');
    // I.fillField('email', 'admin@example.com');
    // I.fillField('password', 'password123');
    // I.click('Đăng nhập');
    
    // I.amOnPage('/admin/movies.php');
    // I.see('Quản lý Phim');
    // I.seeElement('.table');
});

Scenario('Ngăn chặn xóa Phim đang có lịch chiếu hoặc người đặt', ({ I }) => {
    // I.amOnPage('/login.php');
    // ... logic đăng nhập ...
    // I.amOnPage('/admin/movies.php');
    
    // Giả sử phim ID=1 đang có lịch chiếu và người đặt
    // I.click('Xóa', 'tr[data-movie-id="1"]');
    // I.acceptPopup();
    
    // Hệ thống nên báo lỗi không cho phép xóa
    // I.see('Không thể xóa phim này vì đã có lịch chiếu hoặc đơn đặt vé');
});

Scenario('Bảo vệ Route: User thường không thể truy cập trang Admin', ({ I }) => {
    // 1. Đăng nhập với tư cách User thường
    // I.amOnPage('/login.php');
    // I.fillField('email', 'user@example.com');
    // I.fillField('password', 'password123');
    // I.click('Đăng nhập');

    // 2. Cố tình gõ URL admin
    // I.amOnPage('/admin/movies.php');
    
    // 3. Phải bị chặn lại (chuyển hướng về trang chủ hoặc hiện lỗi 403)
    // I.seeInCurrentUrl('index.php'); // hoặc login.php tùy logic
    // I.dontSee('Quản lý Phim');
});

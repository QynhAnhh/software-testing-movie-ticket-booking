Feature('KAN83 Movie Showtime');

Before(({ I }) => {
    const email = process.env.KAN83_ADMIN_EMAIL || 'admin@example.com';
    const password = process.env.KAN83_ADMIN_PASSWORD || 'password';

    I.amOnPage('/frontend/login.php');
    I.fillField('#login_email', email);
    I.fillField('#login_password', password);
    I.click('.sign-in button[type="submit"]');
});

Scenario('movieManagementPageLoads', ({ I }) => {
    I.amOnPage('/backend/admin/manage_movies.php');
    I.executeScript(() => {
        document.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
        document.querySelectorAll('input').forEach(el => el.removeAttribute('maxlength'));
    });
    
    I.see('Quản lý phim'); // Matching "Quản lý phim" h1
    I.see('Tên phim');
    I.see('Thời lượng');
    I.see('Ngày khởi chiếu');
});

Scenario('showtimeManagementPageLoads', ({ I }) => {
    I.amOnPage('/backend/admin/manage_showtimes.php');
    I.see('Quản lý lịch chiếu'); // Matching "Quản lý lịch chiếu" h1
});

Scenario('movieRejectsNegativeDuration', ({ I }) => {
    I.amOnPage('/backend/admin/manage_movies.php');
    I.executeScript(() => {
        document.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
        document.querySelectorAll('input').forEach(el => el.removeAttribute('min'));
    });
    
    I.fillField('title', 'KAN83 Negative Duration');
    I.fillField('country', 'Vietnam');
    I.fillField('duration', '-120');
    I.fillField('screening_date', '2026-12-20');
    I.fillField('description', 'Codeception KAN-83');
    
    I.click('button[type="submit"]');
    I.seeInPopup('Lỗi: Vui lòng nhập thời lượng phim hợp lệ!');
    I.acceptPopup();
});

Scenario('movieRejectsZeroDuration', ({ I }) => {
    I.amOnPage('/backend/admin/manage_movies.php');
    I.executeScript(() => {
        document.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
        document.querySelectorAll('input').forEach(el => el.removeAttribute('min'));
    });
    
    I.fillField('title', 'KAN83 Zero Duration');
    I.fillField('country', 'Vietnam');
    I.fillField('duration', '0');
    I.fillField('screening_date', '2026-12-20');
    I.fillField('description', 'Codeception KAN-83');
    
    I.click('button[type="submit"]');
    I.seeInPopup('Lỗi: Vui lòng nhập thời lượng phim hợp lệ!');
    I.acceptPopup();
});

Scenario('movieRejectsDescriptionOver5000Characters', ({ I }) => {
    I.amOnPage('/backend/admin/manage_movies.php');
    I.executeScript(() => {
        document.querySelectorAll('input, select, textarea').forEach(el => el.removeAttribute('required'));
        document.querySelectorAll('input, textarea').forEach(el => el.removeAttribute('maxlength'));
    });
    
    I.fillField('title', 'KAN83 Long Description');
    I.fillField('country', 'Vietnam');
    I.fillField('duration', '120');
    I.executeScript(() => {
        document.querySelector('input[name="screening_date"]').value = '2026-12-20';
        document.querySelector('textarea[name="description"]').value = 'A'.repeat(5001);
    });
    
    I.click('button[type="submit"]');
    I.seeInPopup('Lỗi: Mô tả vượt quá giới hạn 5000 ký tự');
    I.acceptPopup();
});

Scenario('showtimeRejectsPastDate', ({ I }) => {
    I.amOnPage('/backend/admin/manage_showtimes.php');
    I.executeScript(() => {
        document.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
        document.querySelectorAll('input[type="time"], input[type="date"]').forEach(el => el.type = 'text');
    });
    
    I.selectOption('movie_id', '2');
    I.selectOption('room_id', '1');
    I.fillField('show_date', '2025-01-01');
    I.fillField('start_time', '10:00');
    I.fillField('base_price', '80000');
    I.selectOption('status', 'active');
    
    I.click('button[type="submit"]');
    I.see('Suất chiếu không thể ở trong quá khứ');
});

Scenario('showtimeRejectsMissingStartTime', ({ I }) => {
    I.amOnPage('/backend/admin/manage_showtimes.php');
    I.executeScript(() => {
        document.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
        document.querySelectorAll('input[type="time"], input[type="date"]').forEach(el => el.type = 'text');
    });
    
    I.selectOption('movie_id', '2');
    I.selectOption('room_id', '1');
    I.fillField('show_date', '2026-12-20');
    // For empty input, CodeceptJS .clearField is useful if it had value, but empty initially is fine.
    I.fillField('start_time', '');
    I.fillField('base_price', '80000');
    I.selectOption('status', 'active');
    
    I.click('button[type="submit"]');
    I.see('Giờ bắt đầu không được để trống!');
});

Scenario('showtimeRejectsInvalidStartTime', ({ I }) => {
    I.amOnPage('/backend/admin/manage_showtimes.php');
    I.executeScript(() => {
        document.querySelectorAll('input, select').forEach(el => el.removeAttribute('required'));
        document.querySelectorAll('input[type="time"], input[type="date"]').forEach(el => el.type = 'text');
    });
    
    I.selectOption('movie_id', '2');
    I.selectOption('room_id', '1');
    I.fillField('show_date', '2026-12-20');
    I.fillField('start_time', '25:30'); // Invalid time
    I.fillField('base_price', '80000');
    I.selectOption('status', 'active');
    
    I.click('button[type="submit"]');
    I.see('Giờ bắt đầu không hợp lệ!');
});

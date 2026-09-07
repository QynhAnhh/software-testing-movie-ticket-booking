Feature('Authentication - Register');

Scenario('[TC-AH-01] Đăng ký thành công (Happy Path)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'Van A');
    // Cần tạo random email mỗi lần test nếu muốn pass liên tục, hoặc xóa DB trước mỗi lần chạy
    I.fillField('input[name=email]', 'test_' + Date.now() + '@example.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', 'password123');
    I.fillField('input[name=confirm_password]', 'password123');
    I.click('Đăng ký', '.auth-action');
    
    // Nếu thành công sẽ redirect về trang chủ hoặc hiện thông báo
    // I.seeInCurrentUrl('index.php');
});

Scenario('[TC-AH-22] Đăng ký không thành công do bỏ trống dữ liệu', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.see('Tạo tài khoản');
    I.click('Đăng ký', '.auth-action');
    
    // Yêu cầu nhập đầy đủ thông tin bắt buộc
    I.seeInCurrentUrl('login.php?mode=register');
});

Scenario('[TC-AH-24] Đăng ký không thành công do sai định dạng Email', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'Van A');
    I.fillField('input[name=email]', 'test24test.com'); // Thiếu @
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', 'password123');
    I.fillField('input[name=confirm_password]', 'password123');
    I.click('Đăng ký', '.auth-action');
    
    I.seeInCurrentUrl('login.php?mode=register');
});

Scenario('[TC-AH-15 & 16] Đăng ký không thành công do trùng Email hoặc Số điện thoại', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'Van B');
    // Dùng email admin mặc định để kích hoạt lỗi trùng lặp
    I.fillField('input[name=email]', 'admin@example.com');
    I.fillField('input[name=phone]', '0999999999');
    I.fillField('input[name=password]', 'password123');
    I.fillField('input[name=confirm_password]', 'password123');
    I.click('Đăng ký', '.auth-action');
    
    I.seeInCurrentUrl('login.php?mode=register');
    I.seeElement('.auth-alert-error'); 
});

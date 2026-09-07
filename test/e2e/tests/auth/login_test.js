Feature('Authentication - Login');

Scenario('[TC-AH-17] Đăng nhập với tài khoản hợp lệ', ({ I }) => {
    I.amOnPage('/login.php');
    I.fillField('form[action="login.php"] input[name=email]', 'exist@test.com');
    I.fillField('form[action="login.php"] input[name=password]', '123456');
    I.click('Đăng nhập', 'form[action="login.php"] button[type=submit]');
    I.dontSeeElement('.auth-alert-error');
});

Scenario('[TC-AH-18] Kiểm tra đăng nhập sai mật khẩu', ({ I }) => {
    I.amOnPage('/login.php');
    I.fillField('form[action="login.php"] input[name=email]', 'exist@test.com');
    I.fillField('form[action="login.php"] input[name=password]', 'wrongpass');
    I.click('Đăng nhập', 'form[action="login.php"] button[type=submit]');
    I.see('Email hoặc mật khẩu không đúng!');
});

Scenario('[TC-AH-19] Kiểm tra đăng nhập với Email chưa đăng ký', ({ I }) => {
    I.amOnPage('/login.php');
    I.fillField('form[action="login.php"] input[name=email]', 'notfound@test.com');
    I.fillField('form[action="login.php"] input[name=password]', '123456');
    I.click('Đăng nhập', 'form[action="login.php"] button[type=submit]');
    I.see('Email hoặc mật khẩu không đúng!');
});

Scenario('[TC-AH-20] Kiểm tra đăng nhập khi để trống dữ liệu', ({ I }) => {
    I.amOnPage('/login.php');
    I.click('Đăng nhập', 'form[action="login.php"] button[type=submit]');
    I.seeInCurrentUrl('login.php');
});

Feature('Authentication - Login');

Scenario('[TC-AH-18 & 19] Đăng nhập không thành công với thông tin sai', ({ I }) => {
    I.amOnPage('/login.php');

    I.see('Đăng nhập');

    I.fillField(
        'form[action="login.php"] input[name=email]',
        'wrong_email@example.com'
    );

    I.fillField(
        'form[action="login.php"] input[name=password]',
        'wrongpassword123'
    );

    I.click(
        'Đăng nhập',
        'form[action="login.php"] button[type=submit]'
    );

    I.seeInCurrentUrl('login.php');
    I.seeElement('.auth-alert-error');
});


Scenario('[TC-AH-17] Đăng nhập với tài khoản hợp lệ (Admin)', ({ I }) => {
    I.amOnPage('/login.php');

    I.see('Đăng nhập');

    I.fillField(
        'form[action="login.php"] input[name=email]',
        'admin@example.com'
    );

    I.fillField(
        'form[action="login.php"] input[name=password]',
        'password123'
    );

    I.click(
        'Đăng nhập',
        'form[action="login.php"] button[type=submit]'
    );

    // Kiểm tra đăng nhập thành công
    I.seeInCurrentUrl('/admin/');
});
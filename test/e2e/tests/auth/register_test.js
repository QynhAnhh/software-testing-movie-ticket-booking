Feature('Authentication - Register');

Scenario('[TC-AH-01] Kiểm tra đăng ký với mật khẩu hợp lệ (13 ký tự - Lớp hợp lệ)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test_' + Date.now() + '@test.com');
    I.fillField('input[name=phone]', '090' + Math.floor(1000000 + Math.random() * 9000000));
    I.fillField('input[name=password]', 'abcdfhgjtghtg');
    I.fillField('input[name=confirm_password]', 'abcdfhgjtghtg');
    I.click('Đăng ký', '.auth-action');
    I.dontSeeElement('.auth-alert-error');
});

Scenario('[TC-AH-02] Kiểm tra đăng ký với mật khẩu quá ngắn (3 ký tự - Lớp không hợp lệ)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test2@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', 'abc');
    I.fillField('input[name=confirm_password]', 'abc');
    I.click('Đăng ký', '.auth-action');
    I.see('Mật khẩu phải từ 6-20 ký tự');
});

Scenario('[TC-AH-03] Kiểm tra đăng ký với mật khẩu quá dài (25 ký tự - Lớp không hợp lệ)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test3@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', 'abcdefghijklmnopqrstuvwxy');
    I.fillField('input[name=confirm_password]', 'abcdefghijklmnopqrstuvwxy');
    I.click('Đăng ký', '.auth-action');
    I.see('Mật khẩu không được vượt quá 20 ký tự');
});

Scenario('[TC-AH-04] Kiểm tra mật khẩu có 5 ký tự (Dưới biên dưới)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test4@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', 'abcde');
    I.fillField('input[name=confirm_password]', 'abcde');
    I.click('Đăng ký', '.auth-action');
    I.see('Mật khẩu phải từ 6-20 ký tự');
});

Scenario('[TC-AH-05] Kiểm tra mật khẩu có 6 ký tự (Ngay biên dưới)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test5_' + Date.now() + '@test.com');
    I.fillField('input[name=phone]', '090' + Math.floor(1000000 + Math.random() * 9000000));
    I.fillField('input[name=password]', 'abcdef');
    I.fillField('input[name=confirm_password]', 'abcdef');
    I.click('Đăng ký', '.auth-action');
    I.dontSeeElement('.auth-alert-error');
});

Scenario('[TC-AH-06] Kiểm tra mật khẩu có 7 ký tự (Trên biên dưới)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test6_' + Date.now() + '@test.com');
    I.fillField('input[name=phone]', '090' + Math.floor(1000000 + Math.random() * 9000000));
    I.fillField('input[name=password]', 'abcdefg');
    I.fillField('input[name=confirm_password]', 'abcdefg');
    I.click('Đăng ký', '.auth-action');
    I.dontSeeElement('.auth-alert-error');
});

Scenario('[TC-AH-07] Kiểm tra mật khẩu có 19 ký tự (Dưới biên trên)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test7_' + Date.now() + '@test.com');
    I.fillField('input[name=phone]', '090' + Math.floor(1000000 + Math.random() * 9000000));
    I.fillField('input[name=password]', 'abcdefghijklmnopqrs');
    I.fillField('input[name=confirm_password]', 'abcdefghijklmnopqrs');
    I.click('Đăng ký', '.auth-action');
    I.dontSeeElement('.auth-alert-error');
});

Scenario('[TC-AH-08] Kiểm tra mật khẩu có 20 ký tự (Ngay biên trên)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test8_' + Date.now() + '@test.com');
    I.fillField('input[name=phone]', '090' + Math.floor(1000000 + Math.random() * 9000000));
    I.fillField('input[name=password]', 'abcdefghijklmnopqrst');
    I.fillField('input[name=confirm_password]', 'abcdefghijklmnopqrst');
    I.click('Đăng ký', '.auth-action');
    I.dontSeeElement('.auth-alert-error');
});

Scenario('[TC-AH-09] Kiểm tra mật khẩu có 21 ký tự (Trên biên trên)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test9@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', 'abcdefghijklmnopqrstu');
    I.fillField('input[name=confirm_password]', 'abcdefghijklmnopqrstu');
    I.click('Đăng ký', '.auth-action');
    I.see('Mật khẩu không được vượt quá 20 ký tự');
});

Scenario('[TC-AH-11] Kiểm tra SĐT chứa chữ cái (Lớp không hợp lệ)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test11@test.com');
    I.fillField('input[name=phone]', '0901a14d67');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Số điện thoại chỉ được chứa số');
});

Scenario('[TC-AH-12] Kiểm tra SĐT ít hơn 10 chữ số (Lớp không hợp lệ)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test12@test.com');
    I.fillField('input[name=phone]', '012345678');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Số điện thoại phải bao gồm 10 số');
});

Scenario('[TC-AH-13] Kiểm tra SĐT nhiều hơn 10 chữ số (Lớp không hợp lệ)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test13@test.com');
    I.fillField('input[name=phone]', '01234567890');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Số điện thoại phải bao gồm 10 số');
});

Scenario('[TC-AH-14] Kiểm tra SĐT không bắt đầu bằng số 0', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test14@test.com');
    I.fillField('input[name=phone]', '1234567890');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Số điện thoại phải bắt đầu bằng số 0');
});

Scenario('[TC-AH-15] Kiểm tra đăng ký trùng Email đã tồn tại', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'exist@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Email này đã được sử dụng');
});

Scenario('[TC-AH-16] Kiểm tra đăng ký trùng SĐT đã tồn tại', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test16@test.com');
    I.fillField('input[name=phone]', '0922222222');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Số điện thoại này đã được sử dụng');
});

Scenario('[TC-AH-21] Kiểm tra Xác nhận mật khẩu không khớp', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test21@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123457');
    I.click('Đăng ký', '.auth-action');
    I.see('Xác nhận mật khẩu không khớp');
});

Scenario('[TC-AH-22] Kiểm tra đăng ký khi để trống tất cả các trường', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.click('Đăng ký', '.auth-action');
    I.seeInCurrentUrl('login.php');
});

Scenario('[TC-AH-23] Kiểm tra đăng ký khi để trống 1 trường bắt buộc', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.seeInCurrentUrl('login.php');
});

Scenario('[TC-AH-24] Kiểm tra Email sai định dạng (Thiếu ký tự @)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test24test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Email không đúng định dạng');
});

Scenario('[TC-AH-25] Kiểm tra Email sai định dạng (Thiếu tên miền)', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test24@');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Email không đúng định dạng');
});

Scenario('[TC-AH-26] Kiểm tra Họ chứa ký tự đặc biệt / số', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen 123');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test26@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Họ không được chứa số hoặc ký tự đặc biệt');
});

Scenario('[TC-AH-27] Kiểm tra Số điện thoại chứa ký tự đặc biệt', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test27@test.com');
    I.fillField('input[name=phone]', '0901234567@');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Số điện thoại không hợp lệ');
});

Scenario('[TC-AH-28] Kiểm tra Tên có ký tự đặc biệt / số', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A@');
    I.fillField('input[name=email]', 'test28@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Tên không được chứa số hoặc ký tự đặc biệt');
});

Scenario('[TC-AH-29] Bỏ trống tên', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=email]', 'test29@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.seeInCurrentUrl('login.php');
});

Scenario('[TC-AH-30] Kiểm tra Tên vượt quá 255 ký tự', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A'.repeat(256));
    I.fillField('input[name=email]', 'test30@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Tên không được vượt quá 255 ký tự');
});

Scenario('[TC-AH-31] Bỏ trống Họ', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test31@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.seeInCurrentUrl('login.php');
});

Scenario('[TC-AH-32] Kiểm tra Họ vượt quá 255 ký tự', ({ I }) => {
    I.amOnPage('/login.php?mode=register');
    I.fillField('input[name=first_name]', 'Nguyen'.repeat(45));
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test32@test.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');
    I.click('Đăng ký', '.auth-action');
    I.see('Họ không được vượt quá 255 ký tự');
});
Scenario('[TC-AH-28] Tên có ký tự đặc biệt', ({ I }) => {
    I.amOnPage('/login.php?mode=register');

    I.fillField('input[name=first_name]', 'Nguyen');
    I.fillField('input[name=last_name]', 'A@');
    I.fillField('input[name=email]', 'test28@example.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');

    I.click('Đăng ký', '.auth-action');

    I.seeInCurrentUrl('login.php?mode=register');
    I.seeElement('.auth-alert-error');
    I.see('Tên không được chứa số hoặc ký tự đặc biệt!');
});


Scenario('[TC-AH-30] Tên vượt quá 255 ký tự', ({ I }) => {
    I.amOnPage('/login.php?mode=register');

    const longName = 'a'.repeat(256);

    I.fillField('input[name=first_name]', 'Nguyen');
    I.executeScript((value) => {
    const input = document.querySelector('input[name="last_name"]');
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
}, longName);    
    I.fillField('input[name=email]', 'test30@example.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');

    I.click('Đăng ký', '.auth-action');

    I.seeInCurrentUrl('login.php?mode=register');
    I.seeElement('.auth-alert-error');
    I.see('Tên không được vượt quá 255 ký tự!');
});


Scenario('[TC-AH-32] Họ vượt quá 255 ký tự', ({ I }) => {
    I.amOnPage('/login.php?mode=register');

    const longName = 'a'.repeat(256);

    I.fillField('input[name=first_name]', longName);
    I.fillField('input[name=last_name]', 'A');
    I.fillField('input[name=email]', 'test32@example.com');
    I.fillField('input[name=phone]', '0901234567');
    I.fillField('input[name=password]', '123456');
    I.fillField('input[name=confirm_password]', '123456');

    I.click('Đăng ký', '.auth-action');

    I.seeInCurrentUrl('login.php?mode=register');
    I.seeElement('.auth-alert-error');
    I.see('Họ không được vượt quá 255 ký tự!');
});
Feature('Kiểm thử BVA - Độ dài mã Voucher');

const TEST_EMAIL = 'user@example.com';
const TEST_PASSWORD = 'password';

const BOOKING_URL = '/booking.php?showtime_id=1';


// ==========================================
// ĐĂNG NHẬP
// ==========================================

const login = (I) => {

    I.amOnPage('/login.php');

    I.waitForElement('#login_email', 10);

    I.fillField('#login_email', TEST_EMAIL);

    I.fillField('#login_password', TEST_PASSWORD);

    I.click('.sign-in .auth-action');

    I.wait(2);
};


// ==========================================
// CHỌN GHẾ
// ==========================================

const selectSeat = (I) => {

    I.amOnPage(BOOKING_URL);

    I.waitForElement(
        'button.seat.available:not([disabled])',
        10
    );

    I.click(
        'button.seat.available:not([disabled])'
    );

    I.wait(1);
};


// ==========================================
// TẠO CHUỖI VOUCHER
// ==========================================

const generateVoucher = (length) => {

    return 'A'.repeat(length);
};


// ==========================================
// TC-TC-16
// Độ dài = 1
// Mong đợi: Không hợp lệ
// ==========================================

Scenario(
    'TC-TC-16 - Voucher có độ dài 1 ký tự',
    ({ I }) => {

        login(I);
        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.fillField(
            '#voucher-code',
            generateVoucher(1)
        );

        I.click('#btn-apply-voucher');

        I.waitForText(
            'Mã voucher phải có từ 2 đến 50 ký tự.',
            5,
            '#voucher-message'
        );
    }
);


// ==========================================
// TC-TC-17
// Độ dài = 2
// Mong đợi: Được xử lý bình thường
// ==========================================

Scenario(
    'TC-TC-17 - Voucher có độ dài 2 ký tự',
    ({ I }) => {

        login(I);
        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.fillField(
            '#voucher-code',
            generateVoucher(2)
        );

        I.click('#btn-apply-voucher');

        I.waitForElement('#voucher-message', 5);

        I.dontSee(
            'Mã voucher phải có từ 2 đến 50 ký tự.',
            '#voucher-message'
        );
    }
);


// ==========================================
// TC-TC-18
// Độ dài = 49
// ==========================================

Scenario(
    'TC-TC-18 - Voucher có độ dài 49 ký tự',
    ({ I }) => {

        login(I);
        selectSeat(I);

        I.fillField(
            '#voucher-code',
            generateVoucher(49)
        );

        I.click('#btn-apply-voucher');

        I.waitForElement('#voucher-message', 5);

        I.dontSee(
            'Mã voucher phải có từ 2 đến 50 ký tự.',
            '#voucher-message'
        );
    }
);


// ==========================================
// TC-TC-19
// Độ dài = 50
// ==========================================

Scenario(
    'TC-TC-19 - Voucher có độ dài 50 ký tự',
    ({ I }) => {

        login(I);
        selectSeat(I);

        I.fillField(
            '#voucher-code',
            generateVoucher(50)
        );

        I.click('#btn-apply-voucher');

        I.waitForElement('#voucher-message', 5);

        I.dontSee(
            'Mã voucher phải có từ 2 đến 50 ký tự.',
            '#voucher-message'
        );
    }
);


// ==========================================
// TC-TC-20
// Độ dài = 51
// Mong đợi: Không hợp lệ
// ==========================================

Scenario(
    'TC-TC-20 - Voucher có độ dài 51 ký tự',
    ({ I }) => {

        login(I);
        selectSeat(I);

        I.fillField(
            '#voucher-code',
            generateVoucher(51)
        );

        I.click('#btn-apply-voucher');

        I.waitForText(
            'Mã voucher phải có từ 2 đến 50 ký tự.',
            5,
            '#voucher-message'
        );
    }
);
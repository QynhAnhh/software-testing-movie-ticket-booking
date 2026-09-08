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
// HÀM TẠO CHUỖI VOUCHER
// ==========================================

const generateVoucher = (length) => {

    return 'A'.repeat(length);
};


// ==========================================
// TC-TC-16
// Độ dài mã Voucher = 1 ký tự
// ==========================================

Scenario(
    'TC-TC-16 - Voucher có độ dài 1 ký tự',
    ({ I }) => {

        login(I);

        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.scrollTo('#voucher-code');

        I.fillField(
            '#voucher-code',
            generateVoucher(1)
        );

        I.click('#btn-apply-voucher');

        I.wait(2);
    }
);


// ==========================================
// TC-TC-17
// Độ dài mã Voucher = 2 ký tự
// ==========================================

Scenario(
    'TC-TC-17 - Voucher có độ dài 2 ký tự',
    ({ I }) => {

        login(I);

        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.scrollTo('#voucher-code');

        I.fillField(
            '#voucher-code',
            generateVoucher(2)
        );

        I.click('#btn-apply-voucher');

        I.wait(2);
    }
);


// ==========================================
// TC-TC-18
// Độ dài Voucher = 49 ký tự
// ==========================================

Scenario(
    'TC-TC-18 - Voucher có độ dài 49 ký tự',
    ({ I }) => {

        login(I);

        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.scrollTo('#voucher-code');

        I.fillField(
            '#voucher-code',
            generateVoucher(49)
        );

        I.click('#btn-apply-voucher');

        I.wait(2);
    }
);


// ==========================================
// TC-TC-19
// Độ dài Voucher = 50 ký tự
// ==========================================

Scenario(
    'TC-TC-19 - Voucher có độ dài 50 ký tự',
    ({ I }) => {

        login(I);

        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.scrollTo('#voucher-code');

        I.fillField(
            '#voucher-code',
            generateVoucher(50)
        );

        I.click('#btn-apply-voucher');

        I.wait(2);
    }
);


// ==========================================
// TC-TC-20
// Độ dài Voucher = 51 ký tự
// ==========================================

Scenario(
    'TC-TC-20 - Voucher có độ dài 51 ký tự',
    ({ I }) => {

        login(I);

        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.scrollTo('#voucher-code');

        I.fillField(
            '#voucher-code',
            generateVoucher(51)
        );

        I.click('#btn-apply-voucher');

        I.wait(2);
    }
);
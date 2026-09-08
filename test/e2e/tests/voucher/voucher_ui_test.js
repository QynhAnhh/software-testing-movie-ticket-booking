const assert = require('assert');

Feature('Kiểm thử Voucher UI - TC-UI');

// =====================================================
// CẤU HÌNH TÀI KHOẢN TEST
// =====================================================

const TEST_EMAIL = 'user@example.com';
const TEST_PASSWORD = 'password';

const BOOKING_URL = '/booking.php?showtime_id=1';


// =====================================================
// HÀM ĐĂNG NHẬP
// =====================================================

const login = (I) => {

    I.amOnPage('/login.php');

    I.waitForElement('#login_email', 10);

    I.fillField('#login_email', TEST_EMAIL);

    I.fillField('#login_password', TEST_PASSWORD);

    I.click('.sign-in .auth-action');

    I.wait(2);
};


// =====================================================
// HÀM CHỌN GHẾ
// =====================================================

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


// =====================================================
// TC-UI-01
// ÁP DỤNG VOUCHER THÀNH CÔNG
// =====================================================

Scenario(
    'TC-UI-01 - Đăng nhập và áp dụng VIP20 thành công',
    async ({ I }) => {

        login(I);

        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        // Cuộn xuống Voucher
        I.scrollTo('#voucher-code');

        I.fillField('#voucher-code', 'VIP20');

        I.click('#btn-apply-voucher');

        I.waitForText(
            'Áp dụng voucher thành công!',
            10,
            '#voucher-message'
        );

        const discount = await I.grabTextFrom(
            '#discount-price'
        );

        console.log('Discount:', discount);

        assert.notStrictEqual(
            discount.trim(),
            '0đ'
        );

        // Giữ màn hình để quan sát
        I.wait(3);
    }
);


// =====================================================
// TC-UI-02
// VOUCHER KHÔNG TỒN TẠI
// =====================================================

Scenario(
    'TC-UI-02 - Voucher không tồn tại',
    ({ I }) => {

        login(I);

        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.scrollTo('#voucher-code');

        I.fillField(
            '#voucher-code',
            'INVALID999'
        );

        I.click('#btn-apply-voucher');

        I.waitForText(
            'Voucher not found.',
            10,
            '#voucher-message'
        );

        I.wait(2);
    }
);


// =====================================================
// TC-UI-03
// CHƯA CHỌN GHẾ
// =====================================================

Scenario(
    'TC-UI-03 - Không áp dụng Voucher khi chưa chọn ghế',
    ({ I }) => {

        login(I);

        I.amOnPage(BOOKING_URL);

        I.waitForElement('#voucher-code', 10);

        I.scrollTo('#voucher-code');

        I.fillField(
            '#voucher-code',
            'VIP20'
        );

        I.click('#btn-apply-voucher');

        I.waitForText(
            'Vui lòng chọn ghế trước khi áp dụng voucher.',
            10,
            '#voucher-message'
        );

        I.wait(2);
    }
);


// =====================================================
// TC-UI-04
// KHÔNG NHẬP MÃ VOUCHER
// =====================================================

Scenario(
    'TC-UI-04 - Không nhập mã Voucher',
    ({ I }) => {

        login(I);

        selectSeat(I);

        I.waitForElement('#voucher-code', 10);

        I.scrollTo('#voucher-code');

        I.click('#btn-apply-voucher');

        I.waitForText(
            'Vui lòng nhập mã voucher.',
            10,
            '#voucher-message'
        );

        I.wait(2);
    }
);
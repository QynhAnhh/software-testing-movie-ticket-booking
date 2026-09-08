const assert = require('assert');

Feature('Kiểm thử Voucher API - TC-TC');

const API = '/check_voucher.php';


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

async function applyVoucher(I, voucher_code, subtotal, user_id = 0) {
    return await I.sendPostRequest(API, {
        voucher_code,
        subtotal,
        user_id
    });
}


/* =========================================================
   ASSERT RESPONSE
========================================================= */

function expectSuccess(response) {

    assert.strictEqual(
        response.status,
        200,
        `Expected HTTP 200 but received ${response.status}`
    );

    assert.strictEqual(
        response.data.status,
        'success',
        `Expected status success but received ${response.data.status}`
    );
}


function expectError(response) {

    assert.strictEqual(
        response.status,
        400,
        `Expected HTTP 400 but received ${response.status}`
    );

    assert.strictEqual(
        response.data.status,
        'error',
        `Expected status error but received ${response.data.status}`
    );
}


function expectErrorMessage(response, message) {

    expectError(response);

    assert.strictEqual(
        response.data.message,
        message,
        `Expected message "${message}" but received "${response.data.message}"`
    );
}


/* =========================================================
   DECISION TABLE
   TC-TC-01 → TC-TC-08
========================================================= */


/**
 * TC-TC-01
 * VIP1 hợp lệ
 */
Scenario(
    'TC-TC-01 - Áp dụng VIP1 thành công',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'VIP1',
            80000
        );

        expectSuccess(response);

        assert.strictEqual(
            response.data.data.discount,
            20000
        );

        assert.strictEqual(
            response.data.data.final_amount,
            60000
        );
    }
);


/**
 * TC-TC-02
 * Giá trị đơn hàng nhỏ hơn mức tối thiểu
 */
Scenario(
    'TC-TC-02 - VIP1 không đạt giá trị đơn hàng tối thiểu',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'VIP1',
            79999
        );

        expectError(response);
    }
);


/**
 * TC-TC-03
 * Giá trị đơn hàng đúng bằng mức tối thiểu
 */
Scenario(
    'TC-TC-03 - VIP1 đúng bằng giá trị đơn hàng tối thiểu',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'VIP1',
            80000
        );

        expectSuccess(response);

        assert.strictEqual(
            response.data.data.discount,
            20000
        );

        assert.strictEqual(
            response.data.data.final_amount,
            60000
        );
    }
);


/**
 * TC-TC-04
 * VIP20 hợp lệ
 */
Scenario(
    'TC-TC-04 - Áp dụng VIP20 thành công',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'VIP20',
            10000
        );

        expectSuccess(response);

        assert.strictEqual(
            response.data.data.discount,
            10000
        );

        assert.strictEqual(
            response.data.data.final_amount,
            0
        );
    }
);


/**
 * TC-TC-05
 * FREE100 hợp lệ
 */
Scenario(
    'TC-TC-05 - Áp dụng FREE100 thành công',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'FREE100',
            100000
        );

        expectSuccess(response);

        assert.strictEqual(
            response.data.data.discount,
            100000
        );

        assert.strictEqual(
            response.data.data.final_amount,
            0
        );
    }
);


/**
 * TC-TC-06
 * BIG500 hợp lệ
 */
Scenario(
    'TC-TC-06 - Áp dụng BIG500 thành công',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'BIG500',
            1000000
        );

        expectSuccess(response);

        assert.strictEqual(
            response.data.data.discount,
            500000
        );

        assert.strictEqual(
            response.data.data.final_amount,
            500000
        );
    }
);


/**
 * TC-TC-07
 * Voucher không tồn tại
 */
Scenario(
    'TC-TC-07 - Voucher không tồn tại',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'NOTFOUND999',
            500000
        );

        expectErrorMessage(
            response,
            'Voucher not found.'
        );
    }
);


/**
 * TC-TC-08
 * Voucher hết hạn
 */
Scenario(
    'TC-TC-08 - OLD50 đã hết hạn',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'OLD50',
            100000
        );

        expectErrorMessage(
            response,
            'Voucher has expired.'
        );
    }
);


/* =========================================================
   ERROR GUESSING
   TC-TC-09 → TC-TC-15
========================================================= */


/**
 * TC-TC-09
 * Voucher VIP2 bị từ chối
 */
Scenario(
    'TC-TC-09 - VIP2 bị từ chối',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'VIP2',
            120000
        );

        expectError(response);
    }
);


/**
 * TC-TC-10
 * Voucher đạt giới hạn sử dụng
 */
Scenario(
    'TC-TC-10 - USED50 đã đạt giới hạn sử dụng',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'USED50',
            80000
        );

        expectErrorMessage(
            response,
            'Voucher has reached its maximum usage limit.'
        );
    }
);


/**
 * TC-TC-11
 * Voucher để trống
 */
Scenario(
    'TC-TC-11 - Mã voucher để trống',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            '',
            500000
        );

        expectError(response);
    }
);


/**
 * TC-TC-12
 * Voucher chỉ chứa khoảng trắng
 */
Scenario(
    'TC-TC-12 - Voucher chỉ chứa khoảng trắng',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            '     ',
            500000
        );

        expectError(response);
    }
);


/**
 * TC-TC-13
 * Voucher viết thường
 */
Scenario(
    'TC-TC-13 - Voucher viết thường',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'vip1',
            80000
        );

        expectError(response);
    }
);


/**
 * TC-TC-14
 * Voucher chứa ký tự đặc biệt
 */
Scenario(
    'TC-TC-14 - Voucher chứa ký tự đặc biệt',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'VIP@1!',
            80000
        );

        expectError(response);
    }
);


/**
 * TC-TC-15
 * Voucher có khoảng trắng đầu/cuối
 */
Scenario(
    'TC-TC-15 - Voucher có khoảng trắng đầu/cuối',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            ' VIP1 ',
            80000
        );

        expectError(response);
    }
);


/* =========================================================
   BOUNDARY VALUE ANALYSIS
   TC-TC-16 → TC-TC-20
========================================================= */


/**
 * TC-TC-16
 * Voucher dài 1 ký tự
 */
Scenario(
    'TC-TC-16 - Voucher có độ dài 1 ký tự',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'A',
            500000
        );

        expectError(response);
    }
);


/**
 * TC-TC-17
 * Voucher dài 2 ký tự
 */
Scenario(
    'TC-TC-17 - Voucher có độ dài 2 ký tự',
    async ({ I }) => {

        const response = await applyVoucher(
            I,
            'AB',
            500000
        );

        expectError(response);
    }
);


/**
 * TC-TC-18
 * Voucher dài 49 ký tự
 */
Scenario(
    'TC-TC-18 - Voucher có độ dài 49 ký tự',
    async ({ I }) => {

        const voucherCode = 'A'.repeat(49);

        const response = await applyVoucher(
            I,
            voucherCode,
            500000
        );

        expectError(response);
    }
);


/**
 * TC-TC-19
 * Voucher dài đúng 50 ký tự
 */
Scenario(
    'TC-TC-19 - Voucher có độ dài đúng 50 ký tự',
    async ({ I }) => {

        const voucherCode = 'A'.repeat(50);

        const response = await applyVoucher(
            I,
            voucherCode,
            500000
        );

        expectError(response);
    }
);


/**
 * TC-TC-20
 * Voucher dài 51 ký tự
 */
Scenario(
    'TC-TC-20 - Voucher có độ dài 51 ký tự',
    async ({ I }) => {

        const voucherCode = 'A'.repeat(51);

        const response = await applyVoucher(
            I,
            voucherCode,
            500000
        );

        expectError(response);
    }
);
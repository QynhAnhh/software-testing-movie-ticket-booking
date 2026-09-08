Feature('Debug Voucher API');

Scenario('Kiểm tra các Voucher thật trong database', async ({ I }) => {

  const vouchers = [
    {
      code: 'VIP1',
      subtotal: 80000
    },
    {
      code: 'VIP20',
      subtotal: 10000
    },
    {
      code: 'FREE100',
      subtotal: 100000
    },
    {
      code: 'BIG500',
      subtotal: 1000000
    },
    {
      code: 'VIP2',
      subtotal: 120000
    },
    {
      code: 'OLD50',
      subtotal: 100000
    },
    {
      code: 'USED50',
      subtotal: 80000
    }
  ];

  for (const voucher of vouchers) {

    const response = await I.sendPostRequest(
      '/check_voucher.php',
      {
        voucher_code: voucher.code,
        subtotal: voucher.subtotal,
        user_id: 0
      }
    );

    console.log('\n================================');
    console.log('VOUCHER:', voucher.code);
    console.log('SUBTOTAL:', voucher.subtotal);
    console.log('HTTP STATUS:', response.status);
    console.log('DATA:', response.data);
    console.log('================================\n');
  }

});
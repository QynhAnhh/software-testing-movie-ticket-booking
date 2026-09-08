Feature('Kiểm thử tính năng Voucher');

Scenario('Áp dụng mã voucher thành công', ({ I }) => {
  // Đường dẫn sẽ ghép với URL gốc thành: .../frontend/booking.php
  I.amOnPage('/booking.php'); // Đổi thành file .php thực tế chứa ô nhập voucher

  // Đợi ô nhập voucher xuất hiện (tối đa 5s)
  I.waitForElement('input[name="voucher"]', 5); 
  
  // Điền mã và bấm nút
  I.fillField('input[name="voucher"]', 'DISCOUNT20');
  I.click('Áp dụng'); // Hoặc đổi thành selector nút: '.btn-apply'

  // Kiểm tra kết quả hiển thị
  I.see('Áp dụng thành công');
});
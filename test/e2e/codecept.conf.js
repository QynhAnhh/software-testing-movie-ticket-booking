exports.config = {
  tests: './tests/**/*_test.js',
  output: './tests/output',
  helpers: {
    Playwright: {
      // 1. Thêm /frontend nếu giao diện trang web nằm trong thư mục frontend
      url: 'http://localhost/software-testing-movie-ticket-booking/frontend',
      // 2. Chuyển show thành true để hiển thị trình duyệt khi test
      show: true,
      browser: 'chromium',
      video: true,
      keepVideoForPassedTests: true,
      // 3. Tăng thời gian chờ mặc định lên 5 giây để đợi trang tải xong
      waitForTimeout: 5000
    }
  },
  include: {},
  name: 'software-testing-movie-ticket-booking'
}
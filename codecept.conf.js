exports.config = {
  tests: './tests/e2e/**/*_test.js',
  output: './tests/e2e/output',
  helpers: {
    Playwright: {
      url: 'http://localhost/software-testing-movie-ticket-booking',
      show: true,
      browser: 'chromium',
      video: true,
      keepVideoForPassedTests: true
    }
  },
  include: {},
  name: 'software-testing-movie-ticket-booking'
}

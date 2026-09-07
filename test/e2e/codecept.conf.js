exports.config = {
  tests: './tests/**/*_test.js',
  output: './tests/output',
  helpers: {
    Playwright: {
      url: 'http://localhost/software-testing-movie-ticket-booking',
      show: false,
      browser: 'chromium',
      video: true,
      keepVideoForPassedTests: true
    }
  },
  include: {},
  name: 'software-testing-movie-ticket-booking'
}

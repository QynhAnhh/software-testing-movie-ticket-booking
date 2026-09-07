exports.config = {
  tests: './tests/**/*_test.js',
  output: './output',

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
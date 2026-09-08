exports.config = {

  tests: './tests/**/*_test.js',

  output: './tests/output',

  helpers: {

    Playwright: {
      url: 'http://localhost/software-testing-movie-ticket-booking/frontend',

      show: true,

      browser: 'chromium',

      waitForNavigation: 'networkidle'
    },

    REST: {
      endpoint:
        'http://localhost/software-testing-movie-ticket-booking/backend/api',

      defaultHeaders: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    }
  },

  name: 'software-testing-movie-ticket-booking'
};

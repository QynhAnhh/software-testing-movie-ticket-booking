export const config: CodeceptJS.MainConfig = {
  tests: './*_test.ts',
  output: './output',
  helpers: {
    Playwright: {
      browser: 'chromium',
      url: 'http://localhost/software-testing-movie-ticket-booking',
      show: true
    }
  },
  include: {
    I: './steps_file.ts'
  },
  noGlobals: true,
  plugins: {},
  name: 'software-testing-movie-ticket-booking',
  require: ['tsx/esm']
}
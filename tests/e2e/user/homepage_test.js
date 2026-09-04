Feature('User - Homepage');

Scenario('Xem danh sách phim đang chiếu', ({ I }) => {
    I.amOnPage('/');
    I.see('Phim Đang Chiếu');
    I.seeElement('.movie-card');
    I.see('CHI TIẾT', '.btn-get-ticket');
});

Scenario('Kiểm tra phim sắp chiếu', ({ I }) => {
    I.amOnPage('/');
    I.see('Phim Sắp Chiếu');
    // Phim sắp chiếu thường sẽ có nút TRAILER thay vì CHI TIẾT
    I.seeElement('.movie-card');
});

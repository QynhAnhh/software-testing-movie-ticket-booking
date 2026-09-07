<?php

namespace Tests\Acceptance;

use Tests\Support\AcceptanceTester;

class KAN83MovieShowtimeCest
{
    public function _before(AcceptanceTester $I): void
    {
        $email = getenv('KAN83_ADMIN_EMAIL');
        $password = getenv('KAN83_ADMIN_PASSWORD');

        $I->amOnUrl(
            'http://localhost/software-testing-movie-ticket-booking/frontend/login.php'
        );

        $I->fillField('#login_email', $email);
        $I->fillField('#login_password', $password);
        $I->click('.sign-in button[type="submit"]');
    }

    public function movieManagementPageLoads(AcceptanceTester $I): void
    {
        $I->amOnUrl(
            'http://localhost/software-testing-movie-ticket-booking/backend/admin/manage_movies.php'
        );

        $I->seeResponseCodeIs(200);
        $I->see('Quản lý Phim');
        $I->see('Tên phim');
        $I->see('Thời lượng');
        $I->see('Ngày khởi chiếu');
    }

    public function showtimeManagementPageLoads(AcceptanceTester $I): void
    {
        $I->amOnUrl(
            'http://localhost/software-testing-movie-ticket-booking/backend/admin/manage_showtimes.php'
        );

        $I->seeResponseCodeIs(200);
        $I->see('Quản lý Lịch chiếu');
    }

    public function movieRejectsNegativeDuration(AcceptanceTester $I): void
    {
        $I->amOnUrl(
            'http://localhost/software-testing-movie-ticket-booking/backend/admin/manage_movies.php'
        );

        $I->submitForm('form[action="manage_movies.php"]', [
            'action' => 'add',
            'title' => 'KAN83 Negative Duration',
            'country' => 'Vietnam',
            'duration' => -120,
            'screening_date' => '2026-12-20',
            'description' => 'Codeception KAN-83'
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeInSource('Vui lòng nhập thời lượng phim hợp lệ!');
    }

    public function movieRejectsZeroDuration(AcceptanceTester $I): void
    {
        $I->amOnUrl(
            'http://localhost/software-testing-movie-ticket-booking/backend/admin/manage_movies.php'
        );

        $I->submitForm('form[action="manage_movies.php"]', [
            'action' => 'add',
            'title' => 'KAN83 Zero Duration',
            'country' => 'Vietnam',
            'duration' => 0,
            'screening_date' => '2026-12-20',
            'description' => 'Codeception KAN-83'
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeInSource('Vui lòng nhập thời lượng phim hợp lệ!');
    }

    public function movieRejectsDescriptionOver5000Characters(AcceptanceTester $I): void
    {
        $I->amOnUrl(
            'http://localhost/software-testing-movie-ticket-booking/backend/admin/manage_movies.php'
        );

        $I->submitForm('form[action="manage_movies.php"]', [
            'action' => 'add',
            'title' => 'KAN83 Long Description',
            'country' => 'Vietnam',
            'duration' => 120,
            'screening_date' => '2026-12-20',
            'description' => str_repeat('A', 5001)
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeInSource('Mô tả vượt quá giới hạn 5000 ký tự');
    }
    public function showtimeRejectsPastDate(AcceptanceTester $I): void
{
    $I->amOnUrl(
        'http://localhost/software-testing-movie-ticket-booking/backend/admin/manage_showtimes.php'
    );

    $I->submitForm('form[action="manage_showtimes.php"]', [
        'action' => 'add',
        'movie_id' => 2,
        'room_id' => 1,
        'show_date' => '2025-01-01',
        'start_time' => '10:00',
        'base_price' => 80000,
        'status' => 'active'
    ]);

    $I->seeResponseCodeIs(200);
    $I->see('Suất chiếu không thể ở trong quá khứ');
}

public function showtimeRejectsMissingStartTime(AcceptanceTester $I): void
{
    $I->amOnUrl(
        'http://localhost/software-testing-movie-ticket-booking/backend/admin/manage_showtimes.php'
    );

    $I->submitForm('form[action="manage_showtimes.php"]', [
        'action' => 'add',
        'movie_id' => 2,
        'room_id' => 1,
        'show_date' => '2026-12-20',
        'start_time' => '',
        'base_price' => 80000,
        'status' => 'active'
    ]);

    $I->seeResponseCodeIs(200);
    $I->see('Giờ bắt đầu không được để trống!');
}
public function showtimeRejectsInvalidStartTime(AcceptanceTester $I): void
{
    $I->amOnUrl(
        'http://localhost/software-testing-movie-ticket-booking/backend/admin/manage_showtimes.php'
    );

    $I->submitForm('form[action="manage_showtimes.php"]', [
        'action' => 'add',
        'movie_id' => 2,
        'room_id' => 1,
        'show_date' => '2026-12-20',
        'start_time' => '25:30',
        'base_price' => 80000,
        'status' => 'active'
    ]);

    $I->seeResponseCodeIs(200);
    $I->see('Giờ bắt đầu không hợp lệ!');
}
}
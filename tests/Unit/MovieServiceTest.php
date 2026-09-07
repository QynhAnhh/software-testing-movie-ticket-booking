<?php

namespace Tests\Unit;

use App\Services\MovieService;
use Tests\Support\UnitTester;

class MovieServiceTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;
    private MovieService $service;

    protected function _before(): void
    {
        $this->service = new MovieService();
    }

    private function validMovieData(): array
    {
        return [
            'title' => 'KAN-83 Test Movie',
            'description' => 'Valid description',
            'director' => 'Test Director',
            'cast' => 'Test Cast',
            'age_restriction' => 13,
            'country' => 'Vietnam',
            'duration' => 120,
            'screening_date' => '2026-12-20',
            'trailer_url' => '',
            'status' => 'coming'
        ];
    }

    public function testRejectsZeroDuration(): void
    {
        $data = $this->validMovieData();
        $data['duration'] = 0;

        $result = $this->service->addMovie($data, []);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Vui lòng nhập thời lượng phim hợp lệ!',
            $result['message']
        );
    }

    public function testRejectsInvalidCalendarDate(): void
    {
        $data = $this->validMovieData();
        $data['screening_date'] = '2026-02-30';

        $result = $this->service->addMovie($data, []);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Ngày khởi chiếu không hợp lệ!',
            $result['message']
        );
    }

    public function testRejectsDescriptionOver5000Characters(): void
    {
        $data = $this->validMovieData();
        $data['description'] = str_repeat('A', 5001);

        $result = $this->service->addMovie($data, []);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Mô tả vượt quá giới hạn 5000 ký tự',
            $result['message']
        );
    }

    public function testRejectsInvalidPosterExtension(): void
    {
        $data = $this->validMovieData();

        $poster = [
            'name' => 'poster.gif',
            'tmp_name' => __FILE__,
            'size' => 1000,
            'error' => UPLOAD_ERR_OK
        ];

        $result = $this->service->addMovie($data, [], $poster);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Poster chỉ chấp nhận file JPG hoặc PNG!',
            $result['message']
        );
    }

    public function testRejectsPosterOver5MB(): void
    {
        $data = $this->validMovieData();

        $poster = [
            'name' => 'poster.jpg',
            'tmp_name' => __FILE__,
            'size' => (5 * 1024 * 1024) + 1,
            'error' => UPLOAD_ERR_OK
        ];

        $result = $this->service->addMovie($data, [], $poster);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Dung lượng poster không được vượt quá 5MB!',
            $result['message']
        );
    }

    public function testRejectsPhpUploadError(): void
    {
        $data = $this->validMovieData();

        $poster = [
            'name' => 'poster.jpg',
            'tmp_name' => '',
            'size' => 0,
            'error' => UPLOAD_ERR_PARTIAL
        ];

        $result = $this->service->addMovie($data, [], $poster);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Upload poster không thành công. Vui lòng thử lại!',
            $result['message']
        );
    }

    public function testRejectsInvalidDeleteMovieId(): void
    {
        $result = $this->service->deleteMovie(0);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'ID phim không hợp lệ!',
            $result['message']
        );
    }
    public function testRejectsMissingRequiredFields(): void
{
    $data = $this->validMovieData();
    $data['title'] = '';

    $result = $this->service->addMovie($data, []);

    $this->assertSame('error', $result['status']);
    $this->assertSame(
        'Vui lòng nhập đầy đủ các trường bắt buộc!',
        $result['message']
    );
}

public function testRejectsPosterPhpSizeError(): void
{
    $data = $this->validMovieData();

    $poster = [
        'name' => 'poster.jpg',
        'tmp_name' => '',
        'size' => 0,
        'error' => UPLOAD_ERR_INI_SIZE
    ];

    $result = $this->service->addMovie($data, [], $poster);

    $this->assertSame('error', $result['status']);
    $this->assertSame(
        'Dung lượng poster không được vượt quá 5MB!',
        $result['message']
    );
}

public function testRejectsInvalidUpdateData(): void
{
    $data = $this->validMovieData();

    $result = $this->service->updateMovie(0, $data, []);

    $this->assertSame('error', $result['status']);
    $this->assertSame(
        'Dữ liệu cập nhật không hợp lệ!',
        $result['message']
    );
}

public function testRejectsInvalidDateOnUpdate(): void
{
    $data = $this->validMovieData();
    $data['screening_date'] = '2026-02-30';

    $result = $this->service->updateMovie(1, $data, []);

    $this->assertSame('error', $result['status']);
    $this->assertSame(
        'Ngày khởi chiếu không hợp lệ!',
        $result['message']
    );
}
}

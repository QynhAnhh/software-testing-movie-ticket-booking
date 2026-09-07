<?php

namespace Tests\Unit;

use App\Services\ShowtimeService;
use Tests\Support\UnitTester;

class ShowtimeServiceTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;
    private ShowtimeService $service;

    protected function _before(): void
    {
        $this->service = new ShowtimeService();
    }

    private function validData(): array
    {
        return [
            'movie_id' => 2,
            'room_id' => 1,
            'show_date' => '2099-12-20',
            'start_time' => '20:00',
            'base_price' => 100000,
            'status' => 'active'
        ];
    }

    public function testRejectsInvalidMovieId(): void
    {
        $data = $this->validData();
        $data['movie_id'] = 0;

        $result = $this->service->addShowtime($data);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Phim không hợp lệ!', $result['message']);
    }

    public function testRejectsInvalidRoomId(): void
    {
        $data = $this->validData();
        $data['room_id'] = 0;

        $result = $this->service->addShowtime($data);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Phòng chiếu không hợp lệ!', $result['message']);
    }

    public function testRejectsEmptyShowDate(): void
    {
        $data = $this->validData();
        $data['show_date'] = '';

        $result = $this->service->addShowtime($data);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Ngày chiếu không được để trống!',
            $result['message']
        );
    }

    public function testRejectsEmptyStartTime(): void
    {
        $data = $this->validData();
        $data['start_time'] = '';

        $result = $this->service->addShowtime($data);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Giờ bắt đầu không được để trống!',
            $result['message']
        );
    }

    public function testRejectsInvalidTime2530(): void
    {
        $data = $this->validData();
        $data['start_time'] = '25:30';

        $result = $this->service->addShowtime($data);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Giờ bắt đầu không hợp lệ!',
            $result['message']
        );
    }

    public function testRejectsPastShowtime(): void
    {
        $data = $this->validData();
        $data['show_date'] = '2020-01-01';
        $data['start_time'] = '10:00';

        $result = $this->service->addShowtime($data);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Suất chiếu không thể ở trong quá khứ',
            $result['message']
        );
    }

    public function testRejectsInvalidDeleteShowtimeId(): void
    {
        $result = $this->service->deleteShowtime(0);

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'ID suất chiếu không hợp lệ!',
            $result['message']
        );
    }

    public function testRejectsInvalidUpdateShowtimeId(): void
    {
        $result = $this->service->updateShowtime(0, $this->validData());

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'ID suất chiếu không hợp lệ!',
            $result['message']
        );
    }
    public function testGetShowtimeDetailRejectsInvalidId(): void
{
    $result = $this->service->getShowtimeDetail(0);

    $this->assertNull($result);
}

public function testGetShowtimesByMovieIdRejectsInvalidId(): void
{
    $result = $this->service->getShowtimesByMovieId(0);

    $this->assertSame([], $result);
}
public function testRejectsDeleteBookedShowtime(): void
{
    $result = $this->service->deleteShowtime(340);

    $this->assertSame('error', $result['status']);
    $this->assertSame(
        'Không thể xóa suất chiếu đã có vé được đặt',
        $result['message']
    );
}
public function testRejectsUpdateWhenRoomCapacityIsLessThanBookedTickets(): void
{
    $data = $this->validData();
    $data['room_id'] = 5;

    $result = $this->service->updateShowtime(340, $data);

    $this->assertSame('error', $result['status']);
    $this->assertSame(
        'Sức chứa của phòng mới không đủ',
        $result['message']
    );
}

public function testRejectsShowtimeConflictWith15MinuteBuffer(): void
{
    $data = $this->validData();

    $data['movie_id'] = 2;
    $data['room_id'] = 5;
    $data['show_date'] = '2026-09-27';
    $data['start_time'] = '18:05';
    $data['base_price'] = 100000;
    $data['status'] = 'active';

    $result = $this->service->addShowtime($data);

    $this->assertSame('error', $result['status']);
    $this->assertSame(
        'Giữa hai suất chiếu phải nghỉ tối thiểu 15 phút',
        $result['message']
    );
}
}
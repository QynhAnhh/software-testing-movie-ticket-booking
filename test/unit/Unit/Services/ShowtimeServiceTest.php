<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ShowtimeService;
use App\Models\ShowtimeModel;
use App\Models\MovieModel;
use App\Models\RoomModel;
use App\Models\TicketModel;

class ShowtimeServiceTest extends TestCase
{
    private ShowtimeService $service;
    private $showtimeModel;
    private $movieModel;
    private $roomModel;
    private $ticketModel;

    protected function setUp(): void
{
    parent::setUp();

    // Mock ShowtimeModel để không tác động Database thật.
    $this->showtimeModel = $this->getMockBuilder(ShowtimeModel::class)
        ->disableOriginalConstructor()
        ->onlyMethods([
            'movieExists',
            'roomExists',
            'getMovieDuration',
            'findConflict',
            'insert',
            'findById',
            'update',
            'delete',
            'getError',
            'countBookedTickets',
            'getAllWithDetails',
'getDetailById',
'getByMovieId',
        ])
        ->getMock();

    // Mock MovieModel.
    $this->movieModel = $this->getMockBuilder(MovieModel::class)
        ->disableOriginalConstructor()
        ->onlyMethods([
            'getMovieByIdWithGenres',
            'getAllMovies'
        ])
        ->getMock();

    // Mock RoomModel.
    $this->roomModel = $this->getMockBuilder(RoomModel::class)
        ->disableOriginalConstructor()
        ->onlyMethods([
            'findById',
            'getAllRooms'
        ])
        ->getMock();

    // Mock TicketModel dùng để giả lập dữ liệu vé đã đặt.
    // Không inject vào ShowtimeService vì Service hiện tại
    // không có thuộc tính $ticketModel.
    $this->ticketModel = $this->getMockBuilder(TicketModel::class)
        ->disableOriginalConstructor()
        ->onlyMethods([
            'getBookedSeatIdsByShowtimeId',
        ])
        ->getMock();

    $this->service = new ShowtimeService(
    $this->showtimeModel,
    $this->movieModel,
    $this->roomModel
);
}

    protected function tearDown(): void
{
    unset(
        $this->service,
        $this->showtimeModel,
        $this->movieModel,
        $this->roomModel,
        $this->ticketModel
    );

    parent::tearDown();
}

    private function validShowtimeData(): array
    {
        return [
            'movie_id' => 1,
            'room_id' => 1,
            'show_date' => '2026-12-20',
            'start_time' => '10:00',
            'base_price' => 80000,
            'status' => 'active',
        ];
    }

    private function prepareValidDependencies(
    int $duration = 120,
    bool $conflict = false,
    bool $movieActive = true
): void {
    $this->showtimeModel
        ->method('movieExists')
        ->willReturn(true);

    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->willReturn([
            'id' => 1,
            'is_active' => $movieActive ? 1 : 0,
            'status' => $movieActive ? 'active' : 'inactive',
        ]);

    $this->showtimeModel
        ->method('roomExists')
        ->willReturn(true);

    $this->showtimeModel
        ->method('getMovieDuration')
        ->willReturn($duration);

    $this->showtimeModel
        ->method('findConflict')
        ->willReturn($conflict);

    $this->showtimeModel
        ->method('insert')
        ->willReturn(true);
}

    /**
     * @testdox TC-DT-11 - Chặn tạo suất chiếu trong quá khứ
     */
    public function testTcDt11RejectsPastShowtime(): void
    {
        $this->prepareValidDependencies();

        $data = $this->validShowtimeData();

        $data['show_date'] = '2020-01-01';
        $data['start_time'] = '10:00';

        $result = $this->service->addShowtime($data);

        $this->assertSame(
            'error',
            $result['status'],
            'TC-DT-11: Hệ thống phải chặn suất chiếu trong quá khứ.'
        );

        $this->assertStringContainsString(
            'quá khứ',
            mb_strtolower($result['message']),
            'TC-DT-11: Phải có thông báo liên quan đến thời gian trong quá khứ.'
        );
    }

    /**
     * @testdox TC-DT-12 - Chặn giờ bắt đầu sai định dạng 25:30
     */
    public function testTcDt12RejectsInvalidTime(): void
    {
        $this->prepareValidDependencies();

        $data = $this->validShowtimeData();
        $data['start_time'] = '25:30';

        $result = $this->service->addShowtime($data);

        $this->assertSame(
            'error',
            $result['status'],
            'TC-DT-12: Giờ 25:30 phải bị từ chối.'
        );

        $this->assertStringContainsString(
            'không hợp lệ',
            mb_strtolower($result['message']),
            'TC-DT-12: Phải báo định dạng giờ không hợp lệ.'
        );
    }

    /**
     * @testdox TC-DT-13 - Chặn suất chiếu trùng ngày, phòng và giờ bắt đầu
     */
    public function testTcDt13RejectsExactDuplicateShowtime(): void
    {
        $this->prepareValidDependencies(120, true);

        $data = $this->validShowtimeData();

        $result = $this->service->addShowtime($data);

        $this->assertSame(
            'error',
            $result['status'],
            'TC-DT-13: Phải chặn suất chiếu bị trùng.'
        );

        $this->assertStringContainsString(
    '15',
    $result['message']
);
    }

    /**
     * @testdox TC-DT-14 - Chặn hai suất chiếu cách nhau dưới 15 phút
     */
    public function testTcDt14RejectsShowtimesLessThan15MinutesApart(): void
    {
        /*
         * Giả lập:
         * Một suất trước kết thúc lúc 12:00,
         * suất mới bắt đầu lúc 12:05.
         *
         * findConflict hiện tại chỉ phát hiện cùng start_time,
         * nên mock trả false.
         */
        $this->prepareValidDependencies(115, true);

        $data = $this->validShowtimeData();
        $data['start_time'] = '12:05';

        $result = $this->service->addShowtime($data);

        $this->assertSame(
            'error',
            $result['status'],
            'TC-DT-14: Phải chặn hai suất chiếu cách nhau dưới 15 phút.'
        );

        $this->assertStringContainsString(
            '15',
            $result['message'],
            'TC-DT-14: Phải thông báo yêu cầu khoảng cách tối thiểu 15 phút.'
        );
    }

    /**
     * @testdox TC-DT-15 - Tự động tính giờ kết thúc chính xác
     */
    public function testTcDt15ComputesEndTimeCorrectly(): void
    {
        $this->prepareValidDependencies(120, false);

        $data = $this->validShowtimeData();
        $data['start_time'] = '10:00';

        $capturedData = null;

        $this->showtimeModel
            ->expects($this->once())
            ->method('insert')
            ->with($this->callback(function ($actualData) use (&$capturedData) {
                $capturedData = $actualData;
                return true;
            }))
            ->willReturn(true);

        $result = $this->service->addShowtime($data);

        $this->assertSame(
            'success',
            $result['status'],
            'TC-DT-15: Suất chiếu hợp lệ phải được xử lý thành công.'
        );

        $this->assertSame(
            '12:00:00',
            $capturedData['end_time'],
            'TC-DT-15: Phim 120 phút bắt đầu 10:00 phải kết thúc 12:00.'
        );
    }

    /**
 * @testdox TC-DT-16 - Chặn tạo suất chiếu cho phim đã bị vô hiệu hóa
 */
public function testTcDt16RejectsInactiveMovie(): void
{
    $data = $this->validShowtimeData();

    /*
     * Phim tồn tại nhưng đã bị vô hiệu hóa.
     * Service đúng phải đọc trạng thái phim trước khi tạo suất chiếu.
     */
    $this->showtimeModel
        ->method('movieExists')
        ->willReturn(true);

    $this->showtimeModel
        ->method('roomExists')
        ->willReturn(true);

    $this->showtimeModel
        ->method('getMovieDuration')
        ->willReturn(120);

    $this->showtimeModel
        ->method('findConflict')
        ->willReturn(true);

    $this->showtimeModel
        ->method('insert')
        ->willReturn(true);

    // Mong đợi Service phải kiểm tra chi tiết/trạng thái phim.
    $this->movieModel
        ->expects($this->once())
        ->method('getMovieByIdWithGenres')
        ->with($data['movie_id'])
        ->willReturn([
            'id' => $data['movie_id'],
            'is_active' => 0,
            'status' => 'inactive',
        ]);

    $result = $this->service->addShowtime($data);

    $this->assertSame(
        'error',
        $result['status'],
        'TC-DT-16: Phim bị vô hiệu hóa không được phép tạo suất chiếu.'
    );
}

    /**
     * @testdox TC-DT-18 - Chặn cập nhật suất chiếu gây xung đột thời gian
     */
    public function testTcDt18RejectsConflictingShowtimeUpdate(): void
    {
        $showtimeId = 50;

        $this->showtimeModel
            ->method('findById')
            ->willReturn([
                'id' => $showtimeId,
            ]);

        $this->showtimeModel
            ->method('movieExists')
            ->willReturn(true);

        $this->showtimeModel
            ->method('roomExists')
            ->willReturn(true);

        $this->showtimeModel
            ->method('getMovieDuration')
            ->willReturn(120);

        /*
         * Giả lập xung đột khoảng thời gian,
         * nhưng không trùng chính xác giờ bắt đầu.
         */
        $this->showtimeModel
            ->method('findConflict')
            ->willReturn(true);

        $this->showtimeModel
            ->method('update')
            ->willReturn(true);

        $data = $this->validShowtimeData();
        $data['start_time'] = '13:00';

        $result = $this->service->updateShowtime(
            $showtimeId,
            $data
        );

        $this->assertSame(
            'error',
            $result['status'],
            'TC-DT-18: Phải chặn cập nhật gây xung đột suất chiếu.'
        );

        $this->assertStringContainsString(
            'trùng',
            mb_strtolower($result['message']),
            'TC-DT-18: Phải thông báo xung đột/trùng lịch.'
        );
    }

    /**
     * @testdox TC-DT-19 - Chặn thêm suất chiếu khi thiếu giờ bắt đầu
     */
    public function testTcDt19RequiresStartTime(): void
    {
        $this->showtimeModel
            ->method('movieExists')
            ->willReturn(true);

        $this->showtimeModel
            ->method('roomExists')
            ->willReturn(true);

        $data = $this->validShowtimeData();
        $data['start_time'] = '';

        $result = $this->service->addShowtime($data);

        $this->assertSame(
            'error',
            $result['status'],
            'TC-DT-19: Phải từ chối khi thiếu giờ bắt đầu.'
        );

        $this->assertStringContainsString(
            'không được để trống',
            mb_strtolower($result['message'])
        );
    }

    /**
 * TC-DT-17
 * Kiểm tra hệ thống phải chặn đổi phòng chiếu
 * khi phòng mới có sức chứa nhỏ hơn số vé đã bán.
 *
 * @testdox TC-DT-17 - Chặn đổi sang phòng có sức chứa nhỏ hơn số vé đã bán
 */
public function testTcDt17PreventsChangingToRoomSmallerThanSoldTickets(): void
{
    $showtimeId = 201;

    /*
     * Giả lập:
     * Suất chiếu đã tồn tại.
     * Có 100 vé đã được đặt.
     * Người dùng cố đổi sang phòng mới chỉ có 50 ghế.
     *
     * Theo TC-DT-17, hệ thống phải từ chối cập nhật.
     */

    $this->showtimeModel
        ->method('findById')
        ->with($showtimeId)
        ->willReturn([
            'id' => $showtimeId,
            'movie_id' => 1,
            'room_id' => 1,
        ]);

    $this->showtimeModel
        ->method('movieExists')
        ->willReturn(true);

    $this->showtimeModel
        ->method('roomExists')
        ->willReturn(true);

    $this->showtimeModel
        ->method('getMovieDuration')
        ->willReturn(120);

    $this->showtimeModel
        ->method('findConflict')
        ->willReturn(false);

    // Giả lập 100 vé đã bán.
    $this->showtimeModel
        ->method('countBookedTickets')
        ->with($showtimeId)
        ->willReturn(100);

    /*
     * Phòng mới chỉ có 50 ghế.
     * Service đúng phải phát hiện 50 < 100 và chặn update.
     */
    $this->roomModel
        ->method('findById')
        ->with(2)
        ->willReturn([
            'id' => 2,
            'name' => 'Phòng nhỏ',
            'total_seats' => 50,
            'is_active' => 1,
        ]);

    $data = [
        'movie_id' => 1,
        'room_id' => 2,
        'show_date' => '2026-12-20',
        'start_time' => '20:00',
        'base_price' => 100000,
        'status' => 'active',
    ];
$result = $this->service->updateShowtime(
    $showtimeId,
    $data
);

$this->assertSame(
    'error',
    $result['status'],
    'TC-DT-17: Phải chặn đổi sang phòng có sức chứa nhỏ hơn số vé đã bán.'
);

$this->assertStringContainsString(
    'sức chứa',
    mb_strtolower($result['message']),
    'TC-DT-17: Lỗi trả về phải liên quan đến sức chứa phòng.'
);
}

/**
 * TC-DT-20
 * Kiểm tra hệ thống không được xóa suất chiếu
 * khi suất chiếu đã có vé được đặt.
 *
 * @testdox TC-DT-20 - Chặn xóa suất chiếu đã có vé được đặt
 */
public function testTcDt20PreventsDeletingShowtimeWithBookedTickets(): void
{
    $showtimeId = 202;

    /*
     * Giả lập suất chiếu tồn tại.
     */
    $this->showtimeModel
        ->method('findById')
        ->with($showtimeId)
        ->willReturn([
            'id' => $showtimeId,
            'movie_id' => 1,
            'room_id' => 1,
        ]);

    /*
     * Giả lập suất chiếu đã có vé được đặt.
     */
    $this->showtimeModel
    ->method('countBookedTickets')
    ->with($showtimeId)
    ->willReturn(3);

    /*
     * Theo TC-DT-20:
     * Khi đã có vé, Service KHÔNG được gọi delete().
     */
    $this->showtimeModel
        ->expects($this->never())
        ->method('delete');

    $result = $this->service->deleteShowtime(
        $showtimeId
    );

    $this->assertSame(
        'error',
        $result['status'],
        'TC-DT-20: Phải chặn xóa suất chiếu đã có vé được đặt.'
    );
}

public function testDeleteShowtimeRejectsInvalidId(): void
{
    $result = $this->service->deleteShowtime(0);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('ID', $result['message']);
}

public function testDeleteShowtimeRejectsNotFound(): void
{
    $this->showtimeModel
        ->method('findById')
        ->with(999)
        ->willReturn(null);

    $result = $this->service->deleteShowtime(999);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('không tồn tại', $result['message']);
}

public function testDeleteShowtimeSuccessfully(): void
{
    $this->showtimeModel
        ->method('findById')
        ->with(203)
        ->willReturn(['id' => 203]);

    $this->showtimeModel
        ->method('countBookedTickets')
        ->with(203)
        ->willReturn(0);

    $this->showtimeModel
        ->method('delete')
        ->with(203)
        ->willReturn(true);

    $result = $this->service->deleteShowtime(203);

    $this->assertSame('success', $result['status']);
}

public function testDeleteShowtimeReturnsErrorWhenDeleteFails(): void
{
    $this->showtimeModel
        ->method('findById')
        ->with(204)
        ->willReturn(['id' => 204]);

    $this->showtimeModel
        ->method('countBookedTickets')
        ->with(204)
        ->willReturn(0);

    $this->showtimeModel
        ->method('delete')
        ->with(204)
        ->willReturn(false);

    $result = $this->service->deleteShowtime(204);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('thất bại', $result['message']);
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

public function testUpdateShowtimeRejectsInvalidId(): void
{
    $result = $this->service->updateShowtime(0, []);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('ID', $result['message']);
}

public function testGetAllShowtimes(): void
{
    $expected = [
        ['id' => 1],
        ['id' => 2]
    ];

    $this->showtimeModel
        ->method('getAllWithDetails')
        ->willReturn($expected);

    $this->assertSame($expected, $this->service->getAllShowtimes());
}

public function testGetShowtimeDetailValidId(): void
{
    $expected = ['id' => 10];

    $this->showtimeModel
        ->method('getDetailById')
        ->with(10)
        ->willReturn($expected);

    $this->assertSame($expected, $this->service->getShowtimeDetail(10));
}

public function testGetShowtimesByMovieIdValidId(): void
{
    $expected = [
        ['id' => 1, 'movie_id' => 5]
    ];

    $this->showtimeModel
        ->method('getByMovieId')
        ->with(5)
        ->willReturn($expected);

    $this->assertSame($expected, $this->service->getShowtimesByMovieId(5));
}

public function testGetAllMovies(): void
{
    $expected = [
        ['id' => 1, 'title' => 'Movie A']
    ];

    $this->movieModel
        ->method('getAllMovies')
        ->willReturn($expected);

    $this->assertSame($expected, $this->service->getAllMovies());
}

public function testGetAllRooms(): void
{
    $expected = [
        ['id' => 1, 'name' => 'Room 1']
    ];

    $this->roomModel
        ->method('getAllRooms')
        ->willReturn($expected);

    $this->assertSame($expected, $this->service->getAllRooms());
}

public function testGetShowtimesByMovieInvalidId(): void
{
    $this->assertSame([], $this->service->getShowtimesByMovie(0));
}

public function testGetShowtimeDetailsInvalidId(): void
{
    $this->assertNull($this->service->getShowtimeDetails(0));
}

public function testGetShowtimeByIdInvalidId(): void
{
    $this->assertNull($this->service->getShowtimeById(0));
}

public function testAddShowtimeInsertFailure(): void
{
    $data = $this->validShowtimeData();

    $this->showtimeModel->method('movieExists')->willReturn(true);
    $this->movieModel->method('getMovieByIdWithGenres')->willReturn(['id' => 1, 'is_active' => 1]);
    $this->showtimeModel->method('roomExists')->willReturn(true);
    $this->showtimeModel->method('getMovieDuration')->willReturn(120);
    $this->showtimeModel->method('findConflict')->willReturn(false);

    $this->showtimeModel->method('insert')->willReturn(false);
    $this->showtimeModel->method('getError')->willReturn('Database error');

    $result = $this->service->addShowtime($data);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('Database error', $result['message']);
}

public function testUpdateShowtimeNotFound(): void
{
    $this->showtimeModel
        ->method('findById')
        ->with(999)
        ->willReturn(false);

    $result = $this->service->updateShowtime(999, []);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('không tồn tại', $result['message']);
}

public function testUpdateShowtimeSuccess(): void
{
    $data = $this->validShowtimeData();

    $this->showtimeModel->method('findById')->willReturn(['id' => 300]);
    $this->roomModel->method('findById')->willReturn(['id' => 1, 'total_seats' => 100]);
    $this->showtimeModel->method('countBookedTickets')->willReturn(10);

    $this->prepareValidDependencies(120, false);

    $this->showtimeModel
        ->method('update')
        ->with(300, $this->anything())
        ->willReturn(true);

    $result = $this->service->updateShowtime(300, $data);

    $this->assertSame('success', $result['status']);
}

public function testUpdateShowtimeFailure(): void
{
    $data = $this->validShowtimeData();

    $this->showtimeModel->method('findById')->willReturn(['id' => 301]);
    $this->roomModel->method('findById')->willReturn(['id' => 1, 'total_seats' => 100]);
    $this->showtimeModel->method('countBookedTickets')->willReturn(10);

    $this->prepareValidDependencies(120, false);

    $this->showtimeModel->method('update')->willReturn(false);
    $this->showtimeModel->method('getError')->willReturn('Update failed');

    $result = $this->service->updateShowtime(301, $data);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('Update failed', $result['message']);
}

public function testUpdateShowtimeWithoutRoomData(): void
{
    $data = $this->validShowtimeData();

    $this->showtimeModel->method('findById')->willReturn(['id' => 302]);

    // Cover nhánh $room == false
    $this->roomModel->method('findById')->willReturn(false);

    $this->prepareValidDependencies(120, false);
    $this->showtimeModel->method('update')->willReturn(true);

    $result = $this->service->updateShowtime(302, $data);

    $this->assertSame('success', $result['status']);
}

public function testDeleteShowtimeRejectsWhenTicketsAlreadyBooked(): void
{
    $showtimeId = 205;

    $this->showtimeModel
        ->method('findById')
        ->with($showtimeId)
        ->willReturn(['id' => $showtimeId]);

    $this->showtimeModel
        ->method('countBookedTickets')
        ->with($showtimeId)
        ->willReturn(2);

    $result = $this->service->deleteShowtime($showtimeId);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('đã có vé', $result['message']);
}
public function testAddShowtimeRejectsInvalidMovieDuration(): void
{
    $data = $this->validShowtimeData();

    $this->showtimeModel
        ->method('movieExists')
        ->willReturn(true);

    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->willReturn([
            'id' => 1,
            'is_active' => 1,
            'status' => 'active'
        ]);

    $this->showtimeModel
        ->method('roomExists')
        ->willReturn(true);

    $this->showtimeModel
        ->method('getMovieDuration')
        ->willReturn(0);

    $result = $this->service->addShowtime($data);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString(
        'Không thể tính giờ kết thúc',
        $result['message']
    );
}
public function testAddShowtimeRejectsInvalidMovie(): void
{
    $data = $this->validShowtimeData();

    $this->showtimeModel
        ->method('movieExists')
        ->willReturn(false);

    $result = $this->service->addShowtime($data);

    $this->assertSame('error', $result['status']);
    $this->assertSame('Phim không hợp lệ!', $result['message']);
}

public function testAddShowtimeRejectsInvalidRoom(): void
{
    $data = $this->validShowtimeData();

    $this->showtimeModel->method('movieExists')->willReturn(true);
    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->willReturn(['id' => 1, 'is_active' => 1, 'status' => 'active']);

    $this->showtimeModel
        ->method('roomExists')
        ->willReturn(false);

    $result = $this->service->addShowtime($data);

    $this->assertSame('error', $result['status']);
    $this->assertSame('Phòng chiếu không hợp lệ!', $result['message']);
}

public function testAddShowtimeRejectsEmptyShowDate(): void
{
    $data = $this->validShowtimeData();
    $data['show_date'] = '';

    $this->showtimeModel->method('movieExists')->willReturn(true);
    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->willReturn(['id' => 1, 'is_active' => 1, 'status' => 'active']);
    $this->showtimeModel->method('roomExists')->willReturn(true);

    $result = $this->service->addShowtime($data);

    $this->assertSame('error', $result['status']);
    $this->assertSame('Ngày chiếu không được để trống!', $result['message']);
}

public function testAddShowtimeRejectsZeroBasePrice(): void
{
    $data = $this->validShowtimeData();
    $data['base_price'] = 0;

    $this->showtimeModel->method('movieExists')->willReturn(true);
    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->willReturn(['id' => 1, 'is_active' => 1, 'status' => 'active']);
    $this->showtimeModel->method('roomExists')->willReturn(true);
    $this->showtimeModel->method('getMovieDuration')->willReturn(120);

    $result = $this->service->addShowtime($data);

    $this->assertSame('error', $result['status']);
    $this->assertSame('Giá vé cơ bản phải lớn hơn 0!', $result['message']);
}

public function testAddShowtimeRejectsInvalidStatus(): void
{
    $data = $this->validShowtimeData();
    $data['status'] = 'invalid_status';

    $this->showtimeModel->method('movieExists')->willReturn(true);
    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->willReturn(['id' => 1, 'is_active' => 1, 'status' => 'active']);
    $this->showtimeModel->method('roomExists')->willReturn(true);
    $this->showtimeModel->method('getMovieDuration')->willReturn(120);

    $result = $this->service->addShowtime($data);

    $this->assertSame('error', $result['status']);
    $this->assertSame('Trạng thái suất chiếu không hợp lệ!', $result['message']);
}
public function testAcceptsStartTimeWithSeconds(): void
{
    $this->prepareValidDependencies(120, false);

    $data = $this->validShowtimeData();
    $data['start_time'] = '10:30:45';

    $capturedData = null;

    $this->showtimeModel
    ->expects($this->once())
    ->method('insert')
    ->willReturnCallback(function ($input) use (&$capturedData) {
        $capturedData = $input;
        return true;
    });

    $result = $this->service->addShowtime($data);

    $this->assertSame('success', $result['status']);
    $this->assertSame('10:30:45', $capturedData['start_time']);
}
public function testGetShowtimesByMovieWithValidId(): void
{
    $expected = [
        ['showtime_id' => 1, 'movie_id' => 5]
    ];

    $this->showtimeModel
        ->expects($this->once())
        ->method('getByMovieId')
        ->with(5)
        ->willReturn($expected);

    $result = $this->service->getShowtimesByMovie(5);

    $this->assertSame($expected, $result);
}

public function testGetShowtimeDetailsWithValidId(): void
{
    $expected = [
        'showtime_id' => 10,
        'movie_id' => 5
    ];

    $this->showtimeModel
        ->expects($this->once())
        ->method('getDetailById')
        ->with(10)
        ->willReturn($expected);

    $result = $this->service->getShowtimeDetails(10);

    $this->assertSame($expected, $result);
}

public function testGetShowtimeByIdWithValidId(): void
{
    $expected = [
        'id' => 20,
        'movie_id' => 5
    ];

    $this->showtimeModel
        ->expects($this->once())
        ->method('findById')
        ->with(20)
        ->willReturn($expected);

    $result = $this->service->getShowtimeById(20);

    $this->assertSame($expected, $result);
}
}


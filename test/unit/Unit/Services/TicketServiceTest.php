<?php

namespace Tests\Unit\Services;

use App\Models\BookingModel;
use App\Models\SeatModel;
use App\Models\ShowtimeModel;
use App\Models\TicketModel;
use App\Services\TicketService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Unit tests for TicketService.
 *
 * All model dependencies are mocked, so the tests do not require a database.
 */
class TicketServiceTest extends TestCase
{
    private $ticketService;
    private $ticketModelMock;
    private $bookingModelMock;
    private $showtimeModelMock;
    private $seatModelMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketModelMock = $this->createMock(TicketModel::class);
        $this->bookingModelMock = $this->createMock(BookingModel::class);
        $this->showtimeModelMock = $this->createMock(ShowtimeModel::class);
        $this->seatModelMock = $this->createMock(SeatModel::class);

        $this->ticketService = new TicketService(
            $this->ticketModelMock,
            $this->bookingModelMock,
            $this->showtimeModelMock,
            $this->seatModelMock
        );
    }

    protected function tearDown(): void
    {
        unset(
            $this->ticketService,
            $this->ticketModelMock,
            $this->bookingModelMock,
            $this->showtimeModelMock,
            $this->seatModelMock
        );

        parent::tearDown();
    }

    private function validTicketData(array $overrides = []): array
    {
        return array_merge([
            'booking_id' => 101,
            'showtime_id' => 1,
            'seat_id' => 10,
            'price' => 90000,
            'status' => 'booked',
        ], $overrides);
    }

    private function configureValidValidationDependencies(): void
    {
        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        $this->showtimeModelMock->method('findById')->willReturn([
            'id' => 1,
            'room_id' => 5,
        ]);
        $this->seatModelMock->method('findById')->willReturn([
            'id' => 10,
            'room_id' => 5,
        ]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(false);
    }

    /**
     * Existing addTicket decision table.
     *
     * @dataProvider ticketCases
     * @testdox {testdox}
     */
    public function test_ticket_cases(
        $testdox,
        $scenario,
        $data,
        $expectedStatus,
        $expectedMessage,
        $expectedTicketId = null
    ): void {
        $booking = ['id' => 101];
        if ($scenario === 'invalid_booking') {
            $booking = null;
        }
        $this->bookingModelMock->method('getById')->willReturn($booking);

        $showtime = ['id' => 1, 'room_id' => 5];
        if ($scenario === 'invalid_showtime') {
            $showtime = null;
        }
        $this->showtimeModelMock->method('findById')->willReturn($showtime);

        $seat = ['id' => 10, 'room_id' => 5];
        if ($scenario === 'invalid_seat') {
            $seat = null;
        } elseif ($scenario === 'room_mismatch') {
            $seat['room_id'] = 6;
        }
        $this->seatModelMock->method('findById')->willReturn($seat);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(
            $scenario === 'already_booked'
        );

        if ($scenario === 'db_failure' || $scenario === 'success') {
            $createdTicketId = $scenario === 'success' ? 77 : false;
            $this->ticketModelMock->expects($this->once())
                ->method('create')
                ->willReturn($createdTicketId);
        }
        if ($scenario === 'db_failure') {
            $this->ticketModelMock->method('getError')
                ->willReturn('Database Connection Timeout');
        }

        $result = $this->ticketService->addTicket($data);

        $this->assertSame($expectedStatus, $result['status']);
        $this->assertStringContainsString($expectedMessage, $result['message']);
        if ($expectedTicketId !== null) {
            $this->assertSame($expectedTicketId, $result['ticket_id']);
        }
    }

    /**
     * TC-TKT-10: Mảng rỗng mặc định booking_id = 0.
     * Phủ validate() với input thiếu dữ liệu bắt buộc.
     */
    public function test_addTicket_rejects_empty_data(): void
    {
        $this->bookingModelMock->expects($this->never())->method('getById');
        $this->ticketModelMock->expects($this->never())->method('create');

        $result = $this->ticketService->addTicket([]);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Booking không hợp lệ!', $result['message']);
    }

    /**
     * TC-TKT-11: Booking ID = 0, âm hoặc chuỗi rỗng.
     * Phủ short-circuit booking_id <= 0 trong validate().
     *
     * @dataProvider nonPositiveIdProvider
     */
    public function test_addTicket_rejects_non_positive_booking_ids($bookingId): void
    {
        $this->bookingModelMock->expects($this->never())->method('getById');

        $result = $this->ticketService->addTicket(
            $this->validTicketData(['booking_id' => $bookingId])
        );

        $this->assertSame('error', $result['status']);
        $this->assertSame('Booking không hợp lệ!', $result['message']);
    }

    /**
     * TC-TKT-12/13: showtime_id hoặc seat_id bằng 0 không được query model tương ứng.
     *
     * @dataProvider zeroLookupIdProvider
     */
    public function test_addTicket_rejects_zero_showtime_and_seat_ids(
        string $invalidField,
        string $expectedMessage,
        bool $shouldLookupShowtime
    ): void {
        $this->bookingModelMock->expects($this->once())
            ->method('getById')
            ->with(101)
            ->willReturn(['id' => 101]);

        if ($shouldLookupShowtime) {
            $this->showtimeModelMock->expects($this->once())
                ->method('findById')
                ->with(1)
                ->willReturn(['id' => 1, 'room_id' => 5]);
        } else {
            $this->showtimeModelMock->expects($this->never())->method('findById');
        }
        $this->seatModelMock->expects($this->never())->method('findById');

        $result = $this->ticketService->addTicket(
            $this->validTicketData([$invalidField => 0])
        );

        $this->assertSame('error', $result['status']);
        $this->assertSame($expectedMessage, $result['message']);
    }

    /**
     * TC-TKT-14: Exception từ model được propagate; TicketService không tự rollback.
     */
    public function test_addTicket_propagates_model_exception(): void
    {
        $this->bookingModelMock->expects($this->once())
            ->method('getById')
            ->willThrowException(new RuntimeException('DB connection failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB connection failed');

        $this->ticketService->addTicket($this->validTicketData());
    }

    /**
     * TC-TKT-15: updateTicket từ chối ID = 0, ID âm và chuỗi rỗng.
     *
     * @dataProvider nonPositiveIdProvider
     */
    public function test_updateTicket_rejects_non_positive_ids($ticketId): void
    {
        $this->ticketModelMock->expects($this->never())->method('getById');

        $result = $this->ticketService->updateTicket(
            $ticketId,
            $this->validTicketData()
        );

        $this->assertSame('error', $result['status']);
        $this->assertSame('Vé không hợp lệ!', $result['message']);
    }

    /**
     * TC-TKT-16: Model trả về false/null khi tìm vé.
     *
     * @dataProvider missingTicketProvider
     */
    public function test_updateTicket_rejects_missing_ticket($storedTicket): void
    {
        $this->ticketModelMock->expects($this->once())
            ->method('getById')
            ->with(77)
            ->willReturn($storedTicket);

        $result = $this->ticketService->updateTicket(77, $this->validTicketData());

        $this->assertSame('error', $result['status']);
        $this->assertSame('Vé không hợp lệ!', $result['message']);
    }

    /**
     * TC-TKT-17: Validation lỗi thì không gọi update().
     */
    public function test_updateTicket_returns_validation_error(): void
    {
        $this->ticketModelMock->method('getById')->willReturn(['id' => 77]);
        $this->ticketModelMock->expects($this->never())->method('update');
        $this->configureValidValidationDependencies();

        $result = $this->ticketService->updateTicket(
            77,
            $this->validTicketData(['price' => 0])
        );

        $this->assertSame('error', $result['status']);
        $this->assertSame('Giá vé phải lớn hơn 0!', $result['message']);
    }

    /**
     * TC-TKT-18: Cập nhật status thành công.
     */
    public function test_updateTicket_updates_status_successfully(): void
    {
        $data = $this->validTicketData(['status' => 'canceled']);
        $this->ticketModelMock->method('getById')->willReturn(['id' => 77]);
        $this->ticketModelMock->expects($this->once())
            ->method('update')
            ->with(77, $data)
            ->willReturn(true);
        $this->configureValidValidationDependencies();

        $result = $this->ticketService->updateTicket(77, $data);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Cập nhật vé thành công!', $result['message']);
    }

    /**
     * TC-TKT-19: UPDATE false/getError phủ nhánh DB failure.
     */
    public function test_updateTicket_returns_database_error(): void
    {
        $data = $this->validTicketData();
        $this->ticketModelMock->method('getById')->willReturn(['id' => 77]);
        $this->ticketModelMock->expects($this->once())
            ->method('update')
            ->with(77, $data)
            ->willReturn(false);
        $this->ticketModelMock->method('getError')->willReturn('Rollback failed');
        $this->configureValidValidationDependencies();

        $result = $this->ticketService->updateTicket(77, $data);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Lỗi khi cập nhật vé: Rollback failed', $result['message']);
    }

    /**
     * TC-TKT-20: Exception DB khi update được propagate.
     */
    public function test_updateTicket_propagates_database_exception(): void
    {
        $this->ticketModelMock->method('getById')->willReturn(['id' => 77]);
        $this->ticketModelMock->expects($this->once())
            ->method('update')
            ->willThrowException(new RuntimeException('DB rollback exception'));
        $this->configureValidValidationDependencies();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('DB rollback exception');

        $this->ticketService->updateTicket(77, $this->validTicketData());
    }

    /**
     * TC-TKT-21: deleteTicket từ chối ID không hợp lệ.
     *
     * @dataProvider nonPositiveIdProvider
     */
    public function test_deleteTicket_rejects_non_positive_ids($ticketId): void
    {
        $this->ticketModelMock->expects($this->never())->method('getById');

        $result = $this->ticketService->deleteTicket($ticketId);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Vé không hợp lệ!', $result['message']);
    }

    /**
     * TC-TKT-22: Model trả về false/null khi xóa vé không tồn tại.
     *
     * @dataProvider missingTicketProvider
     */
    public function test_deleteTicket_rejects_missing_ticket($storedTicket): void
    {
        $this->ticketModelMock->expects($this->once())
            ->method('getById')
            ->with(77)
            ->willReturn($storedTicket);

        $result = $this->ticketService->deleteTicket(77);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Vé không hợp lệ!', $result['message']);
    }

    /**
     * TC-TKT-23/24: delete false và delete thành công.
     *
     * @dataProvider deleteResultProvider
     */
    public function test_deleteTicket_handles_database_result(
        bool $deleteResult,
        string $expectedStatus,
        string $expectedMessage
    ): void {
        $this->ticketModelMock->method('getById')->willReturn(['id' => 77]);
        $this->ticketModelMock->expects($this->once())
            ->method('delete')
            ->with(77)
            ->willReturn($deleteResult);
        if (!$deleteResult) {
            $this->ticketModelMock->method('getError')->willReturn('Delete failed');
        }

        $result = $this->ticketService->deleteTicket(77);

        $this->assertSame($expectedStatus, $result['status']);
        $this->assertSame($expectedMessage, $result['message']);
    }

    /**
     * TC-TKT-25: Exception DB khi delete được propagate.
     */
    public function test_deleteTicket_propagates_database_exception(): void
    {
        $this->ticketModelMock->method('getById')->willReturn(['id' => 77]);
        $this->ticketModelMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new RuntimeException('Delete exception'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Delete exception');

        $this->ticketService->deleteTicket(77);
    }

    /**
     * TC-TKT-26: getAllTickets() delegate nguyên kết quả model, kể cả mảng rỗng.
     */
    public function test_getAllTickets_delegates_to_model(): void
    {
        $this->ticketModelMock->expects($this->once())
            ->method('getAll')
            ->willReturn([]);

        $this->assertSame([], $this->ticketService->getAllTickets());
    }

    /**
     * TC-TKT-27/28: getTicketById() xử lý ID biên và kết quả null/false.
     *
     * @dataProvider nonPositiveIdProvider
     */
    public function test_getTicketById_returns_null_for_non_positive_ids($ticketId): void
    {
        $this->ticketModelMock->expects($this->never())->method('getById');

        $this->assertNull($this->ticketService->getTicketById($ticketId));
    }

    /**
     * @dataProvider nullableModelResultProvider
     */
    public function test_getTicketById_returns_model_result($modelResult): void
    {
        $this->ticketModelMock->expects($this->once())
            ->method('getById')
            ->with(77)
            ->willReturn($modelResult);

        $this->assertSame($modelResult, $this->ticketService->getTicketById(77));
    }

    /**
     * TC-TKT-29: ID = 0, âm, chuỗi rỗng và mảng rỗng trả [].
     *
     * @dataProvider invalidGetterIdProvider
     */
    public function test_getBookedSeatIdsByShowtimeId_returns_empty_for_invalid_ids($id): void
    {
        $this->ticketModelMock->expects($this->never())
            ->method('getBookedSeatIdsByShowtimeId');

        $this->assertSame([], $this->ticketService->getBookedSeatIdsByShowtimeId($id));
    }

    /**
     * TC-TKT-30: getBookedSeatIdsByShowtimeId() ép kiểu ID trước khi delegate.
     */
    public function test_getBookedSeatIdsByShowtimeId_casts_id(): void
    {
        $this->ticketModelMock->expects($this->once())
            ->method('getBookedSeatIdsByShowtimeId')
            ->with(10)
            ->willReturn([2, 4]);

        $this->assertSame([2, 4], $this->ticketService->getBookedSeatIdsByShowtimeId('10'));
    }

    /**
     * TC-TKT-31/32: isSeatBooked() forward ID đã ép kiểu và cả kết quả false.
     */
    public function test_isSeatBooked_casts_ids_and_returns_model_result(): void
    {
        $this->ticketModelMock->expects($this->once())
            ->method('isSeatBooked')
            ->with(12, 8, '99')
            ->willReturn(false);

        $this->assertFalse($this->ticketService->isSeatBooked('12', '8', '99'));
    }

    /**
     * TC-TKT-33/34: createMany() forward ID và mảng giá, kể cả mảng rỗng.
     *
     * @dataProvider createManyResultProvider
     */
    public function test_createMany_delegates_and_returns_model_result(
        array $seatPrices,
        $modelResult
    ): void {
        $this->ticketModelMock->expects($this->once())
            ->method('createMany')
            ->with(101, 5, $seatPrices)
            ->willReturn($modelResult);

        $this->assertSame(
            $modelResult,
            $this->ticketService->createMany('101', '5', $seatPrices)
        );
    }

    /**
     * TC-TKT-35: Exception DB ở helper createMany() được propagate.
     */
    public function test_createMany_propagates_database_exception(): void
    {
        $this->ticketModelMock->expects($this->once())
            ->method('createMany')
            ->willThrowException(new RuntimeException('Bulk insert failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bulk insert failed');

        $this->ticketService->createMany(101, 5, [
            ['seat_id' => 10, 'price' => 90000],
        ]);
    }

    /**
     * TC-TKT-36/37: getTicketsByBookingId() xử lý ID biên và delegate ID hợp lệ.
     *
     * @dataProvider invalidGetterIdProvider
     */
    public function test_getTicketsByBookingId_returns_empty_for_invalid_ids($id): void
    {
        $this->ticketModelMock->expects($this->never())->method('getByBookingId');

        $this->assertSame([], $this->ticketService->getTicketsByBookingId($id));
    }

    public function test_getTicketsByBookingId_casts_id_and_delegates(): void
    {
        $tickets = [['id' => 1, 'booking_id' => 101]];
        $this->ticketModelMock->expects($this->once())
            ->method('getByBookingId')
            ->with(101)
            ->willReturn($tickets);

        $this->assertSame($tickets, $this->ticketService->getTicketsByBookingId('101'));
    }

    /**
     * TC-TKT-38/39: getTotalTicketsByUser() xử lý ID biên và delegate ID hợp lệ.
     *
     * @dataProvider invalidGetterIdProvider
     */
    public function test_getTotalTicketsByUser_returns_zero_for_invalid_ids($id): void
    {
        $this->ticketModelMock->expects($this->never())->method('getTotalTicketsByUser');

        $this->assertSame(0, $this->ticketService->getTotalTicketsByUser($id));
    }

    public function test_getTotalTicketsByUser_casts_id_and_delegates(): void
    {
        $this->ticketModelMock->expects($this->once())
            ->method('getTotalTicketsByUser')
            ->with(12)
            ->willReturn(3);

        $this->assertSame(3, $this->ticketService->getTotalTicketsByUser('12'));
    }

    public static function zeroLookupIdProvider(): array
    {
        return [
            'showtime_id bằng 0' => [
                'showtime_id',
                'Suất chiếu không hợp lệ!',
                false,
            ],
            'seat_id bằng 0' => [
                'seat_id',
                'Ghế không hợp lệ!',
                true,
            ],
        ];
    }

    public static function nonPositiveIdProvider(): array
    {
        return [
            'ID bằng 0' => [0],
            'ID âm' => [-1],
            'chuỗi rỗng' => [''],
        ];
    }

    public static function invalidGetterIdProvider(): array
    {
        return [
            'ID bằng 0' => [0],
            'ID âm' => [-1],
            'chuỗi rỗng' => [''],
            'mảng rỗng' => [[]],
        ];
    }

    public static function missingTicketProvider(): array
    {
        return [
            'model trả về null' => [null],
            'model trả về false' => [false],
        ];
    }

    public static function nullableModelResultProvider(): array
    {
        return [
            'model trả về null' => [null],
            'model trả về false' => [false],
        ];
    }

    public static function deleteResultProvider(): array
    {
        return [
            'delete thành công' => [true, 'success', 'Xóa vé thành công!'],
            'delete thất bại' => [false, 'error', 'Lỗi khi xóa vé: Delete failed'],
        ];
    }

    public static function createManyResultProvider(): array
    {
        return [
            'mảng seatPrices hợp lệ' => [
                [['seat_id' => 10, 'price' => 90000]],
                true,
            ],
            'mảng seatPrices rỗng, model trả false' => [[], false],
            'model trả null' => [
                [['seat_id' => 10, 'price' => 90000]],
                null,
            ],
        ];
    }

    public static function ticketCases(): array
    {
        return [
            'TC-TKT-01: Booking ID không hợp lệ hoặc không tồn tại' => [
                'TC-TKT-01',
                'invalid_booking',
                [
                    'booking_id' => 999,
                    'showtime_id' => 1,
                    'seat_id' => 1,
                    'price' => 90000,
                    'status' => 'booked',
                ],
                'error',
                'Booking không hợp lệ!',
            ],
            'TC-TKT-02: Showtime ID không tồn tại' => [
                'TC-TKT-02',
                'invalid_showtime',
                [
                    'booking_id' => 101,
                    'showtime_id' => 999,
                    'seat_id' => 1,
                    'price' => 90000,
                    'status' => 'booked',
                ],
                'error',
                'Suất chiếu không hợp lệ!',
            ],
            'TC-TKT-03: Seat ID không tồn tại' => [
                'TC-TKT-03',
                'invalid_seat',
                [
                    'booking_id' => 101,
                    'showtime_id' => 1,
                    'seat_id' => 999,
                    'price' => 90000,
                    'status' => 'booked',
                ],
                'error',
                'Ghế không hợp lệ!',
            ],
            'TC-TKT-04: Ghế không thuộc phòng của suất chiếu' => [
                'TC-TKT-04',
                'room_mismatch',
                [
                    'booking_id' => 101,
                    'showtime_id' => 1,
                    'seat_id' => 10,
                    'price' => 90000,
                    'status' => 'booked',
                ],
                'error',
                'Ghế không thuộc phòng của suất chiếu này!',
            ],
            'TC-TKT-05: Ghế đã được đặt' => [
                'TC-TKT-05',
                'already_booked',
                [
                    'booking_id' => 101,
                    'showtime_id' => 1,
                    'seat_id' => 10,
                    'price' => 90000,
                    'status' => 'booked',
                ],
                'error',
                'Ghế này đã được đặt trong suất chiếu!',
            ],
            'TC-TKT-06: Giá vé âm' => [
                'TC-TKT-06',
                'invalid_price',
                [
                    'booking_id' => 101,
                    'showtime_id' => 1,
                    'seat_id' => 10,
                    'price' => -50,
                    'status' => 'booked',
                ],
                'error',
                'Giá vé phải lớn hơn 0!',
            ],
            'TC-TKT-07: Trạng thái không hợp lệ' => [
                'TC-TKT-07',
                'invalid_status',
                [
                    'booking_id' => 101,
                    'showtime_id' => 1,
                    'seat_id' => 10,
                    'price' => 90000,
                    'status' => 'pending',
                ],
                'error',
                'Trạng thái vé không hợp lệ!',
            ],
            'TC-TKT-08: Database không thể lưu vé' => [
                'TC-TKT-08',
                'db_failure',
                [
                    'booking_id' => 101,
                    'showtime_id' => 1,
                    'seat_id' => 10,
                    'price' => 90000,
                    'status' => 'booked',
                ],
                'error',
                'Lỗi khi thêm vé: Database Connection Timeout',
            ],
            'TC-TKT-09: Thêm vé thành công' => [
                'TC-TKT-09',
                'success',
                [
                    'booking_id' => 101,
                    'showtime_id' => 1,
                    'seat_id' => 10,
                    'price' => 90000,
                    'status' => 'booked',
                ],
                'success',
                'Thêm vé thành công!',
                77,
            ],
        ];
    }
}

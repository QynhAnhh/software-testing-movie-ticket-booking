<?php

namespace Tests\Unit;

use App\Services\BookingService;
use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;
use PHPUnit\Framework\TestCase;

class BookingServiceBoundaryTest extends TestCase
{
    private function createService(
        array $showtime = [],
        array $seats = [],
        bool $seatBooked = false,
        ?int $bookingId = 1001,
        bool $ticketsCreated = true
    ): BookingService {
        $booking = $this->createMock(BookingModel::class);
        $showtimeModel = $this->createMock(ShowtimeModel::class);
        $seatModel = $this->createMock(SeatModel::class);
        $ticketModel = $this->createMock(TicketModel::class);

        $defaultShowtime = [
            'id' => 1,
            'room_id' => 1,
            'show_date' => '2099-12-31',
            'start_time' => '20:00:00',
            'base_price' => 90000,
            'status' => 'active'
        ];

        $showtimeModel->method('getDetailById')
            ->willReturn($showtime ?: $defaultShowtime);

        $seatModel->method('getByIds')
            ->willReturn($seats);

        $ticketModel->method('isSeatBooked')
            ->willReturn($seatBooked);

        $booking->method('createBooking')
            ->willReturn($bookingId);

        $ticketModel->method('createMany')
            ->willReturn($ticketsCreated);

        $booking->method('beginTransaction');
        $booking->method('commit');
        $booking->method('rollback');

        $reflection = new \ReflectionClass(BookingService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $reflection = new \ReflectionClass($service);

        foreach ([
            'bookingModel' => $booking,
            'showtimeModel' => $showtimeModel,
            'seatModel' => $seatModel,
            'ticketModel' => $ticketModel
        ] as $property => $mock) {
            $propertyRef = $reflection->getProperty($property);
            $propertyRef->setAccessible(true); // NOSONAR
            $propertyRef->setValue($service, $mock); // NOSONAR
        }

        return $service;
    }

    private function validSeat(int $id = 1, int $roomId = 1, int $active = 1, float $extra = 0): array
    {
        return [
            'id' => $id,
            'room_id' => $roomId,
            'is_active' => $active,
            'seat_type_price' => $extra
        ];
    }

    /**
     * White-box branch:
     * userId <= 0
     */
    public function testRejectsInvalidUserId(): void
    {
        $service = $this->createService();

        $result = $service->processBooking(
            0,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * showtimeId <= 0
     */
    public function testRejectsInvalidShowtimeId(): void
    {
        $service = $this->createService();

        $result = $service->processBooking(
            1,
            0,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * empty($seatIds) || !is_array($seatIds)
     */
    public function testRejectsEmptySeatList(): void
    {
        $service = $this->createService();

        $result = $service->processBooking(
            1,
            1,
            [],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * payment method không hợp lệ -> cash
     */
    public function testInvalidPaymentMethodFallsBackToCash(): void
    {
        $booking = $this->createMock(BookingModel::class);
        $showtimeModel = $this->createMock(ShowtimeModel::class);
        $seatModel = $this->createMock(SeatModel::class);
        $ticketModel = $this->createMock(TicketModel::class);

        $showtimeModel->method('getDetailById')->willReturn([
            'id' => 1,
            'room_id' => 1,
            'show_date' => '2099-12-31',
            'start_time' => '20:00:00',
            'base_price' => 90000,
            'status' => 'active'
        ]);

        $seatModel->method('getByIds')->willReturn([
            $this->validSeat()
        ]);

        $ticketModel->method('isSeatBooked')->willReturn(false);

        $booking->expects($this->once())
            ->method('createBooking')
            ->with(1, 90000.0, 'cash')
            ->willReturn(1001);

        $ticketModel->method('createMany')->willReturn(true);

        $booking->method('beginTransaction');
        $booking->method('commit');
        $booking->method('rollback');

        $reflection = new \ReflectionClass(BookingService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $reflection = new \ReflectionClass($service);

        foreach ([
            'bookingModel' => $booking,
            'showtimeModel' => $showtimeModel,
            'seatModel' => $seatModel,
            'ticketModel' => $ticketModel
        ] as $property => $mock) {
            $propertyRef = $reflection->getProperty($property);
            $propertyRef->setAccessible(true); // NOSONAR
            $propertyRef->setValue($service, $mock); // NOSONAR
        }

        $result = $service->processBooking(
            1,
            1,
            [1],
            'invalid_method'
        );

        $this->assertSame('success', $result['status']);
    }

    /**
     * White-box branch:
     * showtime không tồn tại
     */
    public function testRejectsUnavailableShowtime(): void
    {
        $service = $this->createService(
            showtime: []
        );

        $reflection = new \ReflectionClass($service);

        $showtimeModel = $this->createMock(ShowtimeModel::class);
        $showtimeModel->method('getDetailById')->willReturn(null);

        $property = $reflection->getProperty('showtimeModel');
        $property->setAccessible(true); // NOSONAR
        $property->setValue($service, $showtimeModel);

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * suất chiếu không active
     */
    public function testRejectsInactiveShowtime(): void
    {
        $service = $this->createService([
            'id' => 1,
            'room_id' => 1,
            'show_date' => '2099-12-31',
            'start_time' => '20:00:00',
            'base_price' => 90000,
            'status' => 'inactive'
        ]);

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * suất chiếu đã bắt đầu
     */
    public function testRejectsStartedShowtime(): void
    {
        $service = $this->createService([
            'id' => 1,
            'room_id' => 1,
            'show_date' => '2000-01-01',
            'start_time' => '10:00:00',
            'base_price' => 90000,
            'status' => 'active'
        ]);

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * số lượng ghế lấy được khác số lượng ghế yêu cầu
     */
    public function testRejectsInvalidSeatList(): void
    {
        $service = $this->createService(
            seats: []
        );

        $result = $service->processBooking(
            1,
            1,
            [999],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * ghế không thuộc phòng chiếu
     */
    public function testRejectsSeatFromDifferentRoom(): void
    {
        $service = $this->createService(
            seats: [
                $this->validSeat(1, 99)
            ]
        );

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * ghế inactive
     */
    public function testRejectsInactiveSeat(): void
    {
        $service = $this->createService(
            seats: [
                $this->validSeat(1, 1, 0)
            ]
        );

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * ghế đã được đặt
     */
    public function testRejectsBookedSeat(): void
    {
        $service = $this->createService(
            seats: [
                $this->validSeat()
            ],
            seatBooked: true
        );

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * createBooking thất bại -> rollback
     */
    public function testRollsBackWhenBookingCreationFails(): void
    {
        $service = $this->createService(
            seats: [
                $this->validSeat()
            ],
            bookingId: null
        );

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box branch:
     * createMany thất bại -> rollback
     */
    public function testRollsBackWhenTicketCreationFails(): void
    {
        $service = $this->createService(
            seats: [
                $this->validSeat()
            ],
            bookingId: 1001,
            ticketsCreated: false
        );

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
    }

    /**
     * White-box main success path:
     * validate -> calculate -> transaction -> booking -> tickets -> commit
     */
    public function testSuccessfulBookingFollowsMainControlFlow(): void
    {
        $service = $this->createService(
            seats: [
                $this->validSeat(1, 1, 1, 0),
                $this->validSeat(2, 1, 1, 20000)
            ]
        );

        $result = $service->processBooking(
            1,
            1,
            [1, 2],
            'momo'
        );

        $this->assertSame('success', $result['status']);
        $this->assertSame('Đặt vé thành công!', $result['message']);
        $this->assertArrayHasKey('booking_id', $result);
    }

    /**
     * White-box loop:
     * kiểm tra nhiều ghế trong foreach.
     */
    public function testProcessesAllSelectedSeats(): void
    {
        $service = $this->createService(
            seats: [
                $this->validSeat(1, 1, 1, 0),
                $this->validSeat(2, 1, 1, 10000),
                $this->validSeat(3, 1, 1, 20000)
            ]
        );

        $result = $service->processBooking(
            1,
            1,
            [1, 2, 3],
            'bank_transfer'
        );

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('booking_id', $result);
    }
}

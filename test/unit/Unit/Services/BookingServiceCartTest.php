<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;
use App\Models\BookingModel;
use App\Models\TicketModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;

/**
 * CartTest: Kiểm thử tính toán giá (seat_type_price + base_price)
 */
class BookingServiceCartTest extends TestCase
{
    private $service;
    private $bookingMock;
    private $showtimeMock;
    private $seatMock;
    private $ticketMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingMock  = $this->createMock(BookingModel::class);
        $this->showtimeMock = $this->createMock(ShowtimeModel::class);
        $this->seatMock     = $this->createMock(SeatModel::class);
        $this->ticketMock   = $this->createMock(TicketModel::class);

        $this->service = new BookingService(
            $this->bookingMock,
            $this->showtimeMock,
            $this->seatMock,
            $this->ticketMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->service, $this->bookingMock,
              $this->showtimeMock, $this->seatMock, $this->ticketMock);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    private function mockShowtimeWith(float $basePrice = 90000): void
    {
        $this->showtimeMock->method('getDetailById')->willReturn([
            'id'         => 1,
            'room_id'    => 1,
            'base_price' => $basePrice,
            'show_date'  => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '18:00:00',
            'status'     => 'active',
        ]);
    }

    private function makeSeat(int $id, float $typePrice = 0, int $roomId = 1): array
    {
        return ['id' => $id, 'room_id' => $roomId, 'is_active' => 1, 'seat_type_price' => $typePrice];
    }

    private function successSetup(array $seats): void
    {
        $this->seatMock->method('getByIds')->willReturn($seats);
        $this->ticketMock->method('isSeatBooked')->willReturn(false);
        $this->bookingMock->method('createBooking')->willReturn(999);
        $this->ticketMock->method('createMany')->willReturn(true);
    }

    // =========================================================================
    // TC-QG-05: Ghế thường (seat_type_price = 0)
    // =========================================================================
    public function testTCQG05CalculateNormalSeats()
    {
        $this->mockShowtimeWith(90000);
        $this->successSetup([
            $this->makeSeat(1, 0),
            $this->makeSeat(2, 0),
        ]);

        $this->bookingMock->expects($this->once())
            ->method('createBooking')
            ->with($this->anything(), 180000.0, $this->anything())
            ->willReturn(999);

        $result = $this->service->processBooking(1, 1, [1, 2], 'momo');
        $this->assertSame('success', $result['status']);
    }

    // =========================================================================
    // TC-QG-06: Ghế thường + VIP (seat_type_price > 0)
    // =========================================================================
    public function testTCQG06CalculateNormalAndVipSeats()
    {
        $this->mockShowtimeWith(90000);
        $this->successSetup([
            $this->makeSeat(1, 0),
            $this->makeSeat(2, 50000), // VIP
        ]);

        $this->bookingMock->expects($this->once())
            ->method('createBooking')
            ->with($this->anything(), 230000.0, $this->anything())
            ->willReturn(999);

        $result = $this->service->processBooking(1, 1, [1, 2], 'momo');
        $this->assertSame('success', $result['status']);
    }

    // =========================================================================
    // TC-QG-07: Chỉ chọn 1 ghế (sau khi bỏ 1 ghế ban đầu)
    // =========================================================================
    public function testTCQG07RecalculateAfterRemovingSeat()
    {
        $this->mockShowtimeWith(90000);
        $this->successSetup([$this->makeSeat(1, 0)]);

        $this->bookingMock->expects($this->once())
            ->method('createBooking')
            ->with($this->anything(), 90000.0, $this->anything())
            ->willReturn(999);

        $result = $this->service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('success', $result['status']);
    }

    // =========================================================================
    // TC-QG-08: Đổi sang ghế VIP
    // =========================================================================
    public function testTCQG08RecalculateAfterChangingToVip()
    {
        $this->mockShowtimeWith(90000);
        $this->successSetup([$this->makeSeat(2, 50000)]);

        $this->bookingMock->expects($this->once())
            ->method('createBooking')
            ->with($this->anything(), 140000.0, $this->anything())
            ->willReturn(999);

        $result = $this->service->processBooking(1, 1, [2], 'momo');
        $this->assertSame('success', $result['status']);
    }

    // =========================================================================
    // TC-QG-09: Giỏ hàng rỗng -> lỗi
    // =========================================================================
    public function testTCQG09EmptyCartIsRejected()
    {
        $this->showtimeMock->method('getDetailById')->willReturn([
            'id' => 1, 'room_id' => 1, 'base_price' => 90000,
            'show_date'  => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '18:00:00', 'status' => 'active',
        ]);

        $result = $this->service->processBooking(1, 1, [], 'momo');
        $this->assertSame('error', $result['status']);
    }

    // =========================================================================
    // TC-QG-15: Tính giá nhất quán (gọi lại 2 lần cho cùng input)
    // =========================================================================
    public function testTCQG15CalculationIsConsistent()
    {
        // Lần 1
        $mock1 = $this->createMock(BookingModel::class);
        $stm1  = $this->createMock(ShowtimeModel::class);
        $sm1   = $this->createMock(SeatModel::class);
        $tm1   = $this->createMock(TicketModel::class);
        $stm1->method('getDetailById')->willReturn([
            'id' => 1, 'room_id' => 1, 'base_price' => 90000,
            'show_date' => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '18:00:00', 'status' => 'active',
        ]);
        $sm1->method('getByIds')->willReturn([$this->makeSeat(1, 0), $this->makeSeat(2, 0)]);
        $tm1->method('isSeatBooked')->willReturn(false);
        $mock1->method('createBooking')->willReturn(1);
        $tm1->method('createMany')->willReturn(true);
        $svc1   = new BookingService($mock1, $stm1, $sm1, $tm1);
        $result1 = $svc1->processBooking(1, 1, [1, 2], 'momo');

        // Lần 2
        $mock2 = $this->createMock(BookingModel::class);
        $stm2  = $this->createMock(ShowtimeModel::class);
        $sm2   = $this->createMock(SeatModel::class);
        $tm2   = $this->createMock(TicketModel::class);
        $stm2->method('getDetailById')->willReturn([
            'id' => 1, 'room_id' => 1, 'base_price' => 90000,
            'show_date' => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '18:00:00', 'status' => 'active',
        ]);
        $sm2->method('getByIds')->willReturn([$this->makeSeat(1, 0), $this->makeSeat(2, 0)]);
        $tm2->method('isSeatBooked')->willReturn(false);
        $mock2->method('createBooking')->willReturn(2);
        $tm2->method('createMany')->willReturn(true);
        $svc2   = new BookingService($mock2, $stm2, $sm2, $tm2);
        $result2 = $svc2->processBooking(1, 1, [1, 2], 'momo');

        $this->assertSame($result1['status'], $result2['status']);
    }

    public function testTCQG16CalculateMultipleSeatTypes()
    {
        // Dùng mock riêng biệt để tránh xung đột với các test khác đã stub getByIds()
        $bm  = $this->createMock(\App\Models\BookingModel::class);
        $stm = $this->createMock(\App\Models\ShowtimeModel::class);
        $sm  = $this->createMock(\App\Models\SeatModel::class);
        $tm  = $this->createMock(\App\Models\TicketModel::class);

        $stm->method('getDetailById')->willReturn([
            'id' => 1, 'room_id' => 1, 'base_price' => 90000,
            'show_date' => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '18:00:00', 'status' => 'active',
        ]);
        $sm->method('getByIds')->willReturn([
            $this->makeSeat(1, 0),
            $this->makeSeat(2, 50000),
            $this->makeSeat(3, 100000),
            $this->makeSeat(4, 0),
        ]);
        $tm->method('isSeatBooked')->willReturn(false);
        $bm->expects($this->once())
            ->method('createBooking')
            ->willReturn(999);
        $tm->method('createMany')->willReturn(true);
        $bm->method('beginTransaction');
        $bm->method('commit');
        $bm->method('rollback');

        $svc    = new BookingService($bm, $stm, $sm, $tm);
        $result = $svc->processBooking(1, 1, [1, 2, 3, 4], 'momo');
        $this->assertSame('success', $result['status'], 'Error: ' . ($result['message'] ?? 'none'));
    }

    // =========================================================================
    // TC-QG-18: Đặt vé thành công hoàn chỉnh
    // =========================================================================
    public function testTCQG18ValidCartProducesSuccessfulBooking()
    {
        $this->mockShowtimeWith(100000);
        $this->successSetup([
            $this->makeSeat(5, 0),
            $this->makeSeat(6, 50000),
        ]);

        $result = $this->service->processBooking(2, 1, [5, 6], 'vnpay');

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('booking_id', $result);
    }
}

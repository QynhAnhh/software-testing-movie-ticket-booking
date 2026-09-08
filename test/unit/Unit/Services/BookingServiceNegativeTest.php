<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;
use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;

/**
 * NegativeTest: Kiểm thử các kịch bản lỗi và điều kiện tranh chấp (Race condition)
 */
class BookingServiceNegativeTest extends TestCase
{
    private function futureShowtime(): array
    {
        return [
            'id'         => 1, 'room_id'    => 1,
            'show_date'  => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '18:00:00',
            'base_price' => 90000, 'status'     => 'active',
        ];
    }

    private function makeService(
        ?array $showtime  = null,
        array  $seats     = [],
        bool   $isBooked  = false,
        ?int   $bookingId = 1
    ): BookingService {
        $bm = $this->createMock(BookingModel::class);
        $sm = $this->createMock(ShowtimeModel::class);
        $se = $this->createMock(SeatModel::class);
        $tm = $this->createMock(TicketModel::class);

        $sm->method('getDetailById')->willReturn($showtime ?? $this->futureShowtime());
        $se->method('getByIds')->willReturn($seats);
        $tm->method('isSeatBooked')->willReturn($isBooked);
        $bm->method('createBooking')->willReturn($bookingId);
        $tm->method('createMany')->willReturn(true);
        $bm->method('beginTransaction');
        $bm->method('commit');
        $bm->method('rollback');

        return new BookingService($bm, $sm, $se, $tm);
    }

    // =========================================================================
    // Đặt ghế đã bị người khác đặt
    // =========================================================================
    public function testCannotBookAlreadyBookedSeat(): void
    {
        $seat    = ['id' => 1, 'room_id' => 1, 'is_active' => 1, 'seat_type_price' => 0];
        $service = $this->makeService(seats: [$seat], isBooked: true);

        $result = $service->processBooking(1, 1, [1], 'momo');

        $this->assertSame('error', $result['status']);
    }

    // =========================================================================
    // Race condition: 2 người cùng đặt 1 ghế
    // =========================================================================
    public function testConcurrentBookingIsRejectedWhenSeatBecomesBooked(): void
    {
        // Lần đặt 1: thành công
        $bm1 = $this->createMock(BookingModel::class);
        $sm1 = $this->createMock(ShowtimeModel::class);
        $se1 = $this->createMock(SeatModel::class);
        $tm1 = $this->createMock(TicketModel::class);
        $seat = ['id' => 1, 'room_id' => 1, 'is_active' => 1, 'seat_type_price' => 0];

        $sm1->method('getDetailById')->willReturn($this->futureShowtime());
        $se1->method('getByIds')->willReturn([$seat]);
        $tm1->method('isSeatBooked')->willReturn(false);
        $bm1->method('createBooking')->willReturn(100);
        $tm1->method('createMany')->willReturn(true);
        $bm1->method('beginTransaction');
        $bm1->method('commit');
        $svc1   = new BookingService($bm1, $sm1, $se1, $tm1);
        $result1 = $svc1->processBooking(1, 1, [1], 'momo');
        $this->assertSame('success', $result1['status']);

        // Lần đặt 2 (cùng ghế): bị chặn vì ghế đã được đặt
        $bm2 = $this->createMock(BookingModel::class);
        $sm2 = $this->createMock(ShowtimeModel::class);
        $se2 = $this->createMock(SeatModel::class);
        $tm2 = $this->createMock(TicketModel::class);

        $sm2->method('getDetailById')->willReturn($this->futureShowtime());
        $se2->method('getByIds')->willReturn([$seat]);
        $tm2->method('isSeatBooked')->willReturn(true); // ghế đã bị chiếm
        $bm2->method('beginTransaction');
        $bm2->method('rollback');
        $svc2   = new BookingService($bm2, $sm2, $se2, $tm2);
        $result2 = $svc2->processBooking(2, 1, [1], 'momo');
        $this->assertSame('error', $result2['status']);
    }

    // =========================================================================
    // Quá 10 ghế
    // =========================================================================
    public function testCannotBookMoreThan10Seats(): void
    {
        $sm = $this->createMock(ShowtimeModel::class);
        $sm->method('getDetailById')->willReturn($this->futureShowtime());
        $svc    = new BookingService(null, $sm);
        $result = $svc->processBooking(1, 1, range(1, 11), 'momo');
        $this->assertSame('error', $result['status']);
    }

    // =========================================================================
    // cancelBooking: các trường hợp biên
    // =========================================================================
    public function testCancelWithInvalidUserId(): void
    {
        $svc    = new BookingService();
        $result = $svc->cancelBooking(0, 1);
        $this->assertSame('error', $result['status']);
    }

    public function testCancelWithInvalidBookingId(): void
    {
        $svc    = new BookingService();
        $result = $svc->cancelBooking(1, 0);
        $this->assertSame('error', $result['status']);
    }

    public function testCancelAlreadyCanceledBooking(): void
    {
        $bm = $this->createMock(BookingModel::class);
        $bm->method('getByIdAndUser')->willReturn(['id' => 1, 'status' => 'canceled']);
        $svc    = new BookingService($bm);
        $result = $svc->cancelBooking(1, 1);
        $this->assertSame('error', $result['status']);
    }

    public function testCancelWhenShowtimeAlreadyStarted(): void
    {
        $bm = $this->createMock(BookingModel::class);
        $bm->method('getByIdAndUser')->willReturn(['id' => 1, 'status' => 'pending']);
        $bm->method('getPrimaryShowtimeByBookingId')->willReturn([
            'show_date'  => date('Y-m-d', strtotime('-1 day')),
            'start_time' => '10:00:00',
        ]);
        $svc    = new BookingService($bm);
        $result = $svc->cancelBooking(1, 1);
        $this->assertSame('error', $result['status']);
    }

    public function testCancelFailsWhenModelReturnsFalse(): void
    {
        $bm = $this->createMock(BookingModel::class);
        $bm->method('getByIdAndUser')->willReturn(['id' => 1, 'status' => 'pending']);
        $bm->method('getPrimaryShowtimeByBookingId')->willReturn(null);
        $bm->method('cancelBooking')->willReturn(false);
        $bm->method('getError')->willReturn('DB error');
        $bm->method('beginTransaction');
        $bm->method('rollback');
        $svc    = new BookingService($bm);
        $result = $svc->cancelBooking(1, 1);
        $this->assertSame('error', $result['status']);
    }
}

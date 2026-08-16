<?php

namespace Tests\Unit;

use App\Services\BookingService;
use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;
use PHPUnit\Framework\TestCase;

class BookingServiceTest extends TestCase
{
    private function service(array $prices, ?float $total = null): BookingService
    {
        $booking = $this->createMock(BookingModel::class);
        $showtime = $this->createMock(ShowtimeModel::class);
        $seat = $this->createMock(SeatModel::class);
        $ticket = $this->createMock(TicketModel::class);

        $showtime->method('getDetailById')->willReturn([
            'room_id' => 1,
            'show_date' => '2099-12-31',
            'start_time' => '20:00:00',
            'base_price' => 90000,
            'status' => 'active'
        ]);

        $data = [];
        foreach ($prices as $i => $price) {
            $data[] = [
                'id' => $i + 1,
                'room_id' => 1,
                'is_active' => 1,
                'seat_type_price' => $price
            ];
        }

        $seat->method('getByIds')->willReturnCallback(
            fn($ids) => array_values(array_filter(
                $data,
                fn($s) => in_array($s['id'], $ids)
            ))
        );

        $ticket->method('isSeatBooked')->willReturn(false);
        $ticket->method('createMany')->willReturn(true);

        if ($total !== null) {
            $booking->expects($this->once())
                ->method('createBooking')
                ->with(1, $total, $this->anything())
                ->willReturn(1001);
        } else {
            $booking->method('createBooking')->willReturn(1001);
        }

        $booking->method('beginTransaction');
        $booking->method('commit');
        $booking->method('rollback');

        $service = new BookingService();
        $ref = new \ReflectionClass($service);

        foreach ([
            'bookingModel' => $booking,
            'showtimeModel' => $showtime,
            'seatModel' => $seat,
            'ticketModel' => $ticket
        ] as $name => $mock) {
            $p = $ref->getProperty($name);
            $p->setAccessible(true);
            $p->setValue($service, $mock);
        }

        return $service;
    }

    // TC-QG-05: 2 ghế thường = 180.000
    public function testNormalSeatsTotal(): void
    {
        $result = $this->service([0, 0], 180000)
            ->processBooking(1, 1, [1, 2], 'cash');

        $this->assertSame('success', $result['status']);
    }

    // TC-QG-06: Thường + VIP = 200.000
    public function testNormalAndVipTotal(): void
    {
        $result = $this->service([0, 20000], 200000)
            ->processBooking(1, 1, [1, 2], 'cash');

        $this->assertSame('success', $result['status']);
    }

    // TC-QG-07: Xóa ghế VIP → còn 90.000
    public function testRemoveSeatUpdatesTotal(): void
    {
        $result = $this->service([0, 20000], 90000)
            ->processBooking(1, 1, [1], 'cash');

        $this->assertSame('success', $result['status']);
    }

    // TC-QG-08: Đổi sang VIP → 110.000
    public function testChangeToVipUpdatesTotal(): void
    {
        $result = $this->service([0, 20000], 110000)
            ->processBooking(1, 1, [2], 'cash');

        $this->assertSame('success', $result['status']);
    }

    // TC-QG-09: Xóa toàn bộ giỏ hàng
    public function testEmptyCart(): void
    {
        $result = $this->service([0])
            ->processBooking(1, 1, [], 'cash');

        $this->assertSame('error', $result['status']);
    }

    // TC-QG-15: Tổng tiền nhất quán
    public function testCalculationIsConsistent(): void
    {
        $result = $this->service([0, 20000], 200000)
            ->processBooking(1, 1, [1, 2], 'cash');

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('booking_id', $result);
    }

    // TC-QG-16: 2 thường + 2 VIP = 400.000
    public function testMultipleSeatsTotal(): void
    {
        $result = $this->service(
            [0, 0, 20000, 20000],
            400000
        )->processBooking(1, 1, [1, 2, 3, 4], 'cash');

        $this->assertSame('success', $result['status']);
    }

    // TC-QG-18: Giỏ hàng hợp lệ
    public function testValidCart(): void
    {
        $result = $this->service([0, 20000], 200000)
            ->processBooking(1, 1, [1, 2], 'momo');

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('booking_id', $result);
    }
}
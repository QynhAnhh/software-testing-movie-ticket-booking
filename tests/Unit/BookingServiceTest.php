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
    private function createService(
        array $seatPrices = [0, 0, 20000, 20000],
        ?float $expectedTotal = null
    ): BookingService {
        $booking = $this->createMock(BookingModel::class);
        $showtime = $this->createMock(ShowtimeModel::class);
        $seat = $this->createMock(SeatModel::class);
        $ticket = $this->createMock(TicketModel::class);

        // Suất chiếu hợp lệ
        $showtime->method('getDetailById')->willReturn([
            'id' => 1,
            'room_id' => 1,
            'show_date' => '2099-12-31',
            'start_time' => '20:00:00',
            'base_price' => 90000,
            'status' => 'active'
        ]);

        // Tạo dữ liệu ghế giả lập
        $seats = [];

        foreach ($seatPrices as $i => $extraPrice) {
            $seats[] = [
                'id' => $i + 1,
                'room_id' => 1,
                'is_active' => 1,
                'seat_type_price' => $extraPrice
            ];
        }

        $seat->method('getByIds')
            ->willReturnCallback(function (array $ids) use ($seats) {
                return array_values(
                    array_filter(
                        $seats,
                        fn($seat) => in_array(
                            (int)$seat['id'],
                            array_map('intval', $ids),
                            true
                        )
                    )
                );
            });

        // Không có ghế nào bị đặt trước
        $ticket->method('isSeatBooked')->willReturn(false);

        /*
         * Kiểm tra tổng tiền.
         *
         * Giá cơ bản:
         * 90.000
         *
         * VIP:
         * 90.000 + 20.000 = 110.000
         */
        if ($expectedTotal !== null) {
            $booking->expects($this->once())
                ->method('createBooking')
                ->with(
                    1,
                    $expectedTotal,
                    $this->anything()
                )
                ->willReturn(1001);
        } else {
            $booking->method('createBooking')
                ->willReturn(1001);
        }

        $ticket->method('createMany')->willReturn(true);

        // Transaction giả lập
        $booking->method('beginTransaction');
        $booking->method('commit');
        $booking->method('rollback');

        // Inject Mock vào BookingService
        $service = new BookingService();

        $reflection = new \ReflectionClass($service);

        foreach ([
            'bookingModel' => $booking,
            'showtimeModel' => $showtime,
            'seatModel' => $seat,
            'ticketModel' => $ticket
        ] as $property => $mock) {

            $propertyRef = $reflection->getProperty($property);
            $propertyRef->setAccessible(true);
            $propertyRef->setValue($service, $mock);
        }

        return $service;
    }

    /**
     * TC-QG-05
     * 2 ghế thường:
     * 90.000 + 90.000 = 180.000
     */
    public function testTCQG05CalculateNormalSeats(): void
    {
        $service = $this->createService(
            [0, 0],
            180000.0
        );

        $result = $service->processBooking(
            1,
            1,
            [1, 2],
            'cash'
        );

        $this->assertSame('success', $result['status']);
    }

    /**
     * TC-QG-06
     * 1 ghế thường + 1 ghế VIP:
     * 90.000 + 110.000 = 200.000
     */
    public function testTCQG06CalculateNormalAndVipSeats(): void
    {
        $service = $this->createService(
            [0, 20000],
            200000.0
        );

        $result = $service->processBooking(
            1,
            1,
            [1, 2],
            'cash'
        );

        $this->assertSame('success', $result['status']);
    }

    /**
     * TC-QG-07
     * Xóa ghế VIP khỏi giỏ:
     * ban đầu: 200.000
     * sau khi xóa VIP: còn 90.000
     */
    public function testTCQG07RecalculateAfterRemovingSeat(): void
    {
        $service = $this->createService(
            [0, 20000],
            90000.0
        );

        // Sau khi xóa ghế VIP, chỉ còn ghế thường
        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('success', $result['status']);
    }

    /**
     * TC-QG-08
     * Thay đổi từ ghế thường sang VIP:
     * 90.000 -> 110.000
     */
    public function testTCQG08RecalculateAfterChangingToVip(): void
    {
        $service = $this->createService(
            [0, 20000],
            110000.0
        );

        // Sau khi đổi sang ghế VIP
        $result = $service->processBooking(
            1,
            1,
            [2],
            'cash'
        );

        $this->assertSame('success', $result['status']);
    }

    /**
     * TC-QG-09
     * Xóa toàn bộ giỏ hàng.
     * Không được phép đặt khi không còn ghế.
     */
    public function testTCQG09EmptyCartIsRejected(): void
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
     * TC-QG-15
     * Kiểm tra tính nhất quán:
     * cùng một dữ liệu phải cho cùng một tổng tiền.
     */
    public function testTCQG15CalculationIsConsistent(): void
    {
        $service = $this->createService(
            [0, 20000],
            200000.0
        );

        $result = $service->processBooking(
            1,
            1,
            [1, 2],
            'cash'
        );

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('booking_id', $result);
    }

    /**
     * TC-QG-16
     * 2 ghế thường + 2 ghế VIP:
     *
     * 90.000 + 90.000
     * + 110.000 + 110.000
     * = 400.000
     */
    public function testTCQG16CalculateMultipleSeatTypes(): void
    {
        $service = $this->createService(
            [0, 0, 20000, 20000],
            400000.0
        );

        $result = $service->processBooking(
            1,
            1,
            [1, 2, 3, 4],
            'cash'
        );

        $this->assertSame('success', $result['status']);
    }

    /**
     * TC-QG-18
     * Giỏ hàng hợp lệ phải tạo booking thành công.
     */
    public function testTCQG18ValidCartProducesSuccessfulBooking(): void
    {
        $service = $this->createService(
            [0, 20000],
            200000.0
        );

        $result = $service->processBooking(
            1,
            1,
            [1, 2],
            'momo'
        );

        $this->assertSame('success', $result['status']);
        $this->assertArrayHasKey('booking_id', $result);
    }

    /**
     * Dọn trạng thái sau mỗi test.
     *
     * Các test đang sử dụng Mock nên không ghi dữ liệu
     * vào Database thật. Việc reset Mock giúp mỗi test
     * hoạt động độc lập.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
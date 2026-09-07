<?php

namespace Tests\Unit;

use App\Services\BookingService;
use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;
use PHPUnit\Framework\TestCase;

class BookingServiceNegativeTest extends TestCase
{
    /**
     * Tạo BookingService với dữ liệu Mock.
     *
     * $seatAlreadyBooked:
     * true  = ghế đã được đặt
     * false = ghế còn trống
     */
    private function createService(bool $seatAlreadyBooked): BookingService
    {
        $booking = $this->createMock(BookingModel::class);
        $showtime = $this->createMock(ShowtimeModel::class);
        $seat = $this->createMock(SeatModel::class);
        $ticket = $this->createMock(TicketModel::class);

        // Suất chiếu hợp lệ
        $showtime->method('getDetailById')
            ->willReturn([
                'id' => 1,
                'room_id' => 1,
                'show_date' => '2099-12-31',
                'start_time' => '20:00:00',
                'base_price' => 90000,
                'status' => 'active'
            ]);

        // Ghế hợp lệ
        $seat->method('getByIds')
            ->willReturn([
                [
                    'id' => 1,
                    'room_id' => 1,
                    'is_active' => 1,
                    'seat_type_price' => 0
                ]
            ]);

        // Mock trạng thái ghế
        $ticket->method('isSeatBooked')
            ->willReturn($seatAlreadyBooked);

        // Các hàm này chỉ được sử dụng khi booking hợp lệ
        $booking->method('createBooking')
            ->willReturn(1001);

        $ticket->method('createMany')
            ->willReturn(true);

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
            $propertyRef->setValue($service, $mock);
        }

        return $service;
    }

    /**
     * KAN-81
     *
     * Phương thức thanh toán không nằm trong danh sách được hỗ trợ
     * phải bị từ chối, không được tự động chuyển sang phương thức khác.
     */
    public function testRejectsInvalidPaymentMethod(): void
    {
        $service = $this->createService(false);

        $result = $service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertSame('error', $result['status']);
        $this->assertSame(
            'Phương thức thanh toán không hợp lệ.',
            $result['message']
        );
    }
    /**
     * TC-QG-10
     *
     * Ghế đã được người khác đặt trước.
     * Backend phải từ chối giao dịch.
     */
    public function testCannotBookAlreadyBookedSeat(): void
    {
        $service = $this->createService(true);

        $result = $service->processBooking(
            1,
            1,
            [1],
            'momo'
        );

        // Phải trả về lỗi
        $this->assertSame('error', $result['status']);

        // Không được tạo booking khi ghế đã được đặt
        $this->assertArrayNotHasKey('booking_id', $result);
    }

    /**
     * TC-QG-11
     *
     * Mô phỏng tranh chấp ghế:
     * Người dùng thứ hai kiểm tra ghế và phát hiện
     * ghế đã được đặt bởi giao dịch khác.
     *
     * Backend phải chặn giao dịch thứ hai.
     */
    public function testConcurrentBookingIsRejectedWhenSeatBecomesBooked(): void
    {
        // Người dùng thứ nhất: ghế còn trống
        $firstService = $this->createService(false);

        $firstResult = $firstService->processBooking(
            1,
            1,
            [1],
            'momo'
        );

        // Giao dịch thứ nhất thành công
        $this->assertSame('success', $firstResult['status']);
        $this->assertArrayHasKey('booking_id', $firstResult);

        // Người dùng thứ hai:
        // sau khi giao dịch thứ nhất đặt ghế,
        // trạng thái ghế được mô phỏng là "đã đặt".
        $secondService = $this->createService(true);

        $secondResult = $secondService->processBooking(
            2,
            1,
            [1],
            'momo'
        );

        // Giao dịch thứ hai phải bị từ chối
        $this->assertSame('error', $secondResult['status']);

        // Không tạo booking thứ hai
        $this->assertArrayNotHasKey('booking_id', $secondResult);
    }

    /**
     * Khôi phục trạng thái sau mỗi test.
     *
     * Các test sử dụng Mock nên không thay đổi
     * dữ liệu thật trong Database.
     */
}

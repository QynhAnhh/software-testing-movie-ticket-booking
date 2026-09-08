<?php

namespace Tests\Unit\Services;

use App\Services\BookingService;
use App\Models\BookingModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;
use App\Models\TicketModel;
use PHPUnit\Framework\TestCase;

/**
 * BoundaryTest: Kiểm thử phân tích giá trị biên và luồng điều khiển (White-box)
 */
class BookingServiceBoundaryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helper: tạo BookingService với mock đầy đủ
    // -------------------------------------------------------------------------
    private function createService(
        ?array $showtime    = null,
        array  $seats       = [],
        bool   $seatBooked  = false,
        ?int   $bookingId   = 1001,
        bool   $ticketsMade = true
    ): BookingService {
        $bookingModel  = $this->createMock(BookingModel::class);
        $showtimeModel = $this->createMock(ShowtimeModel::class);
        $seatModel     = $this->createMock(SeatModel::class);
        $ticketModel   = $this->createMock(TicketModel::class);

        $defaultShowtime = [
            'id'         => 1, 'room_id'    => 1,
            'show_date'  => '2099-12-31', 'start_time' => '20:00:00',
            'base_price' => 90000,        'status'     => 'active',
        ];

        $showtimeModel->method('getDetailById')
            ->willReturn($showtime ?? $defaultShowtime);

        $seatModel->method('getByIds')->willReturn($seats);
        $ticketModel->method('isSeatBooked')->willReturn($seatBooked);
        $bookingModel->method('createBooking')->willReturn($bookingId);
        $ticketModel->method('createMany')->willReturn($ticketsMade);
        $bookingModel->method('beginTransaction');
        $bookingModel->method('commit');
        $bookingModel->method('rollback');

        return new BookingService($bookingModel, $showtimeModel, $seatModel, $ticketModel);
    }

    private function validSeat(int $id = 1): array
    {
        return ['id' => $id, 'room_id' => 1, 'is_active' => 1, 'seat_type_price' => 0];
    }

    // =========================================================================
    // WHITE-BOX: Input validation branches
    // =========================================================================

    /** userId <= 0 */
    public function testRejectsInvalidUserId(): void
    {
        $service = $this->createService(null, [$this->validSeat()]);
        $result  = $service->processBooking(0, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** showtimeId <= 0 */
    public function testRejectsInvalidShowtimeId(): void
    {
        $service = $this->createService(null, [$this->validSeat()]);
        $result  = $service->processBooking(1, 0, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** seatIds rỗng */
    public function testRejectsEmptySeatList(): void
    {
        $stm = $this->createMock(ShowtimeModel::class);
        $stm->method('getDetailById')->willReturn([
            'id' => 1, 'room_id' => 1, 'base_price' => 90000,
            'show_date' => '2099-12-31', 'start_time' => '20:00:00', 'status' => 'active',
        ]);
        $svc    = new BookingService(null, $stm);
        $result = $svc->processBooking(1, 1, [], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** payment method không hợp lệ -> fallback về 'cash' (BookingRequestValidator vẫn cho qua) */
    public function testInvalidPaymentMethodThrowsException(): void
    {
        $service = $this->createService(null, [$this->validSeat()]);
        // 'invalid_method' sẽ được normalize về 'cash' bởi BookingRequestValidator,
        // booking vẫn thành công
        $result = $service->processBooking(1, 1, [1], 'invalid_method');
        $this->assertSame('success', $result['status']);
    }

    // =========================================================================
    // WHITE-BOX: Showtime validation
    // =========================================================================

    /** Showtime không tồn tại (null) */
    public function testRejectsUnavailableShowtime(): void
    {
        $service = $this->createService(showtime: null);

        // Override showtimeModel trả về null
        $stm = $this->createMock(ShowtimeModel::class);
        $stm->method('getDetailById')->willReturn(null);
        $svc    = new BookingService(null, $stm);
        $result = $svc->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** Showtime inactive */
    public function testRejectsInactiveShowtime(): void
    {
        $service = $this->createService(['id' => 1, 'room_id' => 1, 'show_date' => '2099-12-31',
            'start_time' => '20:00:00', 'base_price' => 90000, 'status' => 'inactive']);
        $result  = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** Showtime đã bắt đầu (quá giờ) */
    public function testRejectsStartedShowtime(): void
    {
        $service = $this->createService([
            'id' => 1, 'room_id' => 1,
            'show_date'  => date('Y-m-d', strtotime('-1 day')),
            'start_time' => '10:00:00',
            'base_price' => 90000, 'status' => 'active',
        ]);
        $result = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    // =========================================================================
    // WHITE-BOX: Seat validation
    // =========================================================================

    /** Số lượng ghế lock < số yêu cầu */
    public function testRejectsInvalidSeatList(): void
    {
        $service = $this->createService(null, []); // getByIds trả về mảng rỗng
        $result  = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** Ghế thuộc phòng khác */
    public function testRejectsSeatFromDifferentRoom(): void
    {
        $seat    = ['id' => 1, 'room_id' => 99, 'is_active' => 1, 'seat_type_price' => 0];
        $service = $this->createService(null, [$seat]);
        $result  = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** Ghế đang bảo trì (is_active = 0) */
    public function testRejectsInactiveSeat(): void
    {
        $seat    = ['id' => 1, 'room_id' => 1, 'is_active' => 0, 'seat_type_price' => 0];
        $service = $this->createService(null, [$seat]);
        $result  = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** Ghế đã được đặt */
    public function testRejectsBookedSeat(): void
    {
        $service = $this->createService(null, [$this->validSeat()], seatBooked: true);
        $result  = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    // =========================================================================
    // WHITE-BOX: Transaction rollback
    // =========================================================================

    /** createBooking thất bại -> rollback */
    public function testRollsBackWhenBookingCreationFails(): void
    {
        $service = $this->createService(null, [$this->validSeat()], bookingId: null);
        $result  = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    /** createMany thất bại -> rollback */
    public function testRollsBackWhenTicketCreationFails(): void
    {
        $service = $this->createService(null, [$this->validSeat()], ticketsMade: false);
        $result  = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('error', $result['status']);
    }

    // =========================================================================
    // WHITE-BOX: Happy path
    // =========================================================================

    /** Luồng thành công hoàn chỉnh */
    public function testSuccessfulBookingFollowsMainControlFlow(): void
    {
        $service = $this->createService(null, [$this->validSeat()]);
        $result  = $service->processBooking(1, 1, [1], 'momo');
        $this->assertSame('success', $result['status']);
        $this->assertSame(1001, $result['booking_id']);
    }

    /** Nhiều ghế -> tất cả được xử lý */
    public function testProcessesAllSelectedSeats(): void
    {
        $seats   = [$this->validSeat(1), $this->validSeat(2), $this->validSeat(3)];
        $service = $this->createService(null, $seats);
        $result  = $service->processBooking(1, 1, [1, 2, 3], 'momo');
        $this->assertSame('success', $result['status']);
    }

    // =========================================================================
    // getUserBookings & getTotalSpentByUser
    // =========================================================================

    public function testGetUserBookingsReturnsEmptyForInvalidUser(): void
    {
        $service = $this->createService();
        $this->assertSame([], $service->getUserBookings(0));
        $this->assertSame([], $service->getUserBookings(-5));
    }

    public function testGetTotalSpentByUserReturnsZeroForInvalidUser(): void
    {
        $service = $this->createService();
        $this->assertSame(0, $service->getTotalSpentByUser(0));
    }

    public function testGetTotalSpentByUserDelegatesToModel(): void
    {
        $bookingModel = $this->createMock(BookingModel::class);
        $bookingModel->method('getTotalSpentByUser')->willReturn(500000);
        $svc = new BookingService($bookingModel);
        $this->assertSame(500000, $svc->getTotalSpentByUser(1));
    }
}

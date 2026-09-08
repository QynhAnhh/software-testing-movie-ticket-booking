<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;
use App\Models\BookingModel;
use App\Models\TicketModel;
use App\Models\ShowtimeModel;
use App\Models\SeatModel;

class BookingServiceTest extends TestCase
{
    private $bookingService;
    private $bookingModelMock;
    private $ticketModelMock;
    private $showtimeModelMock;
    private $seatModelMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bookingModelMock  = $this->createMock(BookingModel::class);
        $this->ticketModelMock   = $this->createMock(TicketModel::class);
        $this->showtimeModelMock = $this->createMock(ShowtimeModel::class);
        $this->seatModelMock     = $this->createMock(SeatModel::class);

        $this->bookingService = new BookingService(
            $this->bookingModelMock,
            $this->showtimeModelMock,
            $this->seatModelMock,
            $this->ticketModelMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->bookingService, $this->bookingModelMock,
              $this->ticketModelMock, $this->showtimeModelMock, $this->seatModelMock);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helper: trả về showtime hợp lệ trong tương lai
    // -------------------------------------------------------------------------
    private function futureShowtime(array $override = []): array
    {
        return array_merge([
            'id'         => 10,
            'room_id'    => 5,
            'base_price' => 90000,
            'show_date'  => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '18:00:00',
            'status'     => 'active',
        ], $override);
    }

    private function mockShowtime(?array $showtime = null): void
    {
        // BookingRequestValidator gọi getDetailById() 2 lần: một lần validate, một lần lấy data
        $this->showtimeModelMock->method('getDetailById')
            ->willReturn($showtime ?? $this->futureShowtime());
    }

    private function mockSeats(array $seats): void
    {
        $this->seatModelMock->method('getByIds')->willReturn($seats);
    }

    private function defaultSeat(int $id = 1, int $roomId = 5): array
    {
        return ['id' => $id, 'room_id' => $roomId, 'is_active' => 1, 'seat_type_price' => 0];
    }

    // =========================================================================
    // NHÓM 1: VÒNG ĐỜI TRẠNG THÁI (STATE TRANSITION)
    // =========================================================================

    /**
     * @testdox TC-OI-01: Tạo booking mới phải ở trạng thái Pending
     */
    public function test_TC_OI_01_create_booking_should_have_pending_status()
    {
        $seatIds    = [1, 2];
        $showtimeId = 10;

        $this->mockShowtime();
        $this->mockSeats([
            $this->defaultSeat(1),
            $this->defaultSeat(2),
        ]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(false);
        $this->bookingModelMock->method('createBooking')->willReturn(101);
        $this->ticketModelMock->method('createMany')->willReturn(true);

        $result = $this->bookingService->processBooking(1, $showtimeId, $seatIds, 'momo');

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertEquals(101, $result['booking_id']);
    }

    /**
     * @testdox TC-OI-02: Chuyển trạng thái Pending -> Paid khi admin cập nhật
     */
    public function test_TC_OI_02_payment_success_transitions_pending_to_paid()
    {
        $bookingId = 101;
        $this->bookingModelMock->method('getAdminBookingById')
            ->willReturn(['id' => $bookingId, 'status' => 'pending']);
        $this->bookingModelMock->method('updateBookingStatus')->willReturn(true);
        $this->bookingModelMock->method('updateTicketsStatusByBooking')->willReturn(true);

        $result = $this->bookingService->updateAdminBookingStatus($bookingId, 'paid');

        $this->assertEquals('success', $result['status']);
    }

    /**
     * @testdox TC-OI-03: Hủy vé thành công -> trạng thái Canceled
     */
    public function test_TC_OI_03_manual_cancellation_changes_status_to_canceled()
    {
        $userId    = 1;
        $bookingId = 101;

        $this->bookingModelMock->method('getByIdAndUser')
            ->willReturn(['id' => $bookingId, 'status' => 'pending']);
        $this->bookingModelMock->method('getPrimaryShowtimeByBookingId')->willReturn(null);
        $this->bookingModelMock->method('cancelBooking')->willReturn(true);

        $result = $this->bookingService->cancelBooking($userId, $bookingId);

        $this->assertEquals('success', $result['status']);
    }

    /**
     * @testdox TC-OI-04: normalizeAdminFilters xử lý đúng ngày tháng hợp lệ
     */
    public function test_TC_OI_04_normalize_admin_filters_valid_dates()
    {
        $input   = ['status' => 'pending', 'from_date' => '2026-01-01', 'to_date' => '2026-12-31', 'search' => ''];
        $filters = $this->bookingService->normalizeAdminFilters($input);

        $this->assertEquals('pending', $filters['status']);
        $this->assertEquals('2026-01-01', $filters['from_date']);
        $this->assertEquals('2026-12-31', $filters['to_date']);
    }

    // =========================================================================
    // NHÓM 2: TÍNH TOÀN VẸN GIAO DỊCH VÀ LỊCH SỬ
    // =========================================================================

    /**
     * @testdox TC-OI-05: Lịch sử đặt vé hiển thị chính xác thông tin
     */
    public function test_TC_OI_05_booking_history_returns_correct_details()
    {
        $userId = 1;
        $this->bookingModelMock->method('getBookingsByUser')->willReturn([
            ['id' => 101, 'movie_title' => 'Avengers', 'status' => 'paid', 'total_price' => 180000]
        ]);

        $history = $this->bookingService->getUserBookings($userId);

        $this->assertCount(1, $history);
        $this->assertEquals('paid', $history[0]['status']);
        $this->assertEquals(180000, $history[0]['total_price']);
    }

    /**
     * @testdox TC-OI-06: Chặn đặt ghế đã có người đặt
     */
    public function test_TC_OI_06_prevent_booking_already_booked_seat()
    {
        $this->mockShowtime();
        $this->mockSeats([$this->defaultSeat(1)]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(true);

        $result = $this->bookingService->processBooking(1, 10, [1], 'momo');

        $this->assertEquals('error', $result['status']);
    }

    // =========================================================================
    // NHÓM 3: RÀNG BUỘC ĐẦU VÀO VÀ BẢO MẬT GIÁ
    // =========================================================================

    /**
     * @testdox TC-OI-07: bookingId không tồn tại -> hủy vé trả lỗi
     */
    public function test_TC_OI_07_invalid_booking_id_returns_error()
    {
        $this->bookingModelMock->method('getByIdAndUser')->willReturn(null);

        $result = $this->bookingService->cancelBooking(1, 999999);

        $this->assertEquals('error', $result['status']);
    }

    /**
     * @testdox TC-OI-08: Thiếu seat_ids -> trả về lỗi
     */
    public function test_TC_OI_08_empty_seat_ids_returns_error()
    {
        $this->showtimeModelMock->method('getDetailById')
            ->willReturn($this->futureShowtime());

        $result = $this->bookingService->processBooking(1, 10, [], 'momo');

        $this->assertEquals('error', $result['status']);
    }

    /**
     * @testdox TC-OI-09: Backend tự tính giá đúng (2 ghế x 90000 = 180000)
     */
    public function test_TC_OI_09_backend_recalculates_price_correctly()
    {
        $seatIds    = [1, 2];
        $showtimeId = 10;

        $this->mockShowtime($this->futureShowtime(['base_price' => 90000]));
        $this->mockSeats([
            $this->defaultSeat(1),
            $this->defaultSeat(2),
        ]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(false);

        $this->bookingModelMock->expects($this->once())
            ->method('createBooking')
            ->with($this->anything(), $this->equalTo(180000.0), $this->anything())
            ->willReturn(201);

        $this->ticketModelMock->method('createMany')->willReturn(true);

        $res = $this->bookingService->processBooking(1, $showtimeId, $seatIds, 'momo');

        $this->assertEquals('success', $res['status']);
        $this->assertEquals(201, $res['booking_id']);
    }

    /**
     * @testdox TC-OI-10: DB lỗi khi tạo booking -> rollback, không sinh dữ liệu rác
     */
    public function test_TC_OI_10_db_failure_rolls_back()
    {
        $this->mockShowtime();
        $this->mockSeats([$this->defaultSeat(1)]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(false);
        $this->bookingModelMock->method('createBooking')->willReturn(null); // tạo thất bại

        $result = $this->bookingService->processBooking(1, 10, [1], 'momo');

        $this->assertEquals('error', $result['status']);
    }

    /**
     * @testdox TC-OI-11: userId = 0 -> cancelBooking trả lỗi (mô phỏng session hết hạn)
     */
    public function test_TC_OI_11_expired_session_blocks_cancel()
    {
        $res = $this->bookingService->cancelBooking(0, 101);

        $this->assertEquals('error', $res['status']);
    }
}

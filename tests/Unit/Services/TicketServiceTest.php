<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\TicketService;

/**
 * Class TicketServiceTest
 * Đạt tiêu chuẩn 100% Statement và Branch-Decision Coverage cho tệp TicketService.php thực tế
 * Sử dụng dependency injection với Mock Models để cô lập 100% CSDL
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
        
        // Tạo các đối tượng giả lập (Mock Objects)
        $this->ticketModelMock = $this->createMock(\App\Models\TicketModel::class);
        $this->bookingModelMock = $this->createMock(\App\Models\BookingModel::class);
        $this->showtimeModelMock = $this->createMock(\App\Models\ShowtimeModel::class);
        $this->seatModelMock = $this->createMock(\App\Models\SeatModel::class);

        $this->ticketService = new TicketService(
            $this->ticketModelMock,
            $this->bookingModelMock,
            $this->showtimeModelMock,
            $this->seatModelMock
        );
    }

    protected function tearDown(): void
    {
        unset($this->ticketService);
        parent::tearDown();
    }

    /**
     * @dataProvider ticketCases
        * @testdox {testdox}
     */
        public function test_ticket_cases($testdox, $scenario, $data, $expectedStatus, $expectedMessage, $expectedTicketId = null)
    {
        $this->bookingModelMock->method('getById')->willReturn($scenario === 'invalid_booking' ? null : ['id' => 101]);
        $this->showtimeModelMock->method('findById')->willReturn($scenario === 'invalid_showtime' ? null : ['id' => 1, 'room_id' => 5]);
        $this->seatModelMock->method('findById')->willReturn($scenario === 'invalid_seat' ? null : ['id' => 10, 'room_id' => $scenario === 'room_mismatch' ? 6 : 5]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn($scenario === 'already_booked');

        if ($scenario === 'db_failure' || $scenario === 'success') {
            $this->ticketModelMock->expects($this->once())
                ->method('create')
                ->willReturn($scenario === 'success' ? 77 : false);
        }
        if ($scenario === 'db_failure') {
            $this->ticketModelMock->method('getError')->willReturn('Database Connection Timeout');
        }

        $result = $this->ticketService->addTicket($data);

        $this->assertSame($expectedStatus, $result['status']);
        $this->assertStringContainsString($expectedMessage, $result['message']);
        if ($expectedTicketId !== null) {
            $this->assertSame($expectedTicketId, $result['ticket_id']);
        }
    }

    public static function ticketCases()
    {
        return [
            'TC-TKT-01: Trả về lỗi khi hóa đơn đặt vé Booking ID không hợp lệ hoặc không tồn tại (Nhánh N2 -> N13)' => ['TC-TKT-01: Trả về lỗi khi hóa đơn đặt vé Booking ID không hợp lệ hoặc không tồn tại (Nhánh N2 -> N13)', 'invalid_booking', ['booking_id' => 999, 'showtime_id' => 1, 'seat_id' => 1, 'price' => 90000, 'status' => 'booked'], 'error', 'Booking không hợp lệ!'],
            'TC-TKT-02: Trả về lỗi khi suất chiếu Showtime ID không tồn tại (Nhánh N3 -> N13)' => ['TC-TKT-02: Trả về lỗi khi suất chiếu Showtime ID không tồn tại (Nhánh N3 -> N13)', 'invalid_showtime', ['booking_id' => 101, 'showtime_id' => 999, 'seat_id' => 1, 'price' => 90000, 'status' => 'booked'], 'error', 'Suất chiếu không hợp lệ!'],
            'TC-TKT-03: Trả về lỗi khi mã ghế Seat ID không tồn tại (Nhánh N4 -> N13)' => ['TC-TKT-03: Trả về lỗi khi mã ghế Seat ID không tồn tại (Nhánh N4 -> N13)', 'invalid_seat', ['booking_id' => 101, 'showtime_id' => 1, 'seat_id' => 999, 'price' => 90000, 'status' => 'booked'], 'error', 'Ghế không hợp lệ!'],
            'TC-TKT-04: Trả về lỗi khi phòng của ghế không khớp với phòng của suất chiếu (Nhánh N5 -> N13)' => ['TC-TKT-04: Trả về lỗi khi phòng của ghế không khớp với phòng của suất chiếu (Nhánh N5 -> N13)', 'room_mismatch', ['booking_id' => 101, 'showtime_id' => 1, 'seat_id' => 10, 'price' => 90000, 'status' => 'booked'], 'error', 'Ghế không thuộc phòng của suất chiếu này!'],
            'TC-TKT-05: Trả về lỗi khi ghế đã có người đặt trước trong cùng suất chiếu (Nhánh N6 -> N13 - Tranh chấp)' => ['TC-TKT-05: Trả về lỗi khi ghế đã có người đặt trước trong cùng suất chiếu (Nhánh N6 -> N13 - Tranh chấp)', 'already_booked', ['booking_id' => 101, 'showtime_id' => 1, 'seat_id' => 10, 'price' => 90000, 'status' => 'booked'], 'error', 'Ghế này đã được đặt trong suất chiếu!'],
            'TC-TKT-06: Trả về lỗi khi giá vé không hợp lệ - bằng 0 hoặc âm (Nhánh N7 -> N13 - Biên dưới)' => ['TC-TKT-06: Trả về lỗi khi giá vé không hợp lệ - bằng 0 hoặc âm (Nhánh N7 -> N13 - Biên dưới)', 'invalid_price', ['booking_id' => 101, 'showtime_id' => 1, 'seat_id' => 10, 'price' => -50, 'status' => 'booked'], 'error', 'Giá vé phải lớn hơn 0!'],
            'TC-TKT-07: Trả về lỗi khi trạng thái vé không nằm trong Whitelist (Nhánh N8 -> N13)' => ['TC-TKT-07: Trả về lỗi khi trạng thái vé không nằm trong Whitelist (Nhánh N8 -> N13)', 'invalid_status', ['booking_id' => 101, 'showtime_id' => 1, 'seat_id' => 10, 'price' => 90000, 'status' => 'pending'], 'error', 'Trạng thái vé không hợp lệ!'],
            'TC-TKT-08: Trả về lỗi hệ thống khi cơ sở dữ liệu không thể lưu vé (Nhánh N10 -> N12)' => ['TC-TKT-08: Trả về lỗi hệ thống khi cơ sở dữ liệu không thể lưu vé (Nhánh N10 -> N12)', 'db_failure', ['booking_id' => 101, 'showtime_id' => 1, 'seat_id' => 10, 'price' => 90000, 'status' => 'booked'], 'error', 'Lỗi khi thêm vé: Database Connection Timeout'],
            'TC-TKT-09: Thêm vé thành công trọn vẹn và trả về ID vé mới (Nhánh N10 -> N11 - Happy Path)' => ['TC-TKT-09: Thêm vé thành công trọn vẹn và trả về ID vé mới (Nhánh N10 -> N11 - Happy Path)', 'success', ['booking_id' => 101, 'showtime_id' => 1, 'seat_id' => 10, 'price' => 90000, 'status' => 'booked'], 'success', 'Thêm vé thành công!', 77],
        ];
    }
}

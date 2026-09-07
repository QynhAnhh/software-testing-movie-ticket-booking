<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\TicketService;

/**
 * Class TicketServiceTest
 * Đạt tiêu chuẩn 100% Statement và Branch-Decision Coverage cho tệp TicketService.php thực tế
 * Sử dụng PHP Reflection bọc Mock Models để cô lập 100% CSDL
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

        // Khởi tạo đối tượng thực tế
        $this->ticketService = new TicketService();

        // Sử dụng PHP Reflection để vượt qua việc 'new' cứng trong Constructor, tiêm Mock Objects vào thuộc tính private
        $reflection = new \ReflectionClass($this->ticketService);
        
        $propModel = $reflection->getProperty('model');
        $propModel->setAccessible(true);
        $propModel->setValue($this->ticketService, $this->ticketModelMock);

        $propBooking = $reflection->getProperty('bookingModel');
        $propBooking->setAccessible(true);
        $propBooking->setValue($this->ticketService, $this->bookingModelMock);

        $propShowtime = $reflection->getProperty('showtimeModel');
        $propShowtime->setAccessible(true);
        $propShowtime->setValue($this->ticketService, $this->showtimeModelMock);

        $propSeat = $reflection->getProperty('seatModel');
        $propSeat->setAccessible(true);
        $propSeat->setValue($this->ticketService, $this->seatModelMock);
    }

    protected function tearDown(): void
    {
        unset($this->ticketService);
        parent::tearDown();
    }

    /**
     * @testdox TC-TKT-01: Trả về lỗi khi hóa đơn đặt vé Booking ID không hợp lệ hoặc không tồn tại (Nhánh N2 -> N13)
     */
    public function test_TC_TKT_01_invalid_booking_id_returns_error()
    {
        $data = [
            'booking_id' => 999, // không hợp lệ
            'showtime_id' => 1,
            'seat_id' => 1,
            'price' => 90000,
            'status' => 'booked'
        ];

        // Giả lập bookingModel trả về null
        $this->bookingModelMock->expects($this->once())
            ->method('getById')
            ->with(999)
            ->willReturn(null);

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Booking không hợp lệ!', $result['message']);
    }

    /**
     * @testdox TC-TKT-02: Trả về lỗi khi suất chiếu Showtime ID không tồn tại (Nhánh N3 -> N13)
     */
    public function test_TC_TKT_02_invalid_showtime_id_returns_error()
    {
        $data = [
            'booking_id' => 101,
            'showtime_id' => 999, // không hợp lệ
            'seat_id' => 1,
            'price' => 90000,
            'status' => 'booked'
        ];

        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        $this->showtimeModelMock->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Suất chiếu không hợp lệ!', $result['message']);
    }

    /**
     * @testdox TC-TKT-03: Trả về lỗi khi mã ghế Seat ID không tồn tại (Nhánh N4 -> N13)
     */
    public function test_TC_TKT_03_invalid_seat_id_returns_error()
    {
        $data = [
            'booking_id' => 101,
            'showtime_id' => 1,
            'seat_id' => 999, // không hợp lệ
            'price' => 90000,
            'status' => 'booked'
        ];

        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        $this->showtimeModelMock->method('findById')->willReturn(['id' => 1, 'room_id' => 5]);
        $this->seatModelMock->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Ghế không hợp lệ!', $result['message']);
    }

    /**
     * @testdox TC-TKT-04: Trả về lỗi khi phòng của ghế không khớp với phòng của suất chiếu (Nhánh N5 -> N13)
     */
    public function test_TC_TKT_04_room_mismatch_returns_error()
    {
        $data = [
            'booking_id' => 101,
            'showtime_id' => 1,
            'seat_id' => 10,
            'price' => 90000,
            'status' => 'booked'
        ];

        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        // Suất chiếu ở phòng 5, nhưng ghế ở phòng 6
        $this->showtimeModelMock->method('findById')->willReturn(['id' => 1, 'room_id' => 5]);
        $this->seatModelMock->method('findById')->willReturn(['id' => 10, 'room_id' => 6]);

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Ghế không thuộc phòng của suất chiếu này!', $result['message']);
    }

    /**
     * @testdox TC-TKT-05: Trả về lỗi khi ghế đã có người đặt trước trong cùng suất chiếu (Nhánh N6 -> N13 - Tranh chấp)
     */
    public function test_TC_TKT_05_seat_already_booked_returns_error()
    {
        $data = [
            'booking_id' => 101,
            'showtime_id' => 1,
            'seat_id' => 10,
            'price' => 90000,
            'status' => 'booked'
        ];

        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        $this->showtimeModelMock->method('findById')->willReturn(['id' => 1, 'room_id' => 5]);
        $this->seatModelMock->method('findById')->willReturn(['id' => 10, 'room_id' => 5]);
        
        // Giả lập ghế đã bán
        $this->ticketModelMock->expects($this->once())
            ->method('isSeatBooked')
            ->with(1, 10, null)
            ->willReturn(true);

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Ghế này đã được đặt trong suất chiếu!', $result['message']);
    }

    /**
     * @testdox TC-TKT-06: Trả về lỗi khi giá vé không hợp lệ - bằng 0 hoặc âm (Nhánh N7 -> N13 - Biên dưới)
     */
    public function test_TC_TKT_06_invalid_price_returns_error()
    {
        $data = [
            'booking_id' => 101,
            'showtime_id' => 1,
            'seat_id' => 10,
            'price' => -50, // âm
            'status' => 'booked'
        ];

        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        $this->showtimeModelMock->method('findById')->willReturn(['id' => 1, 'room_id' => 5]);
        $this->seatModelMock->method('findById')->willReturn(['id' => 10, 'room_id' => 5]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(false);

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Giá vé phải lớn hơn 0!', $result['message']);
    }

    /**
     * @testdox TC-TKT-07: Trả về lỗi khi trạng thái vé không nằm trong Whitelist (Nhánh N8 -> N13)
     */
    public function test_TC_TKT_07_invalid_status_returns_error()
    {
        $data = [
            'booking_id' => 101,
            'showtime_id' => 1,
            'seat_id' => 10,
            'price' => 90000,
            'status' => 'pending' // không hợp lệ
        ];

        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        $this->showtimeModelMock->method('findById')->willReturn(['id' => 1, 'room_id' => 5]);
        $this->seatModelMock->method('findById')->willReturn(['id' => 10, 'room_id' => 5]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(false);

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Trạng thái vé không hợp lệ!', $result['message']);
    }

    /**
     * @testdox TC-TKT-08: Trả về lỗi hệ thống khi cơ sở dữ liệu không thể lưu vé (Nhánh N10 -> N12)
     */
    public function test_TC_TKT_08_db_save_failure_returns_error()
    {
        $data = [
            'booking_id' => 101,
            'showtime_id' => 1,
            'seat_id' => 10,
            'price' => 90000,
            'status' => 'booked'
        ];

        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        $this->showtimeModelMock->method('findById')->willReturn(['id' => 1, 'room_id' => 5]);
        $this->seatModelMock->method('findById')->willReturn(['id' => 10, 'room_id' => 5]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(false);
        
        // Giả lập lưu vé lỗi
        $this->ticketModelMock->expects($this->once())
            ->method('create')
            ->willReturn(false);
        $this->ticketModelMock->method('getError')->willReturn('Database Connection Timeout');

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Lỗi khi thêm vé: Database Connection Timeout', $result['message']);
    }

    /**
     * @testdox TC-TKT-09: Thêm vé thành công trọn vẹn và trả về ID vé mới (Nhánh N10 -> N11 - Happy Path)
     */
    public function test_TC_TKT_09_add_ticket_success()
    {
        $data = [
            'booking_id' => 101,
            'showtime_id' => 1,
            'seat_id' => 10,
            'price' => 90000,
            'status' => 'booked'
        ];

        $this->bookingModelMock->method('getById')->willReturn(['id' => 101]);
        $this->showtimeModelMock->method('findById')->willReturn(['id' => 1, 'room_id' => 5]);
        $this->seatModelMock->method('findById')->willReturn(['id' => 10, 'room_id' => 5]);
        $this->ticketModelMock->method('isSeatBooked')->willReturn(false);
        
        // Giả lập lưu vé thành công, trả về ID = 77
        $this->ticketModelMock->expects($this->once())
            ->method('create')
            ->willReturn(77);

        $result = $this->ticketService->addTicket($data);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Thêm vé thành công!', $result['message']);
        $this->assertEquals(77, $result['ticket_id']);
    }
}
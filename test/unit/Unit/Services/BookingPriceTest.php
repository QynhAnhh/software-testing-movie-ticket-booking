<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;
use App\Config\Database;

class BookingPriceTest extends TestCase
{
    private $bookingService;
    private $conn;

    protected function setUp(): void
    {
        $this->bookingService = new BookingService();
        $this->conn = Database::getConnection();
        // TODO: Thiết lập dữ liệu ảo (Dummy data) nếu cần
    }

    protected function tearDown(): void
    {
        // TODO: Xóa dữ liệu ảo
    }

    /**
     * @test
     * Chiến lược: Bao phủ nhánh (Branch Coverage)
     * Thành viên 2 (Trúc): Bắt đầu viết test cases cho phần áp dụng Voucher và tính giá tiền ở đây.
     */
    public function testExample()
    {
        $this->markTestIncomplete('Test case này chưa được implement.');
    }
}

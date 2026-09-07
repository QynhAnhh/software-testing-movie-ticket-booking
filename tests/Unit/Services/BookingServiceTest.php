<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;
use App\Config\Database;

class BookingServiceTest extends TestCase
{
    private $bookingService;
    private $conn;

    protected function setUp(): void
    {
        $this->bookingService = new BookingService();
        $this->conn = Database::getConnection();
    }

    /**
     * @test
     * Chiến lược: Bao phủ nhánh - điều kiện (Branch-Condition Coverage)
     * Thành viên 2: Bắt đầu viết test cases ở đây.
     */
    public function testExample()
    {
        $this->markTestIncomplete('Test case này chưa được implement.');
    }
}
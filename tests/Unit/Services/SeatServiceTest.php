<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\SeatService;
use App\Config\Database;

class SeatServiceTest extends TestCase
{
    private $seatService;
    private $conn;

    protected function setUp(): void
    {
        $this->seatService = new SeatService();
        $this->conn = Database::getConnection();
        // TODO: Thiết lập dữ liệu ảo (Dummy data) nếu cần
    }

    protected function tearDown(): void
    {
        // TODO: Xóa dữ liệu ảo
    }

    /**
     * @test
     * Chiến lược: Bao phủ nhánh (Branch / Decision Coverage)
     * Thành viên 4: Bắt đầu viết test cases ở đây.
     */
    public function testExample()
    {
        $this->markTestIncomplete('Test case này chưa được implement.');
    }
}

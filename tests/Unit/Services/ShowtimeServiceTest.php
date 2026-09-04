<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ShowtimeService;
use App\Config\Database;

class ShowtimeServiceTest extends TestCase
{
    private $showtimeService;
    private $conn;

    protected function setUp(): void
    {
        $this->showtimeService = new ShowtimeService();
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
     * Thành viên 3: Bắt đầu viết test cases ở đây.
     */
    public function testExample()
    {
        $this->markTestIncomplete('Test case này chưa được implement.');
    }
}

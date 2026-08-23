<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\MovieService;
use App\Config\Database;

class MovieServiceTest extends TestCase
{
    private $movieService;
    private $conn;

    protected function setUp(): void
    {
        $this->movieService = new MovieService();
        $this->conn = Database::getConnection();
        // TODO: Thiết lập dữ liệu ảo (Dummy data) nếu cần
    }

    protected function tearDown(): void
    {
        // TODO: Xóa dữ liệu ảo
    }

    /**
     * @test
     * Chiến lược: Bao phủ câu lệnh & nhánh (Statement / Branch Coverage)
     * Thành viên 5: Bắt đầu viết test cases ở đây.
     */
    public function testExample()
    {
        $this->markTestIncomplete('Test case này chưa được implement.');
    }
}

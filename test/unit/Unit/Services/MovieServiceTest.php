<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\MovieService;
use App\Models\MovieModel;

class MovieServiceTest extends TestCase
{
    private MovieService $service;
    private $movieModel;
    private array $temporaryFiles = [];

    protected function setUp(): void
{
    parent::setUp();

    // Mock MovieModel để Unit Test không tác động Database thật.
    $this->movieModel = $this->getMockBuilder(MovieModel::class)
        ->disableOriginalConstructor()
        ->onlyMethods([
            'insertMovie',
            'insertMovieGenres',
            'getMovieByIdWithGenres',
            'updateMovie',
            'deleteMovieGenres',
            'deleteMovie',
            'getError',
            'hasBookedTickets',
            'hasShowtimes',
        ])
        ->getMock();

    // Inject mock trực tiếp qua constructor.
    $this->service = new MovieService($this->movieModel);
}

    protected function tearDown(): void
    {
        // Xóa các file giả được tạo trong quá trình test.
        foreach ($this->temporaryFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $this->temporaryFiles = [];

        unset($this->service, $this->movieModel);

        parent::tearDown();
    }

    /**
     * @dataProvider invalidMovieDataProvider
     */
    public function testMovieInputValidation(
        string $testCaseId,
        array $data,
        ?array $posterFile,
        string $expectedStatus,
        string $expectedMessage
    ): void {
        // Nếu validation bị thiếu và Service cố lưu xuống DB,
        // mock sẽ giả lập lưu thành công để testcase FAIL đúng thực tế.
        $this->movieModel
            ->method('insertMovie')
            ->willReturn(999);

        $this->movieModel
            ->method('insertMovieGenres');

        $result = $this->service->addMovie(
            $data,
            [1],
            $posterFile
        );

        $this->assertSame(
            $expectedStatus,
            $result['status'],
            $testCaseId . ': Sai trạng thái trả về.'
        );

        $this->assertStringContainsString(
            $expectedMessage,
            $result['message'],
            $testCaseId . ': Nội dung validation chưa đúng yêu cầu.'
        );
    }

    public function invalidMovieDataProvider(): array
    {
        $validData = [
            'title' => 'Avatar Test',
            'description' => 'Mô tả phim hợp lệ',
            'director' => 'Test Director',
            'cast' => 'Test Actor',
            'age_restriction' => 0,
            'country' => 'Việt Nam',
            'duration' => 120,
            'screening_date' => '2026-08-20',
            'trailer_url' => '',
            'status' => 'now_showing',
        ];

        return [
            'TC-DT-01 - thiếu tiêu đề' => [
                'TC-DT-01',
                array_merge($validData, [
                    'title' => '',
                ]),
                null,
                'error',
                'bắt buộc',
            ],

            'TC-DT-02 - thời lượng âm' => [
                'TC-DT-02',
                array_merge($validData, [
                    'duration' => -120,
                ]),
                null,
                'error',
                'thời lượng',
            ],

            'TC-DT-03 - thời lượng bằng 0' => [
                'TC-DT-03',
                array_merge($validData, [
                    'duration' => 0,
                ]),
                null,
                'error',
                'thời lượng',
            ],

            'TC-DT-04 - ngày không hợp lệ' => [
                'TC-DT-04',
                array_merge($validData, [
                    'screening_date' => '2024-02-31',
                ]),
                null,
                'error',
                'ngày',
            ],

            'TC-DT-10 - mô tả vượt 5000 ký tự' => [
                'TC-DT-10',
                array_merge($validData, [
                    'description' => str_repeat('a', 5001),
                ]),
                null,
                'error',
                '5000',
            ],
        ];
    }

    public function testTcDt05RejectsInvalidPosterExtension(): void
    {
        $data = $this->validMovieData();

        $posterFile = [
            'name' => 'document.pdf',
            'tmp_name' => __FILE__,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
        ];

        $result = $this->service->addMovie(
            $data,
            [1],
            $posterFile
        );

        $this->assertSame('error', $result['status']);

        $this->assertStringContainsString(
            'JPG hoặc PNG',
            $result['message']
        );
    }

    public function testTcDt06RejectsPosterLargerThan5Mb(): void
    {
        $data = $this->validMovieData();

        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'kan60_poster_'
        );

        // Tạo file giả 6MB.
        file_put_contents(
            $temporaryFile,
            str_repeat('A', 6 * 1024 * 1024)
        );

        $this->temporaryFiles[] = $temporaryFile;

        $posterFile = [
            'name' => 'large_image.jpg',
            'tmp_name' => $temporaryFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($temporaryFile),
        ];

        $result = $this->service->addMovie(
            $data,
            [1],
            $posterFile
        );

        $this->assertSame('error', $result['status']);

        // Theo TC-DT-06, Service phải báo lỗi dung lượng.
        $this->assertStringContainsString(
            'dung lượng',
            mb_strtolower($result['message'])
        );
    }

    public function testTcDt07HandlesSpecialCharactersSafely(): void
    {
        $data = $this->validMovieData();
        $data['title'] = 'Movie@#$%^&*';

        $this->movieModel
            ->expects($this->once())
            ->method('insertMovie')
            ->willReturn(1001);

        $this->movieModel
            ->expects($this->once())
            ->method('insertMovieGenres');

        $result = $this->service->addMovie(
            $data,
            [1],
            null
        );

        $this->assertSame('success', $result['status']);
    }
    
/**
 * @testdox TC-DT-08 - Chặn xóa phim đang có suất chiếu hoạt động
 */
public function testTcDt08PreventsDeletingMovieWithActiveShowtime(): void
{
    $movieId = 101;

    /*
     * Giả lập nghiệp vụ:
     * Phim đang có suất chiếu hoạt động.
     *
     * Theo TC-DT-08, Service phải phát hiện điều này
     * và KHÔNG được gọi thao tác xóa phim.
     */
    $this->movieModel
        ->expects($this->never())
        ->method('deleteMovie');

    $result = $this->service->deleteMovie($movieId);

    $this->assertSame(
        'error',
        $result['status'],
        'TC-DT-08: Phải chặn xóa phim đang có suất chiếu hoạt động.'
    );

    $this->assertStringContainsString(
        'suất chiếu',
        mb_strtolower($result['message']),
        'TC-DT-08: Phải thông báo phim đang được sử dụng bởi suất chiếu.'
    );
}

/**
 * @testdox TC-DT-09 - Chặn xóa phim đã có khách hàng đặt vé
 */
public function testTcDt09PreventsDeletingMovieWithExistingBooking(): void
{
    $movieId = 102;

    /*
     * Giả lập nghiệp vụ:
     * Phim đã có khách hàng đặt vé.
     *
     * Theo TC-DT-09, Service phải bảo vệ dữ liệu liên quan
     * và KHÔNG được gọi thao tác xóa phim.
     */
    $this->movieModel
        ->expects($this->never())
        ->method('deleteMovie');

    $result = $this->service->deleteMovie($movieId);

    $this->assertSame(
        'error',
        $result['status'],
        'TC-DT-09: Phải chặn xóa phim đã có khách hàng đặt vé.'
    );

    $this->assertStringContainsString(
        'vé',
        mb_strtolower($result['message']),
        'TC-DT-09: Phải thông báo phim đã có dữ liệu đặt vé.'
    );
}
    private function validMovieData(): array
    {
        return [
            'title' => 'Avatar Test',
            'description' => 'Mô tả phim hợp lệ',
            'director' => 'Test Director',
            'cast' => 'Test Actor',
            'age_restriction' => 0,
            'country' => 'Việt Nam',
            'duration' => 120,
            'screening_date' => '2026-08-20',
            'trailer_url' => '',
            'status' => 'now_showing',
        ];
    }
}


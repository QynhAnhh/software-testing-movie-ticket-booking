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
'getAllMoviesWithGenres',
'getNowShowingMovies',
'getComingMovies',
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

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidMovieDataProvider')]
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
    mb_strtolower($expectedMessage),
    mb_strtolower($result['message']),
    $testCaseId . ': Nội dung validation chưa đúng yêu cầu.'
);
    }

    public static function invalidMovieDataProvider(): array
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

    $this->movieModel
    ->method('hasBookedTickets')
    ->with($movieId)
    ->willReturn(false);

$this->movieModel
    ->method('hasShowtimes')
    ->with($movieId)
    ->willReturn(true);

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


    $this->movieModel
    ->method('hasBookedTickets')
    ->with($movieId)
    ->willReturn(true);
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

public function testUpdateMovieSuccessfully(): void
{
    $data = $this->validMovieData();

    $this->movieModel
        ->expects($this->once())
        ->method('getMovieByIdWithGenres')
        ->with(10)
        ->willReturn(['id' => 10, 'poster' => 'images/movies/old.jpg']);

    $this->movieModel
        ->expects($this->once())
        ->method('updateMovie')
        ->with(10, $this->callback(function ($updatedData) {
            return $updatedData['poster'] === 'images/movies/old.jpg';
        }))
        ->willReturn(true);

    $this->movieModel
        ->expects($this->once())
        ->method('deleteMovieGenres')
        ->with(10);

    $this->movieModel
        ->expects($this->once())
        ->method('insertMovieGenres')
        ->with(10, [1, 2]);

    $result = $this->service->updateMovie(10, $data, [1, 2]);

    $this->assertSame('success', $result['status']);
}


public function testUpdateMovieReturnsErrorWhenMovieDoesNotExist(): void
{
    $this->movieModel
        ->expects($this->once())
        ->method('getMovieByIdWithGenres')
        ->with(999)
        ->willReturn(null);

    $result = $this->service->updateMovie(
        999,
        $this->validMovieData(),
        [1]
    );

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('không tồn tại', $result['message']);
}


public function testUpdateMovieReturnsErrorWhenModelFails(): void
{
    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->willReturn([
            'id' => 10,
            'poster' => 'images/movies/old.jpg'
        ]);

    $this->movieModel
        ->method('updateMovie')
        ->willReturn(false);

    $this->movieModel
        ->method('getError')
        ->willReturn('database error');

    $result = $this->service->updateMovie(
        10,
        $this->validMovieData(),
        [1]
    );

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('database error', $result['message']);
}


public function testAddMovieReturnsErrorWhenInsertFails(): void
{
    $this->movieModel
        ->method('insertMovie')
        ->willReturn(false);

    $this->movieModel
        ->method('getError')
        ->willReturn('insert failed');

    $result = $this->service->addMovie(
        $this->validMovieData(),
        [1]
    );

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('insert failed', $result['message']);
}


public function testDeleteMovieRejectsInvalidId(): void
{
    $result = $this->service->deleteMovie(0);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('ID phim', $result['message']);
}


public function testDeleteMovieRejectsMovieWithBookedTickets(): void
{
    $this->movieModel
        ->expects($this->once())
        ->method('hasBookedTickets')
        ->with(10)
        ->willReturn(true);

    $this->movieModel
        ->expects($this->never())
        ->method('deleteMovie');

    $result = $this->service->deleteMovie(10);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('vé', $result['message']);
}


public function testDeleteMovieRejectsMovieWithShowtimes(): void
{
    $this->movieModel
        ->method('hasBookedTickets')
        ->willReturn(false);

    $this->movieModel
        ->method('hasShowtimes')
        ->willReturn(true);

    $this->movieModel
        ->expects($this->never())
        ->method('deleteMovie');

    $result = $this->service->deleteMovie(10);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('suất chiếu', $result['message']);
}


public function testDeleteMovieSuccessfully(): void
{
    $this->movieModel
        ->method('hasBookedTickets')
        ->willReturn(false);

    $this->movieModel
        ->method('hasShowtimes')
        ->willReturn(false);

    $this->movieModel
        ->expects($this->once())
        ->method('deleteMovie')
        ->with(10)
        ->willReturn(true);

    $result = $this->service->deleteMovie(10);

    $this->assertSame('success', $result['status']);
}


public function testDeleteMovieReturnsErrorWhenDeleteFails(): void
{
    $this->movieModel
        ->method('hasBookedTickets')
        ->willReturn(false);

    $this->movieModel
        ->method('hasShowtimes')
        ->willReturn(false);

    $this->movieModel
        ->method('deleteMovie')
        ->willReturn(false);

    $result = $this->service->deleteMovie(10);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('thất bại', $result['message']);
}


public function testGetMovieByIdRejectsInvalidId(): void
{
    $this->movieModel
        ->expects($this->never())
        ->method('getMovieByIdWithGenres');

    $this->assertNull($this->service->getMovieById(0));
}


public function testGetMovieByIdReturnsMovie(): void
{
    $movie = ['id' => 10, 'title' => 'Avatar'];

    $this->movieModel
        ->expects($this->once())
        ->method('getMovieByIdWithGenres')
        ->with(10)
        ->willReturn($movie);

    $this->assertSame(
        $movie,
        $this->service->getMovieById(10)
    );
}


public function testGetAllMovies(): void
{
    $movies = [
        ['id' => 1],
        ['id' => 2],
    ];

    $this->movieModel
        ->expects($this->once())
        ->method('getAllMoviesWithGenres')
        ->willReturn($movies);

    $this->assertSame(
        $movies,
        $this->service->getAllMovies()
    );
}


public function testGetNowShowingMovies(): void
{
    $movies = [['id' => 1]];

    $this->movieModel
        ->expects($this->once())
        ->method('getNowShowingMovies')
        ->with(5)
        ->willReturn($movies);

    $this->assertSame(
        $movies,
        $this->service->getNowShowingMovies(5)
    );
}


public function testGetComingMovies(): void
{
    $movies = [['id' => 2]];

    $this->movieModel
        ->expects($this->once())
        ->method('getComingMovies')
        ->with(3)
        ->willReturn($movies);

    $this->assertSame(
        $movies,
        $this->service->getComingMovies(3)
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

public function testUpdateMovieReturnsErrorWhenMovieNotFound(): void
{
    $data = [
        'title' => 'Test Movie',
        'country' => 'Vietnam',
        'duration' => 120,
        'screening_date' => '2026-12-20',
        'description' => 'Test'
    ];

    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->with(999)
        ->willReturn(null);

    $result = $this->service->updateMovie(999, $data, [1]);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('không tồn tại', $result['message']);
}

public function testUpdateMovieRejectsTooLongDescription(): void
{
    $data = [
        'title' => 'Test Movie',
        'country' => 'Vietnam',
        'duration' => 120,
        'screening_date' => '2026-12-20',
        'description' => str_repeat('a', 5001)
    ];

    $result = $this->service->updateMovie(10, $data, [1]);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('5000', $result['message']);
}

public function testUpdateMovieReturnsErrorWhenDatabaseUpdateFails(): void
{
    $data = [
        'title' => 'Test Movie',
        'country' => 'Vietnam',
        'duration' => 120,
        'screening_date' => '2026-12-20',
        'description' => 'Test'
    ];

    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->with(10)
        ->willReturn([
            'id' => 10,
            'poster' => 'old.jpg'
        ]);

    $this->movieModel
        ->method('updateMovie')
        ->willReturn(false);

    $this->movieModel
        ->method('getError')
        ->willReturn('Update database failed');

    $result = $this->service->updateMovie(10, $data, [1]);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString(
        'Update database failed',
        $result['message']
    );
}

public function testAddMovieRejectsPosterIniSizeError(): void
{
    $data = [
        'title' => 'Test Movie',
        'country' => 'Vietnam',
        'duration' => 120,
        'screening_date' => '2026-12-20',
        'description' => 'Test'
    ];

    $poster = [
        'error' => UPLOAD_ERR_INI_SIZE,
        'name' => 'poster.jpg',
        'size' => 6000000,
        'tmp_name' => ''
    ];

    $result = $this->service->addMovie($data, [1], $poster);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('5MB', $result['message']);
}

public function testAddMovieRejectsGenericPosterUploadError(): void
{
    $data = [
        'title' => 'Test Movie',
        'country' => 'Vietnam',
        'duration' => 120,
        'screening_date' => '2026-12-20',
        'description' => 'Test'
    ];

    $poster = [
        'error' => UPLOAD_ERR_PARTIAL,
        'name' => 'poster.jpg',
        'size' => 1000,
        'tmp_name' => ''
    ];

    $result = $this->service->addMovie($data, [1], $poster);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString(
        'Upload poster không thành công',
        $result['message']
    );
}

public function testAddMovieRejectsPosterLargerThanFiveMb(): void
{
    $data = [
        'title' => 'Test Movie',
        'country' => 'Vietnam',
        'duration' => 120,
        'screening_date' => '2026-12-20',
        'description' => 'Test'
    ];

    $poster = [
        'error' => UPLOAD_ERR_OK,
        'name' => 'poster.jpg',
        'size' => (5 * 1024 * 1024) + 1,
        'tmp_name' => ''
    ];

    $result = $this->service->addMovie($data, [1], $poster);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString('5MB', $result['message']);
}

public function testAddMovieValidPosterButMoveUploadFails(): void
{
    $data = [
        'title' => 'Test Movie',
        'country' => 'Vietnam',
        'duration' => 120,
        'screening_date' => '2026-12-20',
        'description' => 'Test'
    ];

    $poster = [
        'error' => UPLOAD_ERR_OK,
        'name' => 'poster.jpg',
        'size' => 1024,
        'tmp_name' => __DIR__ . '/fake-poster.jpg'
    ];

    $result = $this->service->addMovie($data, [1], $poster);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString(
        'Không thể lưu file poster',
        $result['message']
    );
}

public function testUpdateMovieRejectsInvalidRequiredData(): void
{
    $data = $this->validMovieData();
    $data['title'] = '';

    $this->movieModel
        ->expects($this->never())
        ->method('getMovieByIdWithGenres');

    $result = $this->service->updateMovie(10, $data, [1]);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString(
        'Dữ liệu cập nhật không hợp lệ',
        $result['message']
    );
}

public function testUpdateMovieRejectsInvalidScreeningDate(): void
{
    $data = $this->validMovieData();
    $data['screening_date'] = '2026-02-31';

    $this->movieModel
        ->expects($this->never())
        ->method('getMovieByIdWithGenres');

    $result = $this->service->updateMovie(10, $data, [1]);

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString(
        'Ngày khởi chiếu không hợp lệ',
        $result['message']
    );
}

public function testUpdateMovieReturnsPosterValidationError(): void
{
    $data = $this->validMovieData();

    $this->movieModel
        ->method('getMovieByIdWithGenres')
        ->with(10)
        ->willReturn([
            'id' => 10,
            'poster' => 'images/movies/old.jpg'
        ]);

    $poster = [
        'error' => UPLOAD_ERR_OK,
        'name' => 'document.pdf',
        'size' => 1024,
        'tmp_name' => __FILE__
    ];

    $this->movieModel
        ->expects($this->never())
        ->method('updateMovie');

    $result = $this->service->updateMovie(
        10,
        $data,
        [1],
        $poster
    );

    $this->assertSame('error', $result['status']);
    $this->assertStringContainsString(
        'JPG hoặc PNG',
        $result['message']
    );
}
public function testAddMovieRejectsEmptyScreeningDate(): void
{
    $data = $this->validMovieData();
    $data['screening_date'] = '';

    $result = $this->service->addMovie($data, [1]);

    $this->assertSame('error', $result['status']);
}


}


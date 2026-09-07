<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\ProfileService;
use App\Config\Database;

class ProfileServiceTest extends TestCase
{
    /** @var ProfileService */
    private $profileService;
    /** @var \mysqli */
    private $conn;
    /** @var int */
    private $dummyUserId;

    protected function setUp(): void
    {
        $this->profileService = new ProfileService();
        $this->conn = Database::getConnection();

        $this->cleanUpDummyData();

        // Create dummy users
        $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
        $sql1 = "INSERT INTO users (first_name, last_name, email, phone, password, role)
                VALUES ('User', 'One', 'user1@test.com', '0911111111', '$passwordHash', 'user')";
        mysqli_query($this->conn, $sql1);
        $this->dummyUserId = mysqli_insert_id($this->conn);

        $sql2 = "INSERT INTO users (first_name, last_name, email, phone, password, role)
                VALUES ('User', 'Two', 'user2@test.com', '0922222222', '$passwordHash', 'user')";
        mysqli_query($this->conn, $sql2);
    }

    protected function tearDown(): void
    {

        $this->cleanUpDummyData();
        if (isset($_SESSION['user'])) {
            unset($_SESSION['user']);
        }
    }

    private function cleanUpDummyData()
    {
        mysqli_query($this->conn, "DELETE FROM users WHERE email LIKE '%@test.com'");
        mysqli_query($this->conn, "DELETE FROM users WHERE phone IN ('0911111111', '0922222222', '0933333333')");
    }

    // --- TEST GET PROFILE ---
    public function testGetProfileInvalidId()
    {
        $result = $this->profileService->getProfile(0);
        $this->assertNull($result);
    }

    public function testGetProfileSuccess()
    {
        $result = $this->profileService->getProfile($this->dummyUserId);
        $this->assertNotNull($result);
        $this->assertEquals('user1@test.com', $result['email']);
    }

    public function testGetProfileOverviewInvalidId()
    {
        $result = $this->profileService->getProfileOverview(-1);
        $this->assertNull($result['user']);
        $this->assertEquals(0, $result['total_tickets']);
        $this->assertEquals(0, $result['total_spent']);
    }

    public function testGetProfileOverviewSuccess()
    {
        $result = $this->profileService->getProfileOverview($this->dummyUserId);
        $this->assertNotNull($result['user']);
        $this->assertEquals('user1@test.com', $result['user']['email']);
        // Ticket and spent count might be 0, but it should not be null
        $this->assertIsNumeric($result['total_tickets']);
        $this->assertIsNumeric($result['total_spent']);
    }

    // --- TEST UPDATE PROFILE ---
    public function testUpdateProfileNotLoggedIn()
    {
        $result = $this->profileService->updateProfile(0, []);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Vui lòng đăng nhập để cập nhật hồ sơ!', $result['message']);
    }

    public static function updateProfileMissingFieldsProvider(): array
    {
        return [
            'Missing first_name' => [['last_name' => 'A', 'email' => 'a@test.com', 'phone' => '0933']],
            'Missing last_name' => [['first_name' => 'A', 'email' => 'a@test.com', 'phone' => '0933']],
            'Missing email' => [['first_name' => 'A', 'last_name' => 'A', 'phone' => '0933']],
            'Missing phone' => [['first_name' => 'A', 'last_name' => 'A', 'email' => 'a@test.com']],
        ];
    }

    /**
     * @dataProvider updateProfileMissingFieldsProvider
     */
    public function testUpdateProfileMissingFields($data)
    {
        $result = $this->profileService->updateProfile($this->dummyUserId, $data);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Vui lòng nhập đầy đủ họ, tên, email và số điện thoại!', $result['message']);
    }

    public function testUpdateProfileInvalidEmail()
    {
        $data = ['first_name' => 'A', 'last_name' => 'B', 'email' => 'invalid', 'phone' => '0933333333'];
        $result = $this->profileService->updateProfile($this->dummyUserId, $data);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Email không hợp lệ!', $result['message']);
    }

    public function testUpdateProfileDuplicateEmail()
    {
        // Try to update user1 to user2's email
        $data = ['first_name' => 'A', 'last_name' => 'B', 'email' => 'user2@test.com', 'phone' => '0933333333'];
        $result = $this->profileService->updateProfile($this->dummyUserId, $data);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Email này đã được sử dụng bởi tài khoản khác!', $result['message']);
    }

    public function testUpdateProfileDuplicatePhone()
    {
        // Try to update user1 to user2's phone
        $data = ['first_name' => 'A', 'last_name' => 'B', 'email' => 'new@test.com', 'phone' => '0922222222'];
        $result = $this->profileService->updateProfile($this->dummyUserId, $data);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Số điện thoại này đã được sử dụng bởi tài khoản khác!', $result['message']);
    }

    public function testUpdateProfileInvalidBirthDate()
    {
        $data = ['first_name' => 'A', 'last_name' => 'B', 'email' => 'new@test.com', 'phone' => '0933333333', 'birth_date' => '2020-13-45'];
        $result = $this->profileService->updateProfile($this->dummyUserId, $data);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Ngày sinh không hợp lệ!', $result['message']);
    }

    public function testUpdateProfileSuccess()
    {
        $_SESSION['user'] = ['id' => $this->dummyUserId]; // To test syncSessionUser
        $data = ['first_name' => 'Updated', 'last_name' => 'Name', 'email' => 'updated@test.com', 'phone' => '0933333333', 'birth_date' => '2000-12-12'];
        $result = $this->profileService->updateProfile($this->dummyUserId, $data);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Cập nhật hồ sơ thành công!', $result['message']);
        $this->assertEquals('updated@test.com', $_SESSION['user']['email']); // Verified session sync
    }

    // --- TEST UPDATE PASSWORD ---
    public function testUpdatePasswordNotLoggedIn()
    {
        $result = $this->profileService->updatePassword(0, '1', '2', '3');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Vui lòng đăng nhập để đổi mật khẩu!', $result['message']);
    }

    public function testUpdatePasswordMissingFields()
    {
        $result = $this->profileService->updatePassword($this->dummyUserId, '', 'newpass', 'newpass');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Vui lòng nhập đầy đủ thông tin mật khẩu!', $result['message']);
    }

    public function testUpdatePasswordMismatch()
    {
        $result = $this->profileService->updatePassword($this->dummyUserId, '123456', 'newpass1', 'newpass2');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Mật khẩu xác nhận không khớp!', $result['message']);
    }

    public function testUpdatePasswordTooShort()
    {
        $result = $this->profileService->updatePassword($this->dummyUserId, '123456', '123', '123');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Mật khẩu mới phải có ít nhất 6 ký tự!', $result['message']);
    }

    public function testUpdatePasswordUserNotFound()
    {
        $result = $this->profileService->updatePassword(99999, '123456', 'newpass', 'newpass');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Tài khoản không tồn tại!', $result['message']);
    }

    public function testUpdatePasswordWrongCurrent()
    {
        $result = $this->profileService->updatePassword($this->dummyUserId, 'wrongpass', 'newpass', 'newpass');
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Mật khẩu hiện tại không chính xác!', $result['message']);
    }

    public function testUpdatePasswordSuccess()
    {
        $result = $this->profileService->updatePassword($this->dummyUserId, '123456', 'newpass123', 'newpass123');
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Đổi mật khẩu thành công!', $result['message']);
    }

    // --- COVERAGE: updateProfile with empty birth_date (line 74-75) ---
    public function testUpdateProfileWithEmptyBirthDate()
    {
        // birth_date = '' should set null and NOT return error
        $data = [
            'first_name' => 'A',
            'last_name'  => 'B',
            'email'      => 'updated2@test.com',
            'phone'      => '0933333333',
            'birth_date' => ''
        ];
        $result = $this->profileService->updateProfile($this->dummyUserId, $data);
        $this->assertEquals('success', $result['status']);
    }

    // --- COVERAGE: syncSessionUser when no session (line 133-134) ---
    public function testUpdateProfileSyncSessionWhenNoSession()
    {
        // Ensure $_SESSION['user'] is not set
        unset($_SESSION['user']);
        $data = [
            'first_name' => 'A',
            'last_name'  => 'B',
            'email'      => 'nosession@test.com',
            'phone'      => '0933333333',
        ];
        $result = $this->profileService->updateProfile($this->dummyUserId, $data);
        // Should succeed even without session
        $this->assertEquals('success', $result['status']);
        // Session should remain unset
        $this->assertArrayNotHasKey('user', $_SESSION);
    }

    // --- COVERAGE: updateProfile DB failure via mock (line 81) ---
    public function testUpdateProfileDbFailureMock()
    {
        $userModelMock = $this->createMock(\App\Models\UserModel::class);
        $userModelMock->method('findByEmail')->willReturn(null);
        $userModelMock->method('findByPhone')->willReturn(null);
        $userModelMock->method('updateProfile')->willReturn(false);
        $userModelMock->method('getError')->willReturn('DB error');

        $service = new ProfileService($userModelMock);
        $data    = ['first_name' => 'A', 'last_name' => 'B', 'email' => 'mock@test.com', 'phone' => '0933333333'];
        $result  = $service->updateProfile($this->dummyUserId, $data);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Lỗi khi cập nhật', $result['message']);
    }

    // --- COVERAGE: updatePassword DB failure via mock (line 121) ---
    public function testUpdatePasswordDbFailureMock()
    {
        $hashedPw      = password_hash('123456', PASSWORD_DEFAULT);
        $userModelMock = $this->createMock(\App\Models\UserModel::class);
        $userModelMock->method('getById')->willReturn(['id' => 1, 'password' => $hashedPw]);
        $userModelMock->method('updatePassword')->willReturn(false);
        $userModelMock->method('getError')->willReturn('DB error');

        $service = new ProfileService($userModelMock);
        $result  = $service->updatePassword($this->dummyUserId, '123456', 'newpass123', 'newpass123');

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Lỗi khi đổi mật khẩu', $result['message']);
    }
}

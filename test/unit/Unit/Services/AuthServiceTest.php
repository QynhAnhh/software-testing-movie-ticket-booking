<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AuthService;
use App\Config\Database;

class AuthServiceTest extends TestCase
{
    private $authService;
    private $conn;

    protected function setUp(): void
    {
        $this->authService = new AuthService();
        $this->conn = Database::getConnection();

        $this->cleanUpDummyData();

        $passwordHash = password_hash('123456', PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password, role)
                VALUES ('Dummy', 'User', 'exist@test.com', '0922222222', '$passwordHash', 'user')";
        mysqli_query($this->conn, $sql);
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
        mysqli_query($this->conn, "DELETE FROM users WHERE email LIKE '%@%'");
        mysqli_query($this->conn, "DELETE FROM users WHERE phone LIKE '090%' OR phone = '0922222222' OR phone = '1234567890'");
    }

    private function getBaseRegisterData()
    {
        return [
            'first_name' => 'Nguyễn',
            'last_name' => 'A',
            'email' => 'test@test.com',
            'phone' => '0901234567',
            'password' => '123456',
            'confirm_password' => '123456'
        ];
    }

    // NHÓM 1: KIỂM TRA TÍNH HỢP LỆ CỦA MẬT KHẨU (TC-AH-01 -> 09, 21)
    public function passwordLengthProvider()
    {
        return [
            // $length, $password, $expectedStatus, $expectedMessage, $testCaseId
            [3, 'abc', 'error', 'Mật khẩu phải từ 6-20 ký tự', 'TC-AH-02'],
            [5, 'abcde', 'error', 'Mật khẩu phải từ 6-20 ký tự', 'TC-AH-04'],
            [6, 'abcdef', 'success', 'Đăng ký tài khoản thành công!', 'TC-AH-05'],
            [7, 'abcdefg', 'success', 'Đăng ký tài khoản thành công!', 'TC-AH-06'],
            [10, 'abcdefghij', 'success', 'Đăng ký tài khoản thành công!', 'TC-AH-01'],
            [19, 'abcdefghijklmnopqrs', 'success', 'Đăng ký tài khoản thành công!', 'TC-AH-07'],
            [20, 'abcdefghijklmnopqrst', 'success', 'Đăng ký tài khoản thành công!', 'TC-AH-08'],
            [21, 'abcdefghijklmnopqrstu', 'error', 'Mật khẩu không được vượt quá 20 ký tự', 'TC-AH-09'],
            [25, 'abcdefghijklmnopqrstuvwxy', 'error', 'Mật khẩu không được vượt quá 20 ký tự', 'TC-AH-03'],
        ];
    }

    /**
     * @dataProvider passwordLengthProvider
     */
    public function testPasswordLengthValidation($length, $password, $expectedStatus, $expectedMessage, $tcId)
    {
        $data = $this->getBaseRegisterData();
        $data['password'] = $password;
        $data['confirm_password'] = $password;
        $data['email'] = "test{$length}@test.com";

        $result = $this->authService->register($data);

        $this->assertEquals($expectedStatus, $result['status'], "Failed at {$tcId}");
        if ($expectedStatus === 'error') {
            $this->assertStringContainsString($expectedMessage, $result['message'], "Failed at {$tcId}");
        }
    }

    public function testRegisterConfirmPasswordMismatch()
    {
        $data = $this->getBaseRegisterData();
        $data['password'] = '123456';
        $data['confirm_password'] = '123457';
        $data['email'] = 'test21@test.com';

        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('không khớp', $result['message']);
    }

    // NHÓM 2: XỬ LÝ ĐỊNH DẠNG ĐẦU VÀO (TC-AH-10 -> 14, 24 -> 27)
    public function formatValidationProvider()
    {
        return [
            // $field, $value, $expectedStatus, $expectedMessageKeyword, $tcId
            ['phone', '0901234567', 'success', 'thành công', 'TC-AH-10'], // 10 số, đầu 0 hợp lệ
            ['phone', '0901a14d67', 'error', 'chỉ được chứa số', 'TC-AH-11'], // Chứa chữ cái
            ['phone', '012345678', 'error', 'phải bao gồm 10 số', 'TC-AH-12'], // 9 số
            ['phone', '01234567890', 'error', 'phải bao gồm 10 số', 'TC-AH-13'], // 11 số
            ['phone', '1234567890', 'error', 'phải bắt đầu bằng số 0', 'TC-AH-14'], // Không đầu 0
            ['email', 'test24test.com', 'error', 'Email không hợp lệ', 'TC-AH-24'], // Thiếu @
            ['email', 'test24@', 'error', 'Email không hợp lệ', 'TC-AH-25'], // Thiếu domain
            ['last_name', 'A@123', 'error', 'không được chứa số hoặc ký tự đặc biệt', 'TC-AH-26'], // Tên chứa ký tự đặc biệt
            ['phone', '090123@#67', 'error', 'không hợp lệ', 'TC-AH-27'], // SĐT chứa ký tự đặc biệt
        ];
    }

    /**
     * @dataProvider formatValidationProvider
     */
    public function testFormatValidation($field, $value, $expectedStatus, $expectedMessageKeyword, $tcId)
    {
        $data = $this->getBaseRegisterData();
        $data[$field] = $value;
        $data['email'] = "testformat" . random_int(1, 1000) . "@test.com";

        if ($field === 'email') {
            $data['email'] = $value;
        }

        $result = $this->authService->register($data);

        $this->assertEquals($expectedStatus, $result['status'], "Failed at {$tcId}");
        if ($expectedStatus === 'error') {
            $this->assertStringContainsStringIgnoringCase($expectedMessageKeyword, $result['message'], "Failed at {$tcId}");
        }
    }

    // NHÓM 3: KIỂM TRA RÀNG BUỘC TOÀN VẸN DỮ LIỆU (TC-AH-15, 16, 22, 23)
    public function testRegisterDuplicateEmail() // TC-AH-15
    {
        $data = $this->getBaseRegisterData();
        $data['email'] = 'exist@test.com';
        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('đã được sử dụng', $result['message']);
    }

    public function testRegisterDuplicatePhone() // TC-AH-16
    {
        $data = $this->getBaseRegisterData();
        $data['phone'] = '0922222222';
        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('đã được sử dụng', $result['message']);
    }

    public function testRegisterEmptyAllFields() // TC-AH-22
    {
        $data = [
            'first_name' => '', 'last_name' => '', 'email' => '',
            'phone' => '', 'password' => '', 'confirm_password' => ''
        ];
        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Vui lòng nhập đầy đủ', $result['message']);
    }

    public function testRegisterEmptyOneField() // TC-AH-23
    {
        $data = $this->getBaseRegisterData();
        $data['email'] = '';
        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Vui lòng nhập đầy đủ', $result['message']);
    }

    // NHÓM 4: KIỂM TRA NGHIỆP VỤ ĐĂNG NHẬP (TC-AH-17 -> 20)
    public function loginProvider()
    {
        return [
            // $email, $password, $expectedStatus, $expectedMessageKeyword, $tcId
            ['exist@test.com', '123456', 'success', 'thành công', 'TC-AH-17'],
            ['exist@test.com', 'wrongpass', 'error', 'không đúng', 'TC-AH-18'],
            ['notfound@test.com', '123456', 'error', 'không đúng', 'TC-AH-19'],
            ['', '', 'error', 'Vui lòng nhập', 'TC-AH-20'],
        ];
    }

    /**
     * @dataProvider loginProvider
     */
    public function testLoginLogic($email, $password, $expectedStatus, $expectedMessageKeyword, $tcId)
    {
        $result = $this->authService->login($email, $password);

        $this->assertEquals($expectedStatus, $result['status'], "Failed at {$tcId}");
        $this->assertStringContainsStringIgnoringCase($expectedMessageKeyword, $result['message'], "Failed at {$tcId}");
        
        if ($expectedStatus === 'success') {
            $this->assertArrayHasKey('user', $_SESSION);
            $this->assertEquals($email, $_SESSION['user']['email']);
        }
    }

    // --- TEST LOGOUT ---
    public function testLogout()
    {
        $_SESSION['user'] = ['email' => 'exist@test.com'];
        $result = $this->authService->logout();

        $this->assertEquals('success', $result['status']);
        $this->assertArrayNotHasKey('user', $_SESSION);
    }

    // --- COVERAGE: register DB insert failure via mock (line 63) ---
    public function testRegisterDbInsertFailureMock()
    {
        $userModelMock = $this->createMock(\App\Models\UserModel::class);
        $userModelMock->method('findByEmail')->willReturn(null);
        $userModelMock->method('findByPhone')->willReturn(null);
        $userModelMock->method('insert')->willReturn(false);
        $userModelMock->method('getError')->willReturn('DB constraint error');

        $service = new AuthService($userModelMock);
        $data    = $this->getBaseRegisterData();
        $data['email'] = 'mock@test.com';
        $result  = $service->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertStringContainsString('Lỗi khi đăng ký', $result['message']);
    }
}

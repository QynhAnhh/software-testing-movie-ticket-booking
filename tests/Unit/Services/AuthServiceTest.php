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

        // Create a dummy user for duplicate checks and login
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
        mysqli_query($this->conn, "DELETE FROM users WHERE phone = '0922222222' OR phone = '0901234567'");
    }

    private function getBaseRegisterData()
    {
        return [
            'first_name' => 'Nguyễn',
            'last_name' => 'A',
            'email' => 'newuser@test.com',
            'phone' => '0901234567',
            'password' => '123456',
            'confirm_password' => '123456',
            'birth_date' => '2000-01-01'
        ];
    }

    // --- TEST REGISTER ---
    public function registerMissingFieldsProvider()
    {
        return [
            'Missing first_name' => ['first_name'],
            'Missing last_name' => ['last_name'],
            'Missing email' => ['email'],
            'Missing phone' => ['phone'],
            'Missing password' => ['password'],
            'Missing confirm_password' => ['confirm_password'],
        ];
    }

    /**
     * @dataProvider registerMissingFieldsProvider
     */
    public function testRegisterMissingFields($missingField)
    {
        $data = $this->getBaseRegisterData();
        $data[$missingField] = '';

        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Vui lòng nhập đầy đủ thông tin bắt buộc!', $result['message']);
    }

    public function testRegisterInvalidEmail()
    {
        $data = $this->getBaseRegisterData();
        $data['email'] = 'invalid-email';

        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Email không hợp lệ!', $result['message']);
    }

    public function testRegisterPasswordMismatch()
    {
        $data = $this->getBaseRegisterData();
        $data['confirm_password'] = 'different';

        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Mật khẩu xác nhận không khớp!', $result['message']);
    }

    public function testRegisterPasswordTooShort()
    {
        $data = $this->getBaseRegisterData();
        $data['password'] = '12345';
        $data['confirm_password'] = '12345';

        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Mật khẩu phải có ít nhất 6 ký tự!', $result['message']);
    }

    public function testRegisterDuplicateEmail()
    {
        $data = $this->getBaseRegisterData();
        $data['email'] = 'exist@test.com'; // Existing email

        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Email này đã được sử dụng!', $result['message']);
    }

    public function testRegisterDuplicatePhone()
    {
        $data = $this->getBaseRegisterData();
        $data['phone'] = '0922222222'; // Existing phone

        $result = $this->authService->register($data);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Số điện thoại này đã được sử dụng!', $result['message']);
    }

    public function testRegisterSuccess()
    {
        $data = $this->getBaseRegisterData();

        $result = $this->authService->register($data);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Đăng ký tài khoản thành công!', $result['message']);
    }

    // --- TEST LOGIN ---
    public function loginMissingFieldsProvider()
    {
        return [
            'Missing email' => ['', '123456'],
            'Missing password' => ['exist@test.com', ''],
            'Missing both' => ['', ''],
        ];
    }

    /**
     * @dataProvider loginMissingFieldsProvider
     */
    public function testLoginMissingFields($email, $password)
    {
        $result = $this->authService->login($email, $password);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Vui lòng nhập email và mật khẩu!', $result['message']);
    }

    public function testLoginUserNotFound()
    {
        $result = $this->authService->login('notfound@test.com', '123456');

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Email hoặc mật khẩu không đúng!', $result['message']);
    }

    public function testLoginWrongPassword()
    {
        $result = $this->authService->login('exist@test.com', 'wrongpassword');

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Email hoặc mật khẩu không đúng!', $result['message']);
    }

    public function testLoginSuccess()
    {
        $result = $this->authService->login('exist@test.com', '123456');

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Đăng nhập thành công!', $result['message']);
        $this->assertEquals('user', $result['role']);
        $this->assertArrayHasKey('user', $_SESSION);
        $this->assertEquals('exist@test.com', $_SESSION['user']['email']);
    }

    // --- TEST LOGOUT ---
    public function testLogout()
    {
        $_SESSION['user'] = ['email' => 'exist@test.com'];
        $result = $this->authService->logout();

        $this->assertEquals('success', $result['status']);
        $this->assertArrayNotHasKey('user', $_SESSION);
    }
}

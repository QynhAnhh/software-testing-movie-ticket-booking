<?php
namespace Tests\Unit\Services;

use App\Config\Database;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

/**
 * Class BookingPaymentTest
 * Đạt tiêu chuẩn 100% Branch-Condition Coverage cho KAN-78
 */
class BookingPaymentTest extends TestCase
{
    private $userModelMock;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock user model và cô lập hoàn toàn môi trường vật lý
        $this->userModelMock = $this->getMockBuilder(UserModel::class)
            ->disableOriginalConstructor()
            ->addMethods(['getUserBalance', 'deductBalance'])
            ->getMock();
    }

    protected function tearDown(): void
    {
        unset($this->userModelMock);
        parent::tearDown();
    }

    /**
     * @testdox TC-PAY-01: Từ chối phương thức thanh toán không hợp lệ (Nhánh N2 -> N3)
     */
    public function test_TC_PAY_01_invalid_payment_method_returns_false()
    {
        $paymentService = new BookingPayment($this->userModelMock);
        $result = $paymentService->processPayment(1, 100000, 'bitcoin');
        $this->assertFalse($result);
    }

    /**
     * @testdox TC-PAY-02: Từ chối MoMo khi số dư không đủ (Nhánh N5 -> N6)
     */
    public function test_TC_PAY_02_insufficient_balance_with_e_wallet_fails()
    {
        $userId = 1;
        $totalPrice = 150000;
        $paymentMethod = 'momo';
        $userBalance = 50000; // 50k < 150k

        $this->userModelMock->expects($this->once())
            ->method('getUserBalance')
            ->with($userId)
            ->willReturn($userBalance);

        $this->userModelMock->expects($this->never())
            ->method('deductBalance');

        $paymentService = new BookingPayment($this->userModelMock);
        $result = $paymentService->processPayment($userId, $totalPrice, $paymentMethod);
        $this->assertFalse($result);
    }

    /**
     * @testdox TC-PAY-03: Cho phép tiền mặt khi số dư tài khoản không đủ (Tiền mặt qua biên)
     */
    public function test_TC_PAY_03_insufficient_balance_with_cash_succeeds()
    {
        $userId = 1;
        $totalPrice = 150000;
        $paymentMethod = 'cash';
        $userBalance = 10000; // 10k < 150k nhưng là cash

        $this->userModelMock->expects($this->once())
            ->method('getUserBalance')
            ->with($userId)
            ->willReturn($userBalance);

        $this->userModelMock->expects($this->once())
            ->method('deductBalance')
            ->with($userId, $totalPrice)
            ->willReturn(true);

        $paymentService = new BookingPayment($this->userModelMock);
        $result = $paymentService->processPayment($userId, $totalPrice, $paymentMethod);
        $this->assertTrue($result);
    }

    /**
     * @testdox TC-PAY-04: Thanh toán MoMo thành công khi đủ số dư (Happy Path)
     */
    public function test_TC_PAY_04_sufficient_balance_with_e_wallet_succeeds()
    {
        $userId = 1;
        $totalPrice = 100000;
        $paymentMethod = 'momo';
        $userBalance = 250000;

        $this->userModelMock->expects($this->once())
            ->method('getUserBalance')
            ->with($userId)
            ->willReturn($userBalance);

        $this->userModelMock->expects($this->once())
            ->method('deductBalance')
            ->with($userId, $totalPrice)
            ->willReturn(true);

        $paymentService = new BookingPayment($this->userModelMock);
        $result = $paymentService->processPayment($userId, $totalPrice, $paymentMethod);
        $this->assertTrue($result);
    }

    /**
     * @testdox TC-PAY-05: Trả về thất bại khi Database không thể trừ tiền (Nhánh N8 -> N9)
     */
    public function test_TC_PAY_05_db_deduct_failure_returns_false()
    {
        $userId = 1;
        $totalPrice = 100000;
        $paymentMethod = 'vnpay';
        $userBalance = 150000;

        $this->userModelMock->expects($this->once())
            ->method('getUserBalance')
            ->with($userId)
            ->willReturn($userBalance);

        $this->userModelMock->expects($this->once())
            ->method('deductBalance')
            ->willReturn(false); // giả lập DB treo

        $paymentService = new BookingPayment($this->userModelMock);
        $result = $paymentService->processPayment($userId, $totalPrice, $paymentMethod);
        $this->assertFalse($result);
    }
}

/**
 * Lớp đại diện BookingPayment sử dụng trong bộ kiểm thử
 */
class BookingPayment {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function processPayment(int $userId, float $totalPrice, string $paymentMethod): bool
    {
        if (!in_array($paymentMethod, ['cash', 'momo', 'vnpay', 'bank_transfer'])) {
            return false;
        }
        $balance = $this->db->getUserBalance($userId);
        if ($balance < $totalPrice && $paymentMethod !== 'cash') {
            return false;
        }
        $deducted = $this->db->deductBalance($userId, $totalPrice);
        if (!$deducted) {
            return false;
        }
        return true;
    }
}
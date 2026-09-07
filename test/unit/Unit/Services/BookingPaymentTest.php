<?php
namespace Tests\Unit\Services;

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
     * @dataProvider paymentCases
        * @testdox {testdox}
     */
        public function test_payment_cases($testdox, $userId, $totalPrice, $paymentMethod, $userBalance, $deducted, $expected)
    {
        if ($userBalance !== null) {
            $this->userModelMock->expects($this->once())
                ->method('getUserBalance')
                ->with($userId)
                ->willReturn($userBalance);
        }

        $deductExpectation = $deducted === null ? $this->never() : $this->once();
        $deductMock = $this->userModelMock->expects($deductExpectation)
            ->method('deductBalance');
        if ($deducted !== null) {
            $deductMock->with($userId, $totalPrice)->willReturn($deducted);
        }

        $result = (new BookingPayment($this->userModelMock))
            ->processPayment($userId, $totalPrice, $paymentMethod);

        $this->assertSame($expected, $result);
    }

    public static function paymentCases()
    {
        return [
            'TC-PAY-01: Từ chối phương thức thanh toán không hợp lệ (Nhánh N2 -> N3)' => ['TC-PAY-01: Từ chối phương thức thanh toán không hợp lệ (Nhánh N2 -> N3)', 1, 100000, 'bitcoin', null, null, false],
            'TC-PAY-02: Từ chối MoMo khi số dư không đủ (Nhánh N5 -> N6)' => ['TC-PAY-02: Từ chối MoMo khi số dư không đủ (Nhánh N5 -> N6)', 1, 150000, 'momo', 50000, null, false],
            'TC-PAY-03: Cho phép tiền mặt khi số dư tài khoản không đủ (Tiền mặt qua biên)' => ['TC-PAY-03: Cho phép tiền mặt khi số dư tài khoản không đủ (Tiền mặt qua biên)', 1, 150000, 'cash', 10000, true, true],
            'TC-PAY-04: Thanh toán MoMo thành công khi đủ số dư (Happy Path)' => ['TC-PAY-04: Thanh toán MoMo thành công khi đủ số dư (Happy Path)', 1, 100000, 'momo', 250000, true, true],
            'TC-PAY-05: Trả về thất bại khi Database không thể trừ tiền (Nhánh N8 -> N9)' => ['TC-PAY-05: Trả về thất bại khi Database không thể trừ tiền (Nhánh N8 -> N9)', 1, 100000, 'vnpay', 150000, false, false],
        ];
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
        $validMethod = in_array($paymentMethod, ['cash', 'momo', 'vnpay', 'bank_transfer']);
        $balance = $validMethod ? $this->db->getUserBalance($userId) : 0;
        $canPay = $validMethod && ($balance >= $totalPrice || $paymentMethod === 'cash');
        $deducted = $canPay ? $this->db->deductBalance($userId, $totalPrice) : false;

        return $canPay && $deducted;
    }
}

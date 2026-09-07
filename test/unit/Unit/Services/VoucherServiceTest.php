<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\VoucherService;
use App\Models\VoucherModel;

class VoucherServiceTest extends TestCase
{
    private VoucherService $voucherService;
    private $voucherModelMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->voucherModelMock = $this->createMock(VoucherModel::class);
        $this->voucherService   = new VoucherService($this->voucherModelMock);
    }

    protected function tearDown(): void
    {
        $this->voucherService->restoreUsageCountAndHistory();
        unset($this->voucherService, $this->voucherModelMock);
        parent::tearDown();
    }

    // ------------------------------------------------------------------ //
    //  Decision Table Tests (TC-TC-01 to TC-TC-08)                        //
    // ------------------------------------------------------------------ //

    /**
     * @dataProvider decisionTableProvider
     */
    public function testApplyVoucherDecisionTable(
        string $status,
        float $orderAmount,
        float $minOrder,
        ?string $expectedException
    ): void {
        $expiryFuture = date('Y-m-d', strtotime('+1 year'));
        $expiryPast   = date('Y-m-d', strtotime('-1 day'));

        if ($status === 'not_found') {
            $this->voucherModelMock->method('findByCode')->willReturn(null);
        } elseif ($status === 'expired') {
            $this->voucherModelMock->method('findByCode')->willReturn([
                'id'             => 1,
                'code'           => 'EXPIRED50',
                'discount_type'  => 'percent',
                'discount_value' => 50,
                'min_order'      => $minOrder,
                'max_usage'      => 100,
                'used_count'     => 0,
                'expiry_date'    => $expiryPast,
                'status'         => 'active',
            ]);
        } else {
            // valid
            $this->voucherModelMock->method('findByCode')->willReturn([
                'id'             => 1,
                'code'           => 'MOVIE50',
                'discount_type'  => 'percent',
                'discount_value' => 50,
                'min_order'      => $minOrder,
                'max_usage'      => 100,
                'used_count'     => 0,
                'expiry_date'    => $expiryFuture,
                'status'         => 'active',
            ]);
            $this->voucherModelMock->method('hasUserUsed')->willReturn(false);
            $this->voucherModelMock->method('incrementUsage')->willReturn(true);
            $this->voucherModelMock->method('recordUsage')->willReturn(true);
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }

        $result = $this->voucherService->applyVoucher('MOVIE50', $orderAmount, 1);

        if ($expectedException === null) {
            $this->assertArrayHasKey('final_amount', $result);
            $this->assertGreaterThanOrEqual(0, $result['final_amount']);
        }
    }

    public function decisionTableProvider(): array
    {
        return [
            'TC-TC-01: Voucher hợp lệ, đạt giá trị tối thiểu'         => ['valid',     500000, 200000, null],
            'TC-TC-02: Voucher hợp lệ, chưa đạt giá trị tối thiểu'    => ['valid',     100000, 200000, \Exception::class],
            'TC-TC-03: Voucher hết hạn, đạt giá trị tối thiểu'        => ['expired',   500000, 200000, \Exception::class],
            'TC-TC-04: Voucher hết hạn, chưa đạt giá trị tối thiểu'   => ['expired',   100000, 200000, \Exception::class],
            'TC-TC-05: Voucher không tồn tại, đạt giá trị tối thiểu'  => ['not_found', 500000, 200000, \Exception::class],
            'TC-TC-06: Voucher không tồn tại, chưa đạt giá trị tối thiểu' => ['not_found', 100000, 200000, \Exception::class],
            'TC-TC-07: Voucher hợp lệ, giá trị bằng đúng tối thiểu'   => ['valid',     200000, 200000, null],
            'TC-TC-08: Voucher hợp lệ, đơn hàng 0đ'                   => ['valid',          0, 200000, \Exception::class],
        ];
    }

    // ------------------------------------------------------------------ //
    //  Error Guessing – Voucher Code Format (TC-TC-09 to TC-TC-19)        //
    // ------------------------------------------------------------------ //

    /**
     * @dataProvider invalidVoucherCodeProvider
     */
    public function testVoucherCodeFormatErrorGuessing(string $voucherCode): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->voucherService->validateCode($voucherCode);
    }

    public function invalidVoucherCodeProvider(): array
    {
        return [
            'TC-TC-09: Mã để trống'                    => [''],
            'TC-TC-10: Chứa khoảng trắng đầu/cuối/giữa' => [' SALE 50 '],
            'TC-TC-11: Mã viết thường'                 => ['discount50'],
            'TC-TC-12: Chứa ký tự đặc biệt'           => ['SALE@2026!'],
            'TC-TC-18: Chuỗi quá dài (> 50 ký tự)'    => [str_repeat('A', 51)],
            'TC-TC-19: Mã chỉ chứa khoảng trắng'      => ['   '],
        ];
    }

    // ------------------------------------------------------------------ //
    //  BVA – Voucher Code Length (TC-TC-16 to TC-TC-20)                   //
    // ------------------------------------------------------------------ //

    public function testVoucherCodeLengthBoundaryBelow(): void
    {
        // TC-TC-16: 1 ký tự – dưới biên dưới cho phép (min thực tế là 1)
        $this->expectNotToPerformAssertions();
        $this->voucherService->validateCode('A');
    }

    public function testVoucherCodeLengthBoundaryLower(): void
    {
        // TC-TC-17: 2 ký tự – ngay trên biên dưới → hợp lệ
        $this->expectNotToPerformAssertions();
        $this->voucherService->validateCode('AB');
    }

    public function testVoucherCodeLengthBoundaryUpper(): void
    {
        // TC-TC-18: Đúng 50 ký tự – ngay biên trên → hợp lệ
        $this->expectNotToPerformAssertions();
        $this->voucherService->validateCode(str_repeat('A', 50));
    }

    public function testVoucherCodeLengthExceedsUpper(): void
    {
        // TC-TC-20: 51 ký tự – vượt biên trên → throw InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->voucherService->validateCode(str_repeat('A', 51));
    }

    // ------------------------------------------------------------------ //
    //  Additional Scenario Tests                                           //
    // ------------------------------------------------------------------ //

    public function testVoucherOutOfTotalUsage(): void
    {
        $this->voucherModelMock->method('findByCode')->willReturn([
            'id'             => 2,
            'code'           => 'FULLUSED',
            'discount_type'  => 'percent',
            'discount_value' => 10,
            'min_order'      => 0,
            'max_usage'      => 5,
            'used_count'     => 5, // already at limit
            'expiry_date'    => date('Y-m-d', strtotime('+1 year')),
            'status'         => 'active',
        ]);

        $this->expectException(\Exception::class);
        $this->voucherService->applyVoucher('FULLUSED', 300000, 1);
    }

    public function testUserAlreadyUsedVoucher(): void
    {
        $this->voucherModelMock->method('findByCode')->willReturn([
            'id'             => 3,
            'code'           => 'ONCE',
            'discount_type'  => 'fixed',
            'discount_value' => 50000,
            'min_order'      => 0,
            'max_usage'      => 100,
            'used_count'     => 1,
            'expiry_date'    => date('Y-m-d', strtotime('+1 year')),
            'status'         => 'active',
        ]);
        $this->voucherModelMock->method('hasUserUsed')->willReturn(true);

        $this->expectException(\Exception::class);
        $this->voucherService->applyVoucher('ONCE', 300000, 7);
    }

    public function testApplyMultipleVouchersSimultaneously(): void
    {
        // System should only allow one voucher per order.
        // Attempting a second apply should still throw if already used.
        $this->voucherModelMock->method('findByCode')->willReturn([
            'id'             => 4,
            'code'           => 'MULTI',
            'discount_type'  => 'percent',
            'discount_value' => 20,
            'min_order'      => 0,
            'max_usage'      => 100,
            'used_count'     => 0,
            'expiry_date'    => date('Y-m-d', strtotime('+1 year')),
            'status'         => 'active',
        ]);

        $this->voucherModelMock->method('hasUserUsed')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->voucherModelMock->method('incrementUsage')->willReturn(true);
        $this->voucherModelMock->method('recordUsage')->willReturn(true);

        // First apply succeeds
        $result = $this->voucherService->applyVoucher('MULTI', 300000, 1);
        $this->assertArrayHasKey('final_amount', $result);

        // Second apply on same user should fail
        $this->expectException(\Exception::class);
        $this->voucherService->applyVoucher('MULTI', 300000, 1);
    }

    public function testVoucherDiscount100Percent(): void
    {
        $this->voucherModelMock->method('findByCode')->willReturn([
            'id'             => 5,
            'code'           => 'FREE100',
            'discount_type'  => 'percent',
            'discount_value' => 100,
            'min_order'      => 0,
            'max_usage'      => 10,
            'used_count'     => 0,
            'expiry_date'    => date('Y-m-d', strtotime('+1 year')),
            'status'         => 'active',
        ]);
        $this->voucherModelMock->method('hasUserUsed')->willReturn(false);
        $this->voucherModelMock->method('incrementUsage')->willReturn(true);
        $this->voucherModelMock->method('recordUsage')->willReturn(true);

        $result = $this->voucherService->applyVoucher('FREE100', 300000, 1);
        $this->assertEquals(0, $result['final_amount']);
    }

    public function testDiscountExceedsTotalOrderAmountNeverNegative(): void
    {
        $this->voucherModelMock->method('findByCode')->willReturn([
            'id'             => 6,
            'code'           => 'BIG500K',
            'discount_type'  => 'fixed',
            'discount_value' => 500000,  // discount > order amount
            'min_order'      => 0,
            'max_usage'      => 10,
            'used_count'     => 0,
            'expiry_date'    => date('Y-m-d', strtotime('+1 year')),
            'status'         => 'active',
        ]);
        $this->voucherModelMock->method('hasUserUsed')->willReturn(false);
        $this->voucherModelMock->method('incrementUsage')->willReturn(true);
        $this->voucherModelMock->method('recordUsage')->willReturn(true);

        $result = $this->voucherService->applyVoucher('BIG500K', 300000, 1);
        $this->assertEquals(0, $result['final_amount'], 'Final amount must never be negative.');
    }
}

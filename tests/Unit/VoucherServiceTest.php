<?php

namespace Tests\Unit;

require_once __DIR__ . '/../../backend/app/init.php';

use App\Services\VoucherService;
use App\Models\VoucherModel;
use App\Exceptions\VoucherException;
use Tests\Support\UnitTester;

class VoucherServiceTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    private const DEFAULT_EXPIRATION_DATE = '2099-12-31';

    /**
     * Create a mock VoucherModel with default voucher data.
     *
     * @param array<string, mixed> $overrides
     */
    private function createVoucherModel(array $overrides = []): VoucherModel
    {
        $model = $this->createMock(VoucherModel::class);

        $voucher = array_merge(
            [
                'id' => 1,
                'code' => 'MOVIE50',
                'expiry_date' => self::DEFAULT_EXPIRATION_DATE,
                'status' => 'active',
                'min_order' => 50000,
                'used_count' => 0,
                'max_usage' => 100,
                'discount_type' => 'fixed',
                'discount_value' => 20000,
            ],
            $overrides
        );

        $model->method('findByCode')
            ->willReturn($voucher);

        return $model;
    }

    public function testEmptyVoucherCode(): void
    {
        $service = new VoucherService();

        $this->expectException(\InvalidArgumentException::class);

        $service->validateCode('');
    }

    public function testVoucherTooLong(): void
    {
        $service = new VoucherService();

        $this->expectException(\InvalidArgumentException::class);

        $service->validateCode(str_repeat('A', 51));
    }

    public function testVoucherContainsSpecialCharacter(): void
    {
        $service = new VoucherService();

        $this->expectException(\InvalidArgumentException::class);

        $service->validateCode('MOVIE@50');
    }

    public function testValidVoucherCode(): void
    {
        $service = new VoucherService();

        $service->validateCode('MOVIE50');

        $this->assertTrue(true);
    }

    public function testVoucherNotFound(): void
    {
        $model = $this->createMock(VoucherModel::class);

        $model->method('findByCode')
            ->willReturn(null);

        $service = new VoucherService($model);

        $this->expectException(VoucherException::class);

        $service->applyVoucher('MOVIE50', 100000);
    }

    public function testExpiredVoucher(): void
    {
        $model = $this->createVoucherModel([
            'expiry_date' => '2020-01-01',
        ]);

        $service = new VoucherService($model);

        $this->expectException(VoucherException::class);
        $this->expectExceptionMessage('Voucher has expired.');

        $service->applyVoucher('MOVIE50', 100000);
    }

    public function testInactiveVoucher(): void
    {
        $model = $this->createVoucherModel([
            'status' => 'inactive',
        ]);

        $service = new VoucherService($model);

        $this->expectException(VoucherException::class);

        $service->applyVoucher('MOVIE50', 100000);
    }

    public function testMinimumOrderNotReached(): void
    {
        $model = $this->createVoucherModel([
            'min_order' => 100000,
        ]);

        $service = new VoucherService($model);

        $this->expectException(VoucherException::class);

        $service->applyVoucher('MOVIE50', 50000);
    }

    public function testMaxUsageReached(): void
    {
        $model = $this->createVoucherModel([
            'used_count' => 100,
        ]);

        $service = new VoucherService($model);

        $this->expectException(VoucherException::class);

        $service->applyVoucher('MOVIE50', 100000);
    }

    public function testApplyFixedVoucherSuccess(): void
    {
        $model = $this->createVoucherModel();

        $model->method('hasUserUsed')
            ->willReturn(false);

        $model->method('incrementUsage')
            ->willReturn(true);

        $model->method('recordUsage')
            ->willReturn(true);

        $service = new VoucherService($model);

        $result = $service->applyVoucher('MOVIE50', 100000, 1);

        $this->assertEquals(20000, $result['discount']);
        $this->assertEquals(80000, $result['final_amount']);
    }

    public function testApplyPercentVoucherSuccess(): void
    {
        $model = $this->createVoucherModel([
            'code' => 'VIP20',
            'discount_type' => 'percent',
            'discount_value' => 20,
        ]);

        $model->method('hasUserUsed')
            ->willReturn(false);

        $model->method('incrementUsage')
            ->willReturn(true);

        $model->method('recordUsage')
            ->willReturn(true);

        $service = new VoucherService($model);

        $result = $service->applyVoucher('VIP20', 100000, 1);

        $this->assertEquals(20000, $result['discount']);
        $this->assertEquals(80000, $result['final_amount']);
    }

    public function testUserAlreadyUsedVoucher(): void
    {
        $model = $this->createVoucherModel();

        $model->method('hasUserUsed')
            ->willReturn(true);

        $service = new VoucherService($model);

        $this->expectException(VoucherException::class);

        $service->applyVoucher('MOVIE50', 100000, 1);
    }

    public function testDiscountCannotMakeNegativeAmount(): void
    {
        $model = $this->createVoucherModel([
            'code' => 'FREE100',
            'min_order' => 0,
            'discount_value' => 200000,
        ]);

        $model->method('hasUserUsed')
            ->willReturn(false);

        $model->method('incrementUsage')
            ->willReturn(true);

        $model->method('recordUsage')
            ->willReturn(true);

        $service = new VoucherService($model);

        $result = $service->applyVoucher('FREE100', 100000, 1);

        $this->assertEquals(100000, $result['discount']);
        $this->assertEquals(0, $result['final_amount']);
    }
}

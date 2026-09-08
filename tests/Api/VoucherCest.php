<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;

final class VoucherCest
{
    public function tryToTest(ApiTester $tester): void
    {
        // Sử dụng $tester để tránh cảnh báo "unused function parameter"
        $tester->wantTo('test voucher API endpoint');
    }
}

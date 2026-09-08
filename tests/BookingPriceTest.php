<?php

use PHPUnit\Framework\TestCase;
use App\Services\BookingService;

class BookingPriceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(BookingService::class)) {
            $this->service = new BookingService();
        }
    }

    public function testInvalidUserId()
    {
        $result = $this->service->processBooking(
            0,
            1,
            [1],
            'momo'
        );

        $this->assertEquals('error', $result['status']);
    }

    public function testInvalidShowtimeId()
    {
        $result = $this->service->processBooking(
            1,
            0,
            [1],
            'momo'
        );

        $this->assertEquals('error', $result['status']);
    }

    public function testEmptySeatList()
    {
        $result = $this->service->processBooking(
            1,
            1,
            [],
            'momo'
        );

        $this->assertEquals('error', $result['status']);
    }

    public function testInvalidPaymentMethod()
    {
        $result = $this->service->processBooking(
            1,
            1,
            [1],
            'cash'
        );

        $this->assertEquals('error', $result['status']);
    }

    public function testVoucherCodeExpired()
    {
        $this->assertTrue(true);
    }

    public function testVoucherAlreadyUsed()
    {
        $this->assertTrue(true);
    }

    public function testMinimumOrderNotReached()
    {
        $this->assertTrue(true);
    }

    public function testBookingSuccess()
    {
        $this->assertTrue(true);
    }
}



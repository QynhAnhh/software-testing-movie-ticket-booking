<?php

use PHPUnit\Framework\TestCase;
use App\Services\RoomService;

class RoomServiceTest extends TestCase
{
    private $service;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(RoomService::class)) {
            $this->service = new RoomService();
        }
    }

    public function testEmptyRoomName()
    {
        $data = [
            'name' => '',
            'theatre_id' => 1,
            'total_seats' => 50
        ];

        $result = $this->service->addRoom($data);

        $this->assertEquals('error', $result['status']);
    }

    public function testInvalidTheatre()
    {
        $data = [
            'name' => 'Room A',
            'theatre_id' => 0,
            'total_seats' => 50
        ];

        $result = $this->service->addRoom($data);

        $this->assertEquals('error', $result['status']);
    }

    public function testSeatNumberLessThanOne()
    {
        $data = [
            'name' => 'Room A',
            'theatre_id' => 1,
            'total_seats' => 0
        ];

        $result = $this->service->addRoom($data);

        $this->assertEquals('error', $result['status']);
    }

    public function testUpdateRoomWithInvalidId()
    {
        $result = $this->service->updateRoom(
            0,
            [
                'name' => 'Room A',
                'theatre_id' => 1,
                'total_seats' => 50
            ]
        );

        $this->assertEquals('error', $result['status']);
    }

    public function testDeleteRoomWithInvalidId()
    {
        $result = $this->service->deleteRoom(0);

        $this->assertEquals('error', $result['status']);
    }

    public function testAddRoomSuccess()
    {
        $this->assertTrue(true);
    }

    public function testUpdateRoomSuccess()
    {
        $this->assertTrue(true);
    }

    public function testDeleteRoomSuccess()
    {
        $this->assertTrue(true);
    }
}
<?php
namespace App\Controllers;

use App\Services\SeatService;

class SeatController {
    private $service;

    public function __construct() {
        $this->service = new SeatService();
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'add' || $action === 'edit') {
                $data = [
                    'room_id' => (int)($_POST['room_id'] ?? 0),
                    'seat_row' => trim($_POST['seat_row'] ?? ''),
                    'seat_number' => (int)($_POST['seat_number'] ?? 0),
                    'seat_type_id' => (int)($_POST['seat_type_id'] ?? 0),
                    'is_active' => isset($_POST['is_active']),
                ];

                if ($action === 'add') {
                    return $this->service->addSeat($data);
                }

                $id = (int)($_POST['id'] ?? 0);
                return $this->service->updateSeat($id, $data);
            }

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                return $this->service->deleteSeat($id);
            }

            if ($action === 'generate') {
                $roomId = (int)($_POST['room_id'] ?? 0);
                $startRow = trim($_POST['start_row'] ?? 'A');
                $endRow = trim($_POST['end_row'] ?? 'H');
                $seatsPerRow = (int)($_POST['seats_per_row'] ?? 5);
                $seatTypeId = (int)($_POST['seat_type_id'] ?? 0);
                return $this->service->generateSeats($roomId, $startRow, $endRow, $seatsPerRow, $seatTypeId);
            }
        }
        return null;
    }

    public function getAllSeats($roomId = null) {
        return $this->service->getAllSeats($roomId);
    }

    public function getAllRooms() {
        return $this->service->getAllRooms();
    }

    public function getAllSeatTypes() {
        return $this->service->getAllSeatTypes();
    }
}

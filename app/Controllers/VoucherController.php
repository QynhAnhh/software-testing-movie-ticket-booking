<?php

namespace App\Controllers;

use App\Services\VoucherService;

class VoucherController
{
    private VoucherService $service;

    public function __construct()
    {
        $this->service = new VoucherService();
    }

    public function handleRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        $action = $_POST['action'] ?? '';

        $data = [
            'code'              => trim($_POST['code'] ?? ''),
            'discount_amount'   => (float)($_POST['discount_amount'] ?? 0),
            'min_order_amount'  => (float)($_POST['min_order_amount'] ?? 0),
            'total_quantity'    => (int)($_POST['total_quantity'] ?? 0),
            'valid_from'        => $_POST['valid_from'] ?? '',
            'expires_at'        => $_POST['expires_at'] ?? '',
            'status'            => $_POST['status'] ?? 'active'
        ];

        switch ($action) {
            case 'add':
                return method_exists($this->service, 'addVoucher')
                    ? $this->service->addVoucher($data)
                    : ['success' => false, 'message' => 'addVoucher() not implemented'];

            case 'edit':
                return method_exists($this->service, 'updateVoucher')
                    ? $this->service->updateVoucher((int)($_POST['id'] ?? 0), $data)
                    : ['success' => false, 'message' => 'updateVoucher() not implemented'];

            case 'delete':
                return method_exists($this->service, 'deleteVoucher')
                    ? $this->service->deleteVoucher((int)($_POST['id'] ?? 0))
                    : ['success' => false, 'message' => 'deleteVoucher() not implemented'];

            default:
                return ['success' => false, 'message' => 'Invalid action'];
        }
    }

    public function getAllVouchers()
    {
        return method_exists($this->service, 'getAllVouchers')
            ? $this->service->getAllVouchers()
            : [];
    }

    public function getVoucherById($id)
    {
        return method_exists($this->service, 'getVoucherById')
            ? $this->service->getVoucherById($id)
            : null;
    }
}
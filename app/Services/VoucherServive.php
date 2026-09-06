<?php
namespace App\Services;

use App\Models\VoucherModel;

class VoucherService {
    private $model;

    public function __construct() {
        $this->model = new VoucherModel();
    }

    public function addVoucher($data) {
        $validated = $this->validateData($data);
        if ($validated['status'] === 'error') return $validated;

        if ($this->model->findByCode($validated['data']['code'])) {
            return ['status' => 'error', 'message' => 'Mã giảm giá đã tồn tại!'];
        }

        if ($this->model->insert($validated['data'])) {
            return ['status' => 'success', 'message' => 'Thêm mã giảm giá thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi thêm mã: ' . $this->model->getError()];
    }

    public function updateVoucher($id, $data) {
        $id = (int)$id;
        if ($id <= 0) return ['status' => 'error', 'message' => 'ID mã giảm giá không hợp lệ!'];

        $current = $this->model->getById($id);
        if (!$current) return ['status' => 'error', 'message' => 'Không tìm thấy mã giảm giá!'];

        $validated = $this->validateData($data);
        if ($validated['status'] === 'error') return $validated;

        if ($this->model->findByCode($validated['data']['code'], $id)) {
            return ['status' => 'error', 'message' => 'Mã giảm giá đã tồn tại!'];
        }

        if ((int)$validated['data']['total_quantity'] < (int)$current['used_quantity']) {
            return ['status' => 'error', 'message' => 'Số lượt phát hành không được nhỏ hơn số lượt đã dùng (' . (int)$current['used_quantity'] . ').'];
        }

        if ($this->model->update($id, $validated['data'])) {
            return ['status' => 'success', 'message' => 'Cập nhật mã giảm giá thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi cập nhật: ' . $this->model->getError()];
    }

    public function deleteVoucher($id) {
        $id = (int)$id;
        if ($id <= 0) return ['status' => 'error', 'message' => 'ID không hợp lệ!'];

        if ($this->model->delete($id)) {
            return ['status' => 'success', 'message' => 'Xóa mã giảm giá thành công!'];
        }
        return ['status' => 'error', 'message' => 'Lỗi khi xóa: ' . $this->model->getError()];
    }

    public function getAllVouchers() {
        return $this->model->getAll();
    }

    public function getVoucherById($id) {
        $id = (int)$id;
        return $id > 0 ? $this->model->getById($id) : null;
    }

    private function validateData($data) {
        $code = strtoupper(trim((string)($data['code'] ?? '')));
        $discount = (float)($data['discount_amount'] ?? 0);
        $minOrder = (float)($data['min_order_amount'] ?? 0);
        $quantity = (int)($data['total_quantity'] ?? 0);
        $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $validFrom = $this->normalizeDate($data['valid_from'] ?? '');
        $expiresAt = $this->normalizeDate($data['expires_at'] ?? '');

        if ($code === '' || !preg_match('/^[A-Z0-9_-]{2,50}$/', $code)) {
            return ['status' => 'error', 'message' => 'Mã giảm giá chỉ gồm A-Z, 0-9, dấu gạch ngang hoặc gạch dưới (2-50 ký tự).'];
        }
        if ($discount <= 0) return ['status' => 'error', 'message' => 'Số tiền giảm phải lớn hơn 0.'];
        if ($minOrder < 0) return ['status' => 'error', 'message' => 'Giá trị đơn tối thiểu không hợp lệ.'];
        if ($quantity <= 0) return ['status' => 'error', 'message' => 'Số lượng mã phải lớn hơn 0.'];
        if ($validFrom && $expiresAt && strtotime($expiresAt) < strtotime($validFrom)) {
            return ['status' => 'error', 'message' => 'Ngày hết hạn phải sau ngày bắt đầu.'];
        }

        return [
            'status' => 'success',
            'data' => [
                'code' => $code,
                'discount_amount' => $discount,
                'min_order_amount' => $minOrder,
                'total_quantity' => $quantity,
                'valid_from' => $validFrom,
                'expires_at' => $expiresAt,
                'status' => $status
            ]
        ];
    }

    private function normalizeDate($value) {
        $value = trim((string)$value);
        if ($value === '') return null;
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}

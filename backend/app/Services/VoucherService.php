<?php
namespace App\Services;

use App\Models\VoucherModel;
use App\Exceptions\VoucherException;

class VoucherService
{
    private VoucherModel $voucherModel;

    /** @var array Tracks voucher usages recorded during this request (for rollback in tests) */
    private array $recordedUsages = [];

    public function __construct(VoucherModel $voucherModel = null)
    {
        $this->voucherModel = $voucherModel ?? new VoucherModel();
    }

    /**
     * Kiểm tra định dạng mã
     * Rules: không rỗng, chỉ chứa ký tự chữ hoa (A-Z) và số (0-9), tối đa 50 ký tự.
     *
     * @param string $code
     * @throws \InvalidArgumentException if format is invalid
     */
    public function validateCode(string $code): void
    {
        $trimmed = trim($code);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Voucher code must not be empty.');
        }

        if (strlen($trimmed) > 50) {
            throw new \InvalidArgumentException('Voucher code must not exceed 50 characters.');
        }

        if (preg_match('/[^A-Z0-9]/', $trimmed)) {
            throw new \InvalidArgumentException(
                'Voucher code must contain only uppercase letters (A-Z) and digits (0-9).'
            );
        }
    }

    /**
     * Áp dụng voucher và trả về tổng tiền sau khi giảm
     *
     * @param string $code        Voucher code
     * @param float  $orderAmount Order total before discount
     * @param int    $userId      ID of the current user (0 for anonymous)
     * @return array ['voucher' => array, 'discount' => float, 'final_amount' => float]
     *
     * @throws \InvalidArgumentException if code format is invalid
     * @throws \Exception                if voucher cannot be applied
     */
    public function applyVoucher(string $code, float $orderAmount, int $userId = 0): array
    {
        // 1. Kiểm tra định dạng mã
        $this->validateCode($code);

        $code = trim($code);

        // 2. Tìm voucher
        $voucher = $this->voucherModel->findByCode($code);
        
        
        if (!$voucher) {
            throw new VoucherException('Voucher not found.');
        }

        // 3. Kiểm tra hạn sử dụng
        if (!empty($voucher['expiry_date']) &&
            strtotime($voucher['expiry_date']) < time()) {
            throw new VoucherException('Voucher has expired.');
        }

        // 4. Kiểm tra trạng thái
        if ($voucher['status'] !== 'active') {
            throw new VoucherException('Voucher is inactive.');
        }

        // 5. Kiểm tra tổng tiền tối thiểu
        if ($orderAmount < (float) $voucher['min_order']) {
            throw new VoucherException(
                sprintf(
                    'Order amount does not meet the minimum required (%.0f VND).',
                    $voucher['min_order']
                )
            );
        }

        // 6. Kiểm tra giới hạn sử dụng
        if ($voucher['used_count'] >= $voucher['max_usage']) {
            throw new VoucherException('Voucher has reached its maximum usage limit.');
        }

        // 7. Kiểm tra user đã xài voucher chưa
        if ($userId > 0 && $this->voucherModel->hasUserUsed((int) $voucher['id'], $userId)) {
            throw new VoucherException('You have already used this voucher.');
        }

        // 8. Tính toán số tiền được giảm
        $discount = $this->calculateDiscount($voucher, $orderAmount);
        $finalAmount = max(0, $orderAmount - $discount);

        // 9. Lưu lịch sử sử dụng
        $this->voucherModel->incrementUsage((int) $voucher['id']);
        if ($userId > 0) {
            $this->voucherModel->recordUsage((int) $voucher['id'], $userId);
            $this->recordedUsages[] = ['voucher_id' => (int) $voucher['id'], 'user_id' => $userId];
        }

        return [
            'voucher'      => $voucher,
            'discount'     => $discount,
            'final_amount' => $finalAmount,
        ];
    }

    /**
     * Tính số tiền được giảm dựa trên loại giảm giá
     *
     * @param array $voucher
     * @param float $orderAmount
     * @return float
     */
    private function calculateDiscount(array $voucher, float $orderAmount): float
    {
        $value = (float) $voucher['discount_value'];
        if ($voucher['discount_type'] === 'percent') {
            $discount = $orderAmount * ($value / 100);
            return min($discount, $orderAmount);
        }
        return min($value, $orderAmount);
    }

    /**
     * Khôi phục số lần sử dụng và lịch sử - dùng trong PHPUnit
     */
    public function restoreUsageCountAndHistory(): void
    {
        foreach ($this->recordedUsages as $usage) {
            $this->voucherModel->decrementUsage($usage['voucher_id']);
            $this->voucherModel->deleteUsage($usage['voucher_id'], $usage['user_id']);
        }
        $this->recordedUsages = [];
    }
}

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
     * Validate the format of a voucher code.
     * Rules: non-empty, uppercase alphanumeric only (A-Z, 0-9), max 50 characters.
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
     * Apply a voucher to an order and return the discounted total.
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
        // 1. Validate format
        $this->validateCode($code);

        $code = trim($code);

        // 2. Find voucher
        $voucher = $this->voucherModel->findByCode($code);
        
        if (!$voucher) {
            throw new VoucherException('Voucher not found.');
    }
        
        if (!$voucher) {
            throw new VoucherException('Voucher not found.');
        }

        // 3. Check expiry
        if (!empty($voucher['expires_at']) &&
            strtotime($voucher['expires_at']) < time()) {
            throw new VoucherException('Voucher has expired.');
    
    }

        // 4. Check active status
        if ($voucher['status'] !== 'active') {
            throw new VoucherException('Voucher is inactive.');
        }

        // 5. Check minimum order amount
        if ($orderAmount < (float) $voucher['min_order_amount']) {
            throw new VoucherException(
                sprintf(
                    'Order amount does not meet the minimum required (%.0f VND).',
                        $voucher['min_order_amount']
)
            );
        }

        // 6. Check usage limit
            if ($voucher['used_quantity'] >= $voucher['total_quantity']) {
                throw new VoucherException('Voucher has reached its maximum usage limit.');
        }

        // 7. Check if this user already used it
        if ($userId > 0 && $this->voucherModel->hasUserUsed((int) $voucher['id'], $userId)) {
            throw new VoucherException('You have already used this voucher.');
        }

        // 8. Calculate discount (result must never be negative)
        $discount = $this->calculateDiscount($voucher, $orderAmount);
        $finalAmount = max(0, $orderAmount - $discount);

        // 9. Record usage
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
     * Calculate the discount amount based on discount type.
     *
     * @param array $voucher
     * @param float $orderAmount
     * @return float
     */
    private function calculateDiscount(array $voucher, float $orderAmount): float
    
{
    return min(
        (float)$voucher['discount_amount'],
        $orderAmount
    );
}
    

    /**
     * Restore usage count and history – used in PHPUnit tearDown().
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

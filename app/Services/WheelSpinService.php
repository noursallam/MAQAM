<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\WheelPrize;
use App\Models\WheelSpin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class WheelSpinService
{
    public function isEnabled(): bool
    {
        return (SystemSetting::where('key', 'wheel_enabled')->value('value') ?? '1') === '1';
    }

    /**
     * @return array{spin: WheelSpin, prize: ?WheelPrize}
     */
    public function spin(Customer $customer): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException(__('admin.wheel.disabled'));
        }

        $customer->loadMissing('rank');
        $rank = $customer->rank;

        if (! $rank) {
            throw new RuntimeException(__('admin.wheel.no_rank'));
        }

        $cost = (int) $rank->wheel_cost_points;
        if ($customer->points_balance < $cost) {
            throw new RuntimeException(__('admin.wheel.insufficient_points'));
        }

        return DB::transaction(function () use ($customer, $rank, $cost) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

            if ($customer->points_balance < $cost) {
                throw new RuntimeException(__('admin.wheel.insufficient_points'));
            }

            $probability = (float) $rank->wheel_win_probability;
            $isWin = (mt_rand() / mt_getrandmax()) < $probability;

            $prize = null;
            $prizeType = 'none';
            $prizeValue = null;
            $pointsWon = 0;
            $product = null;

            if ($isWin) {
                $prize = $this->pickPrize();
                if (! $prize) {
                    $isWin = false;
                } else {
                    [$prizeType, $prizeValue, $pointsWon, $product] = $this->applyPrize($prize);
                    $prize->increment('awarded_count');
                }
            }

            $balanceAfterCost = $customer->points_balance - $cost;
            $customer->points_balance = $balanceAfterCost;
            $customer->total_points_spent = $customer->total_points_spent + $cost;

            if ($pointsWon > 0) {
                $customer->points_balance += $pointsWon;
                $customer->total_points_earned += $pointsWon;
            }

            $customer->save();

            PointsTransaction::create([
                'customer_id' => $customer->id,
                'type' => 'spend',
                'amount' => -$cost,
                'description' => 'عجلة الحظ — تكلفة اللفة',
                'balance_after' => $balanceAfterCost,
                'transaction_date' => now(),
            ]);

            if ($pointsWon > 0) {
                PointsTransaction::create([
                    'customer_id' => $customer->id,
                    'type' => 'earn',
                    'amount' => $pointsWon,
                    'description' => 'عجلة الحظ — جائزة نقاط',
                    'balance_after' => $customer->points_balance,
                    'transaction_date' => now(),
                ]);
            }

            $spin = WheelSpin::create([
                'customer_id' => $customer->id,
                'rank_id' => $rank->id,
                'wheel_prize_id' => $prize?->id,
                'points_cost' => $cost,
                'points_won' => $pointsWon,
                'prize_type' => $prizeType,
                'prize_value' => $prizeValue,
                'is_win' => $isWin,
                'probability_used' => $probability,
                'spun_at' => now(),
            ]);

            if ($isWin && $prize && in_array($prizeType, ['coupon', 'discount', 'product'], true)) {
                $reward = $this->grantReward($customer, $prize, $spin, $product);
                if ($reward) {
                    $spin->update([
                        'customer_reward_id' => $reward->id,
                        'prize_value' => $reward->code ?? $spin->prize_value,
                    ]);
                }
            }

            return [
                'spin' => $spin->fresh(['prize', 'rank', 'customer.user', 'reward']),
                'prize' => $prize,
            ];
        });
    }

    public function pickPrize(): ?WheelPrize
    {
        $prizes = WheelPrize::query()->available()->orderBy('sort_order')->get();
        if ($prizes->isEmpty()) {
            return null;
        }

        $total = (int) $prizes->sum('weight');
        if ($total <= 0) {
            return null;
        }

        $roll = mt_rand(1, $total);
        $cursor = 0;

        foreach ($prizes as $prize) {
            $cursor += (int) $prize->weight;
            if ($roll <= $cursor) {
                return $prize;
            }
        }

        return $prizes->last();
    }

    /**
     * Turn a redeemable wheel prize into a customer-owned reward row.
     */
    public function grantReward(
        Customer $customer,
        WheelPrize $prize,
        WheelSpin $spin,
        ?Product $product = null,
    ): ?CustomerReward {
        $prize->loadMissing('coupon');

        return match ($prize->type) {
            'coupon' => $this->grantCouponReward($customer, $prize, $spin),
            'discount' => $this->grantDiscountReward($customer, $prize, $spin),
            'product' => $this->grantProductReward($customer, $prize, $spin, $product),
            default => null,
        };
    }

    protected function grantCouponReward(Customer $customer, WheelPrize $prize, WheelSpin $spin): ?CustomerReward
    {
        $coupon = $prize->coupon;
        if (! $coupon) {
            return null;
        }

        return CustomerReward::create([
            'customer_id' => $customer->id,
            'type' => CustomerReward::TYPE_COUPON,
            'status' => CustomerReward::STATUS_AVAILABLE,
            'source' => 'wheel',
            'code' => $this->makeCode('MQ'),
            'coupon_id' => $coupon->id,
            'wheel_spin_id' => $spin->id,
            'amount_type' => $coupon->type,
            'amount_value' => $coupon->value,
            'expires_at' => $coupon->valid_to,
        ]);
    }

    protected function grantDiscountReward(Customer $customer, WheelPrize $prize, WheelSpin $spin): CustomerReward
    {
        $days = (int) (SystemSetting::where('key', 'wheel_discount_expiry_days')->value('value') ?? 30);

        return CustomerReward::create([
            'customer_id' => $customer->id,
            'type' => CustomerReward::TYPE_DISCOUNT,
            'status' => CustomerReward::STATUS_AVAILABLE,
            'source' => 'wheel',
            'code' => $this->makeCode('WD'),
            'wheel_spin_id' => $spin->id,
            'amount_type' => $prize->discount_type,
            'amount_value' => $prize->discount_value,
            'expires_at' => now()->addDays(max(1, $days)),
        ]);
    }

    protected function grantProductReward(
        Customer $customer,
        WheelPrize $prize,
        WheelSpin $spin,
        ?Product $product,
    ): ?CustomerReward {
        $product ??= $prize->product_id
            ? Product::query()->find($prize->product_id)
            : null;

        if (! $product) {
            return null;
        }

        return CustomerReward::create([
            'customer_id' => $customer->id,
            'type' => CustomerReward::TYPE_PRODUCT,
            'status' => CustomerReward::STATUS_AVAILABLE,
            'source' => 'wheel',
            'code' => $this->makeCode('GP'),
            'product_id' => $product->id,
            'wheel_spin_id' => $spin->id,
            'expires_at' => null,
        ]);
    }

    protected function makeCode(string $prefix): string
    {
        do {
            $code = strtoupper($prefix).'-'.Str::upper(Str::random(8));
        } while (CustomerReward::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * @return array{0: string, 1: ?string, 2: int, 3: ?Product}
     */
    protected function applyPrize(WheelPrize $prize): array
    {
        return match ($prize->type) {
            'points' => [
                'points',
                (string) $prize->points_amount,
                (int) $prize->points_amount,
                null,
            ],
            'coupon' => [
                'coupon',
                $prize->coupon?->code ?? (string) $prize->coupon_id,
                0,
                null,
            ],
            'product' => $this->applyProductPrize($prize),
            'discount' => [
                'discount',
                json_encode([
                    'type' => $prize->discount_type,
                    'value' => (float) $prize->discount_value,
                ], JSON_UNESCAPED_UNICODE),
                0,
                null,
            ],
            default => ['none', null, 0, null],
        };
    }

    /**
     * Reserve stock on win; ownership lives on customer_rewards.
     *
     * @return array{0: string, 1: ?string, 2: int, 3: ?Product}
     */
    protected function applyProductPrize(WheelPrize $prize): array
    {
        $product = $prize->product_id
            ? Product::query()->lockForUpdate()->find($prize->product_id)
            : null;

        if ($product && $product->stock_quantity > 0) {
            $product->decrement('stock_quantity');
        }

        return [
            'product',
            $product
                ? ($product->sku ?: (string) $product->id)
                : (string) $prize->product_id,
            0,
            $product,
        ];
    }
}

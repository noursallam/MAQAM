<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PointsTransaction;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\WheelPrize;
use App\Models\WheelSpin;
use Illuminate\Support\Facades\DB;
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

            if ($isWin) {
                $prize = $this->pickPrize();
                if (! $prize) {
                    $isWin = false;
                } else {
                    [$prizeType, $prizeValue, $pointsWon] = $this->applyPrize($prize);
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

            return ['spin' => $spin->fresh(['prize', 'rank', 'customer.user']), 'prize' => $prize];
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
     * @return array{0: string, 1: ?string, 2: int}
     */
    protected function applyPrize(WheelPrize $prize): array
    {
        return match ($prize->type) {
            'points' => [
                'points',
                (string) $prize->points_amount,
                (int) $prize->points_amount,
            ],
            'coupon' => [
                'coupon',
                $prize->coupon?->code ?? (string) $prize->coupon_id,
                0,
            ],
            'product' => $this->applyProductPrize($prize),
            'discount' => [
                'discount',
                json_encode([
                    'type' => $prize->discount_type,
                    'value' => (float) $prize->discount_value,
                ], JSON_UNESCAPED_UNICODE),
                0,
            ],
            default => ['none', null, 0],
        };
    }

    /**
     * @return array{0: string, 1: ?string, 2: int}
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
        ];
    }
}

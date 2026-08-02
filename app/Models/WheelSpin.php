<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WheelSpin extends Model
{
    protected $fillable = [
        'customer_id', 'rank_id', 'wheel_prize_id', 'customer_reward_id', 'points_cost',
        'points_won', 'prize_type', 'prize_value', 'is_win', 'probability_used', 'spun_at',
    ];

    protected function casts(): array
    {
        return [
            'is_win' => 'boolean',
            'probability_used' => 'float',
            'spun_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(WheelPrize::class, 'wheel_prize_id');
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(CustomerReward::class, 'customer_reward_id');
    }
}

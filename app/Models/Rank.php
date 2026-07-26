<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends Model
{
    protected $fillable = [
        'name_en', 'name_ar', 'min_points', 'max_points',
        'customer_points_per_scan', 'merchant_points_per_scan',
        'wheel_win_probability', 'wheel_cost_points', 'icon_url', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'wheel_win_probability' => 'float',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}

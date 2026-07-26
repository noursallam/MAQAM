<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id', 'path', 'is_thumbnail', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_thumbnail' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return app(\App\Services\MediaService::class)->url($this->path);
    }
}

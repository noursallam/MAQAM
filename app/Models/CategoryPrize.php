<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CategoryPrize extends Model
{
    protected $table = 'categories_prize';

    protected $fillable = [
        'name_en', 'name_ar', 'category_type', 'points_value',
        'background_color', 'icon', 'image_path', 'image_url', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'points_value' => 'integer',
        ];
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'category_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        $mediaService = app(\App\Services\MediaService::class);

        if (!empty($this->image_path)) {
            return $mediaService->url($this->image_path);
        }
        
        if (!empty($this->image_url) && !str_contains($this->image_url, 'identity/MAQAM-24.jpg')) {
            return $mediaService->url($this->image_url);
        }
        
        return null;
    }

    public function hasImage(): bool
    {
        if (!empty($this->image_path)) {
            return true;
        }

        if (!empty($this->image_url) && !str_contains($this->image_url, 'identity/MAQAM-24.jpg')) {
            return true;
        }

        return false;
    }
}

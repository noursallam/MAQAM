<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    protected $fillable = [
        'name_en', 'name_ar', 'slug', 'parent_id', 'icon', 'image_path', 'image_url', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getImageUrlAttribute(): string
    {
        $mediaService = app(\App\Services\MediaService::class);

        if (!empty($this->image_path)) {
            return $mediaService->url($this->image_path);
        }
        
        if (!empty($this->image_url)) {
            return $mediaService->url($this->image_url);
        }
        
        return asset('identity/MAQAM-24.jpg');
    }

    public function hasImage(): bool
    {
        return !empty($this->image_path) || !empty($this->image_url);
    }
}

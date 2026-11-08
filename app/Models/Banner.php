<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    public const PLATFORM_MOBILE = 'mobile_app';

    public const SLOT_WEBSITE_HERO = 'website_hero';

    public const SLOTS = [self::PLATFORM_MOBILE, self::SLOT_WEBSITE_HERO];

    protected $fillable = [
        'slot', 'title_ar', 'title_en', 'image_path', 'link_url', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeMobile(Builder $query): Builder
    {
        return $query->where('slot', self::PLATFORM_MOBILE);
    }

    public function scopeWebsiteHero(Builder $query): Builder
    {
        return $query->where('slot', self::SLOT_WEBSITE_HERO);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        if (str_starts_with($this->image_path, 'identity/') || str_starts_with($this->image_path, 'store/')) {
            return asset($this->image_path);
        }

        return Storage::disk('public')->url($this->image_path);
    }
}

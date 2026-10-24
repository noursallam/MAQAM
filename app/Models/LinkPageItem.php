<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LinkPageItem extends Model
{
    /** App stores: shown as the big download buttons at the top. */
    public const APP_STORES = ['android', 'ios'];

    /** Social accounts: shown as the round icons. */
    public const SOCIAL = ['instagram', 'facebook', 'tiktok', 'whatsapp', 'youtube', 'x', 'snapchat', 'telegram'];

    public const PLATFORMS = ['android', 'ios', 'website', 'instagram', 'facebook', 'tiktok', 'whatsapp', 'youtube', 'x', 'snapchat', 'telegram', 'custom'];

    protected $fillable = ['platform', 'label_ar', 'label_en', 'url', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The button text: the admin's own wording, else the platform's usual name.
     */
    public function label(): string
    {
        $own = app()->getLocale() === 'en' ? ($this->label_en ?: $this->label_ar) : ($this->label_ar ?: $this->label_en);

        return $own ?: __('store.links.platform_'.$this->platform);
    }
}

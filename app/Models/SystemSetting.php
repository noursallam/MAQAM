<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'key', 'value', 'group', 'description', 'is_public',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting.{$key}", 3600, fn () => static::query()->where('key', $key)->first());

        return $setting?->value ?? $default;
    }
}

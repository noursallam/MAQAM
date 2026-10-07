<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id', 'token', 'platform', 'last_used_at',
    ];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Attach a push token to a user. A token belongs to one account at a time.
     */
    public static function register(User $user, string $token, ?string $platform = null): void
    {
        static::updateOrCreate(
            ['token' => $token],
            ['user_id' => $user->id, 'platform' => $platform, 'last_used_at' => now()],
        );
    }
}

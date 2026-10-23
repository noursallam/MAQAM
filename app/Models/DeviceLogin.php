<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A phone allowed to sign back in with its own secret after the customer
 * confirms themselves on it (fingerprint, Face ID or the app PIN).
 */
class DeviceLogin extends Model
{
    protected $fillable = ['user_id', 'device_name', 'secret_hash', 'last_used_at'];

    protected $hidden = ['secret_hash'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hash(string $secret): string
    {
        return hash('sha256', $secret);
    }
}

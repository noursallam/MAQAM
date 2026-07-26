<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'phone_number',
        'email',
        'password',
        'full_name',
        'role',
        'is_active',
        'phone_verified_at',
        'last_login_at',
        'device_token',
        'preferred_language',
        'face_id_enabled',
        'face_id_token',
        'otp_code',
        'otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
        'face_id_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'face_id_enabled' => 'boolean',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'otp_expires_at' => 'datetime',
        ];
    }

    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' && $this->admin !== null;
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function merchant(): HasOne
    {
        return $this->hasOne(Merchant::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }
}

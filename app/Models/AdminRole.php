<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRole extends Model
{
    public const SUPER_ADMIN = 'super_admin';

    protected $fillable = ['key', 'name', 'modules', 'is_system'];

    protected function casts(): array
    {
        return [
            'modules' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function admins(): HasMany
    {
        return $this->hasMany(Admin::class, 'role', 'key');
    }

    public function isSuperAdmin(): bool
    {
        return $this->key === self::SUPER_ADMIN;
    }
}

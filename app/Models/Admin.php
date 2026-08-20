<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Support\AdminAccess;

class Admin extends Model
{
    protected $fillable = [
        'user_id', 'role', 'permissions', 'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'last_activity_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canAccess(string $module): bool
    {
        return in_array($module, AdminAccess::modulesFor($this), true);
    }
}

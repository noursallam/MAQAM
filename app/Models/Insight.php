<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Insight extends Model
{
    protected $fillable = [
        'key',
        'type',
        'locale',
        'content',
        'source',
        'metrics_snapshot',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'metrics_snapshot' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function isFresh(): bool
    {
        return $this->expires_at instanceof Carbon && $this->expires_at->isFuture();
    }
}

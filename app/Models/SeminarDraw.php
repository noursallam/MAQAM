<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeminarDraw extends Model
{
    protected $fillable = ['seminar_id', 'position', 'prize', 'winner_user_id', 'drawn_at'];

    protected function casts(): array
    {
        return ['drawn_at' => 'datetime'];
    }

    public function seminar(): BelongsTo
    {
        return $this->belongsTo(Seminar::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }
}

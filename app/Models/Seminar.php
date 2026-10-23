<?php

namespace App\Models;

use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seminar extends Model
{
    public const UPCOMING = 'upcoming';
    public const LIVE = 'live';
    public const ENDED = 'ended';

    protected $fillable = [
        'title', 'description', 'location', 'image_path', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function draws(): HasMany
    {
        return $this->hasMany(SeminarDraw::class)->orderBy('position');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(SeminarRegistration::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'seminar_registrations')->withTimestamps();
    }

    /**
     * Running now: customers can see it and sign up.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    public function status(): string
    {
        return match (true) {
            $this->starts_at->isFuture() => self::UPCOMING,
            $this->ends_at->isPast() => self::ENDED,
            default => self::LIVE,
        };
    }

    public function isLive(): bool
    {
        return $this->status() === self::LIVE;
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? app(MediaService::class)->url($this->image_path) : null;
    }

    /**
     * Prizes stay editable until the first one is drawn.
     */
    public function hasDrawn(): bool
    {
        return $this->draws()->whereNotNull('drawn_at')->exists();
    }
}

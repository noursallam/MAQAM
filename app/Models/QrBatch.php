<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QrBatch extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    public const STATUS_ZIP_MISSING = 'zip_missing';

    protected $fillable = [
        'batch_id',
        'category_id',
        'quantity',
        'processed_count',
        'status',
        'notes',
        'error_message',
        'zip_ready_at',
    ];

    protected function casts(): array
    {
        return [
            'zip_ready_at' => 'datetime',
            'quantity' => 'integer',
            'processed_count' => 'integer',
        ];
    }

    public function categoryPrize(): BelongsTo
    {
        return $this->belongsTo(CategoryPrize::class, 'category_id');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(QrCode::class, 'batch_id', 'batch_id');
    }

    public function zipPath(): string
    {
        return storage_path('app/qr-batches/'.$this->batch_id.'.zip');
    }

    public function zipExists(): bool
    {
        return is_file($this->zipPath());
    }

    public function isBuilding(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PROCESSING], true);
    }

    public function progressPercent(): int
    {
        if ($this->quantity <= 0) {
            return 0;
        }

        return (int) min(100, floor(($this->processed_count / $this->quantity) * 100));
    }
}

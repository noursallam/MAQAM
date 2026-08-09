<?php

namespace App\Jobs;

use App\Services\QrBatchGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class GenerateQrBatchArtifacts implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Allow up to 15 minutes for 5000 PNG + ZIP builds. */
    public int $timeout = 900;

    public int $tries = 1;

    public function __construct(public string $batchId) {}

    public function handle(QrBatchGenerator $generator): void
    {
        $generator->buildArtifacts($this->batchId);
    }

    public function failed(?Throwable $exception): void
    {
        // Status + error are already persisted inside buildArtifacts on failure.
    }
}

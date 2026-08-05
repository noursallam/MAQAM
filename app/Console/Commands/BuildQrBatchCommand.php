<?php

namespace App\Console\Commands;

use App\Jobs\GenerateQrBatchArtifacts;
use App\Models\QrBatch;
use App\Models\QrCode;
use App\Services\QrBatchGenerator;
use Illuminate\Console\Command;

class BuildQrBatchCommand extends Command
{
    protected $signature = 'qr:build-batch
                            {batchId? : Specific batch ID}
                            {--missing : Rebuild ZIP for every batch that has codes but no ZIP}
                            {--sync : Run in this process instead of the queue}';

    protected $description = 'Build or rebuild QR batch ZIP artifacts';

    public function handle(QrBatchGenerator $generator): int
    {
        $batchIds = [];

        if ($this->option('missing')) {
            $fromCodes = QrCode::query()
                ->whereNotNull('batch_id')
                ->distinct()
                ->pluck('batch_id');

            foreach ($fromCodes as $batchId) {
                if (! is_file(storage_path('app/qr-batches/'.$batchId.'.zip'))) {
                    $batchIds[] = $batchId;
                }
            }
        } elseif ($this->argument('batchId')) {
            $batchIds[] = (string) $this->argument('batchId');
        } else {
            $this->error('Provide a batchId or use --missing.');

            return self::FAILURE;
        }

        if ($batchIds === []) {
            $this->info('Nothing to build.');

            return self::SUCCESS;
        }

        foreach ($batchIds as $batchId) {
            $this->line("Building {$batchId}…");

            if ($this->option('sync')) {
                $generator->buildArtifacts($batchId);
                $this->info("Ready: {$batchId}");
            } else {
                QrBatch::query()->firstOrCreate(
                    ['batch_id' => $batchId],
                    [
                        'category_id' => QrCode::query()->where('batch_id', $batchId)->value('category_id'),
                        'quantity' => QrCode::query()->where('batch_id', $batchId)->count(),
                        'status' => QrBatch::STATUS_QUEUED,
                    ]
                );
                GenerateQrBatchArtifacts::dispatch($batchId);
                $this->info("Queued: {$batchId}");
            }
        }

        return self::SUCCESS;
    }
}

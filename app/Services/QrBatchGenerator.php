<?php

namespace App\Services;

use App\Models\CategoryPrize;
use App\Models\QrBatch;
use App\Models\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

class QrBatchGenerator
{
    public const MAX_BATCH = 5000;

    public const IMAGE_SIZE = 600;

    public const QR_SIZE = 420;

    /** Small chunks so each HTTP request stays under shared-host timeouts. */
    public const HTTP_CHUNK_SIZE = 40;

    /**
     * Create DB records quickly and return a batch ready for HTTP chunk building.
     */
    public function queueGeneration(CategoryPrize $category, int $quantity, ?string $notes = null): QrBatch
    {
        $quantity = max(1, min($quantity, self::MAX_BATCH));
        $batchId = 'BATCH-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        $now = now();

        return DB::transaction(function () use ($category, $quantity, $notes, $batchId, $now) {
            $batch = QrBatch::create([
                'batch_id' => $batchId,
                'category_id' => $category->id,
                'quantity' => $quantity,
                'processed_count' => 0,
                'status' => QrBatch::STATUS_QUEUED,
                'notes' => $notes,
            ]);

            $serials = $this->uniqueSerials($quantity);
            $rows = [];
            foreach ($serials as $serial) {
                $rows[] = [
                    'serial_code' => $serial,
                    'category_id' => $category->id,
                    'points_awarded' => $category->points_value,
                    'status' => 'active',
                    'generated_at' => $now,
                    'batch_id' => $batchId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                QrCode::insert($chunk);
            }

            return $batch;
        });
    }

    /**
     * Reset a batch so ZIP can be rebuilt from existing codes (HTTP chunks).
     */
    public function resetForRebuild(string $batchId): QrBatch
    {
        $categoryId = QrCode::query()->where('batch_id', $batchId)->value('category_id');
        $quantity = QrCode::query()->where('batch_id', $batchId)->count();

        if ($quantity === 0 || ! $categoryId) {
            throw new RuntimeException('No QR codes found for batch '.$batchId);
        }

        $zipPath = storage_path('app/qr-batches/'.$batchId.'.zip');
        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        $dir = storage_path('app/qr-batches/'.$batchId);
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }

        return QrBatch::query()->updateOrCreate(
            ['batch_id' => $batchId],
            [
                'category_id' => $categoryId,
                'quantity' => $quantity,
                'processed_count' => 0,
                'status' => QrBatch::STATUS_QUEUED,
                'error_message' => null,
                'zip_ready_at' => null,
            ]
        );
    }

    /**
     * Backwards-compatible sync generate.
     *
     * @return array{batch_id: string, count: int, zip_path: string, zip_url: string, background_color: string}
     */
    public function generate(CategoryPrize $category, int $quantity): array
    {
        $batch = $this->queueGeneration($category, $quantity);
        $this->buildArtifacts($batch->batch_id);
        $batch->refresh();

        $bgHex = $this->normalizeHex($category->background_color ?: '#C5A059');

        return [
            'batch_id' => $batch->batch_id,
            'count' => $batch->quantity,
            'zip_path' => $batch->zipPath(),
            'zip_url' => route('admin.qr-codes.download', $batch->batch_id),
            'background_color' => $bgHex,
        ];
    }

    /**
     * Process the whole batch in this process (CLI / tests).
     */
    public function buildArtifacts(string $batchId): void
    {
        @set_time_limit(0);

        do {
            $batch = $this->processChunk($batchId, 200);
        } while ($batch && $batch->isBuilding());
    }

    /**
     * Process a small chunk of PNGs into the ZIP. Safe for shared hosting HTTP requests.
     * Keep calling until status is ready/failed.
     */
    public function processChunk(string $batchId, int $chunkSize = self::HTTP_CHUNK_SIZE): ?QrBatch
    {
        @set_time_limit(60);

        $claim = DB::transaction(function () use ($batchId) {
            $batch = QrBatch::query()->where('batch_id', $batchId)->lockForUpdate()->first();
            $zipPath = storage_path('app/qr-batches/'.$batchId.'.zip');

            if ($batch && $batch->status === QrBatch::STATUS_READY && is_file($zipPath)) {
                return ['done' => true, 'batch' => $batch];
            }

            $totalCodes = QrCode::query()->where('batch_id', $batchId)->count();
            if ($totalCodes === 0) {
                if ($batch) {
                    $batch->update([
                        'status' => QrBatch::STATUS_FAILED,
                        'error_message' => 'No QR codes found for this batch.',
                    ]);

                    return ['done' => true, 'batch' => $batch->fresh()];
                }
                throw new RuntimeException('No QR codes found for batch '.$batchId);
            }

            $firstCode = QrCode::query()->where('batch_id', $batchId)->orderBy('id')->first();
            $category = CategoryPrize::find($batch?->category_id ?: $firstCode?->category_id);

            if (! $category) {
                $batch?->update([
                    'status' => QrBatch::STATUS_FAILED,
                    'error_message' => 'Prize category missing for this batch.',
                ]);
                throw new RuntimeException('Prize category missing for batch '.$batchId);
            }

            if (! $batch) {
                $batch = QrBatch::create([
                    'batch_id' => $batchId,
                    'category_id' => $category->id,
                    'quantity' => $totalCodes,
                    'processed_count' => 0,
                    'status' => QrBatch::STATUS_QUEUED,
                ]);
            } else {
                $batch->update(['quantity' => $totalCodes]);
            }

            if ($batch->processed_count <= 0 && is_file($zipPath)) {
                @unlink($zipPath);
            }

            $batch->update([
                'status' => QrBatch::STATUS_PROCESSING,
                'error_message' => null,
            ]);

            return [
                'done' => false,
                'batch' => $batch->fresh(),
                'category' => $category,
                'offset' => max(0, (int) $batch->fresh()->processed_count),
                'total' => $totalCodes,
                'zip_path' => $zipPath,
            ];
        });

        if ($claim['done']) {
            return $claim['batch'];
        }

        return $this->renderClaimedChunk($claim, $chunkSize);
    }

    /**
     * @param  array{batch: QrBatch, category: CategoryPrize, offset: int, total: int, zip_path: string}  $claim
     */
    protected function renderClaimedChunk(array $claim, int $chunkSize): QrBatch
    {
        /** @var QrBatch $batch */
        $batch = $claim['batch'];
        $batchId = $batch->batch_id;
        $offset = $claim['offset'];
        $totalCodes = $claim['total'];
        $zipPath = $claim['zip_path'];
        $category = $claim['category'];

        $codes = QrCode::query()
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->skip($offset)
            ->take(max(1, $chunkSize))
            ->get(['id', 'serial_code', 'category_id', 'points_awarded', 'status', 'generated_at', 'batch_id']);

        if ($codes->isEmpty()) {
            return $this->finalizeZip($batch, $category);
        }

        $bgHex = $this->normalizeHex($category->background_color ?: '#C5A059');
        $writer = new PngWriter;

        try {
            $zip = new ZipArchive;
            $flags = ($offset === 0 || ! is_file($zipPath))
                ? (ZipArchive::CREATE | ZipArchive::OVERWRITE)
                : 0;

            if ($zip->open($zipPath, $flags) !== true) {
                throw new RuntimeException('Unable to open ZIP archive.');
            }

            foreach ($codes as $code) {
                $png = $this->renderPngWithBackground($writer, $code->serial_code, $bgHex);
                $name = $code->serial_code.'.png';
                $zip->addFromString($name, $png);
                if (method_exists($zip, 'setCompressionName')) {
                    $zip->setCompressionName($name, ZipArchive::CM_STORE);
                }
            }

            $zip->close();

            $processed = $offset + $codes->count();

            DB::transaction(function () use ($batchId, $offset, $processed) {
                $locked = QrBatch::query()->where('batch_id', $batchId)->lockForUpdate()->first();
                if (! $locked || (int) $locked->processed_count !== $offset) {
                    return;
                }
                $locked->update(['processed_count' => $processed]);
            });

            $batch = $batch->fresh();

            if ($processed >= $totalCodes) {
                return $this->finalizeZip($batch, $category);
            }

            return $batch;
        } catch (Throwable $e) {
            $batch->update([
                'status' => QrBatch::STATUS_FAILED,
                'error_message' => Str::limit($e->getMessage(), 1000),
            ]);

            throw $e;
        }
    }

    protected function finalizeZip(QrBatch $batch, CategoryPrize $category): QrBatch
    {
        $batchId = $batch->batch_id;
        $zipPath = storage_path('app/qr-batches/'.$batchId.'.zip');
        $bgHex = $this->normalizeHex($category->background_color ?: '#C5A059');

        $codes = QrCode::query()
            ->where('batch_id', $batchId)
            ->orderBy('id')
            ->get(['serial_code', 'category_id', 'points_awarded', 'status', 'generated_at', 'batch_id']);

        $jsonBackup = [
            'batch_id' => $batchId,
            'generated_at' => $codes->sortBy('generated_at')->first()?->generated_at?->toIso8601String()
                ?? now()->toIso8601String(),
            'category' => [
                'id' => $category->id,
                'name_ar' => $category->name_ar,
                'name_en' => $category->name_en,
                'points_value' => $category->points_value,
                'background_color' => $bgHex,
            ],
            'quantity' => $codes->count(),
            'codes' => $codes->map(fn (QrCode $code) => [
                'serial_code' => $code->serial_code,
                'category_id' => $code->category_id,
                'points_awarded' => $code->points_awarded,
                'status' => $code->status,
                'generated_at' => $code->generated_at?->toDateTimeString(),
                'batch_id' => $code->batch_id,
            ])->values()->all(),
        ];

        $zip = new ZipArchive;
        if (! is_file($zipPath) || $zip->open($zipPath) !== true) {
            throw new RuntimeException('ZIP archive missing while finalizing.');
        }

        $jsonName = $batchId.'.json';
        $zip->addFromString(
            $jsonName,
            json_encode($jsonBackup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}'
        );
        if (method_exists($zip, 'setCompressionName')) {
            $zip->setCompressionName($jsonName, ZipArchive::CM_STORE);
        }
        $zip->close();

        $dir = storage_path('app/qr-batches/'.$batchId);
        if (is_dir($dir)) {
            File::deleteDirectory($dir);
        }

        $batch->update([
            'status' => QrBatch::STATUS_READY,
            'processed_count' => $codes->count(),
            'zip_ready_at' => now(),
            'error_message' => null,
        ]);

        return $batch->fresh();
    }

    public function renderPngWithBackground(PngWriter $writer, string $serial, string $bgHex): string
    {
        [$br, $bg, $bb] = $this->hexToRgb($bgHex);

        $qr = new EndroidQrCode(
            data: $serial,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: self::QR_SIZE,
            margin: 12,
            foregroundColor: new Color(11, 15, 25),
            backgroundColor: new Color(255, 255, 255),
        );

        $qrResult = $writer->write($qr);
        $qrImage = imagecreatefromstring($qrResult->getString());
        if ($qrImage === false) {
            throw new RuntimeException('Failed to create QR image.');
        }

        $canvas = imagecreatetruecolor(self::IMAGE_SIZE, self::IMAGE_SIZE);
        $bgColor = imagecolorallocate($canvas, $br, $bg, $bb);
        imagefilledrectangle($canvas, 0, 0, self::IMAGE_SIZE, self::IMAGE_SIZE, $bgColor);

        $qrW = imagesx($qrImage);
        $qrH = imagesy($qrImage);
        $offsetX = (int) ((self::IMAGE_SIZE - $qrW) / 2);
        $offsetY = (int) ((self::IMAGE_SIZE - $qrH) / 2);
        imagecopy($canvas, $qrImage, $offsetX, $offsetY, 0, 0, $qrW, $qrH);

        ob_start();
        imagepng($canvas, null, 6);
        $png = ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($canvas);

        return $png ?: '';
    }

    /**
     * @return list<string>
     */
    public function uniqueSerials(int $count): array
    {
        $serials = [];

        while (count($serials) < $count) {
            $needed = $count - count($serials);
            $candidates = [];

            for ($i = 0; $i < $needed + 32; $i++) {
                $candidates[] = $this->randomSerial();
            }

            $candidates = array_values(array_unique($candidates));
            $existing = QrCode::query()
                ->whereIn('serial_code', $candidates)
                ->pluck('serial_code')
                ->all();
            $existingLookup = array_fill_keys($existing, true);

            foreach ($candidates as $candidate) {
                if (isset($existingLookup[$candidate]) || isset($serials[$candidate])) {
                    continue;
                }
                $serials[$candidate] = true;
                if (count($serials) >= $count) {
                    break;
                }
            }
        }

        return array_keys($serials);
    }

    public function uniqueSerial(): string
    {
        return $this->uniqueSerials(1)[0];
    }

    protected function randomSerial(): string
    {
        $bytes = random_bytes(16);
        $serial = '';
        for ($i = 0; $i < 16; $i++) {
            $serial .= (string) (ord($bytes[$i]) % 10);
        }

        return $serial;
    }

    public function normalizeHex(string $hex): string
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return '#C5A059';
        }

        return '#'.strtoupper($hex);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}

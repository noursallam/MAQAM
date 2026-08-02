<?php

namespace App\Services;

use App\Models\CategoryPrize;
use App\Models\QrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class QrBatchGenerator
{
    public const MAX_BATCH = 5000;

    public const IMAGE_SIZE = 600;

    public const QR_SIZE = 420;

    /**
     * Generate a batch of QR codes with category background color PNGs and a ZIP archive.
     *
     * @return array{batch_id: string, count: int, zip_path: string, zip_url: string}
     */
    public function generate(CategoryPrize $category, int $quantity): array
    {
        $quantity = max(1, min($quantity, self::MAX_BATCH));
        $batchId = 'BATCH-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        $bgHex = $this->normalizeHex($category->background_color ?: '#C5A059');

        $dir = storage_path('app/qr-batches/'.$batchId);
        File::ensureDirectoryExists($dir);

        $codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $serial = $this->uniqueSerial();
            $codes[] = [
                'serial_code' => $serial,
                'category_id' => $category->id,
                'points_awarded' => $category->points_value,
                'status' => 'active',
                'generated_at' => now(),
                'batch_id' => $batchId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($codes, 500) as $chunk) {
            QrCode::insert($chunk);
        }

        $writer = new PngWriter;
        foreach ($codes as $row) {
            $png = $this->renderPngWithBackground($writer, $row['serial_code'], $bgHex);
            file_put_contents($dir.'/'.$row['serial_code'].'.png', $png);
        }

        $zipPath = storage_path('app/qr-batches/'.$batchId.'.zip');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach (File::files($dir) as $file) {
            $zip->addFile($file->getPathname(), $file->getFilename());
        }
        
        // Add JSON backup file
        $jsonBackup = [
            'batch_id' => $batchId,
            'generated_at' => now()->toIso8601String(),
            'category' => [
                'id' => $category->id,
                'name_ar' => $category->name_ar,
                'name_en' => $category->name_en,
                'points_value' => $category->points_value,
                'background_color' => $bgHex,
            ],
            'quantity' => $quantity,
            'codes' => $codes,
        ];
        $jsonPath = $dir.'/'.$batchId.'.json';
        file_put_contents($jsonPath, json_encode($jsonBackup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFile($jsonPath, $batchId.'.json');
        
        $zip->close();

        return [
            'batch_id' => $batchId,
            'count' => $quantity,
            'zip_path' => $zipPath,
            'zip_url' => route('admin.qr-codes.download', $batchId),
            'background_color' => $bgHex,
        ];
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
            throw new \RuntimeException('Failed to create QR image.');
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
        imagepng($canvas);
        $png = ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($canvas);

        return $png ?: '';
    }

    public function uniqueSerial(): string
    {
        do {
            // Cryptographically random 16 digits — never sequential
            $bytes = random_bytes(16);
            $serial = '';
            for ($i = 0; $i < 16; $i++) {
                $serial .= (string) (ord($bytes[$i]) % 10);
            }
        } while (QrCode::where('serial_code', $serial)->exists());

        return $serial;
    }

    protected function normalizeHex(string $hex): string
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

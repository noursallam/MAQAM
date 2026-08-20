<?php

namespace App\Services;

use App\Models\Product;
use Picqer\Barcode\BarcodeGeneratorPNG;
use RuntimeException;
use ZipArchive;

class ProductBarcodeService
{
    public function png(string $code, int $barWidth = 2, int $barHeight = 80): string
    {
        $code = trim($code);
        if ($code === '') {
            throw new RuntimeException('Empty barcode value.');
        }

        $generator = new BarcodeGeneratorPNG;
        $bars = $generator->getBarcode($code, $generator::TYPE_CODE_128, $barWidth, $barHeight);

        $barcode = imagecreatefromstring($bars);
        if ($barcode === false) {
            throw new RuntimeException('Unable to render barcode.');
        }

        $barW = imagesx($barcode);
        $barH = imagesy($barcode);
        $paddingX = 24;
        $paddingTop = 16;
        $textArea = 36;
        $canvasW = max($barW + ($paddingX * 2), 220);
        $canvasH = $paddingTop + $barH + $textArea;

        $canvas = imagecreatetruecolor($canvasW, $canvasH);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 17, 17, 17);
        imagefilledrectangle($canvas, 0, 0, $canvasW, $canvasH, $white);

        $offsetX = (int) (($canvasW - $barW) / 2);
        imagecopy($canvas, $barcode, $offsetX, $paddingTop, 0, 0, $barW, $barH);

        $label = $code;
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($label);
        $textX = (int) (($canvasW - $textWidth) / 2);
        $textY = $paddingTop + $barH + 10;
        imagestring($canvas, $font, max(4, $textX), $textY, $label, $black);

        ob_start();
        imagepng($canvas);
        $png = ob_get_clean();

        imagedestroy($barcode);
        imagedestroy($canvas);

        return $png ?: '';
    }

    public function pngForProduct(Product $product): string
    {
        $code = trim((string) ($product->sku ?: $product->catalog_code ?: $product->production_code));
        if ($code === '') {
            throw new RuntimeException('Product has no SKU to barcode.');
        }

        return $this->png($code);
    }

    /**
     * Build a ZIP of barcode PNGs for every product that has an SKU.
     *
     * @return string Absolute path to temp ZIP
     */
    public function exportAllZip(): string
    {
        $products = Product::query()
            ->orderBy('id')
            ->get(['id', 'sku', 'catalog_code', 'production_code', 'name_en', 'name_ar']);

        $dir = storage_path('app/temp');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zipPath = $dir.'/product-barcodes-'.now()->format('Ymd-His').'.zip';
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create barcodes ZIP.');
        }

        $added = 0;
        foreach ($products as $product) {
            $code = trim((string) ($product->sku ?: $product->catalog_code ?: $product->production_code));
            if ($code === '') {
                continue;
            }

            $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', $code) ?: ('product-'.$product->id);
            $png = $this->png($code);
            $zip->addFromString($safe.'.png', $png);
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);
            throw new RuntimeException('No products with SKU found to export.');
        }

        return $zipPath;
    }
}

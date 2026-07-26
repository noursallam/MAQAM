<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Upload a file to organized storage
     *
     * @param UploadedFile $file
     * @param string $directory (e.g., 'categories', 'products', 'prize-categories')
     * @return string|null The file path relative to storage
     */
    public function upload(UploadedFile $file, string $directory): ?string
    {
        try {
            // Validate file
            if (!$file->isValid()) {
                return null;
            }

            // Generate organized path: directory/YYYY/MM/DD/random_filename.ext
            $datePath = now()->format('Y/m/d');
            $filename = $this->generateFilename($file);
            $path = "{$directory}/{$datePath}/{$filename}";

            // Store file
            $storedPath = Storage::disk('public')->putFileAs(
                $directory . '/' . $datePath,
                $file,
                $filename
            );

            return $storedPath;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Delete a file from storage
     *
     * @param string|null $path
     * @return bool
     */
    public function delete(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }

        try {
            return Storage::disk('public')->delete($path);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get public URL for a file
     *
     * @param string|null $path
     * @return string
     */
    public function url(?string $path): string
    {
        if (empty($path)) {
            return asset('identity/MAQAM-24.jpg');
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            if (preg_match('/\/storage\/(.+)$/i', $path, $matches)) {
                $path = $matches[1];
            } else {
                return $path;
            }
        }

        $path = preg_replace('/^\/?storage\//i', '', $path);
        $path = ltrim($path, '/');

        return asset('storage/' . $path);
    }

    /**
     * Generate a unique filename
     *
     * @param UploadedFile $file
     * @return string
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $random = Str::random(8);
        
        return "{$basename}-{$random}.{$extension}";
    }

    /**
     * Validate image file
     *
     * @param UploadedFile $file
     * @return bool
     */
    public function isValidImage(UploadedFile $file): bool
    {
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        return in_array($file->getMimeType(), $allowedMimes);
    }

    /**
     * Get file size in human-readable format
     *
     * @param UploadedFile $file
     * @return string
     */
    public function getFileSize(UploadedFile $file): string
    {
        $bytes = $file->getSize();
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

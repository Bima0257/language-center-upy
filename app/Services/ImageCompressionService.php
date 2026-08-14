<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class ImageCompressionService
{
    private const MAX_DIMENSION = 1920;

    private const KEEP_UNDER_BYTES = 5 * 1024 * 1024; // 5MB

    private const WEBP_QUALITY = 80;

    private const ABSOLUTE_MAX_DIMENSION = 12000; // skip compress if larger (OOM safety)

    /**
     * Compress image to WebP q80 (max 1920px) when beneficial.
     * Returns the new stored path if replaced, null if unchanged.
     */
    public function compress(string $disk, string $path): ?string
    {
        $storage = Storage::disk($disk);
        $fullPath = $storage->path($path);

        if (! file_exists($fullPath)) {
            return null;
        }

        $sizeBytes = filesize($fullPath);

        $info = @getimagesize($fullPath);
        if ($info === false) {
            return null; // not a valid/decodable image
        }

        $width = $info[0];
        $height = $info[1];
        $maxDim = max($width, $height);

        // OOM safety: extremely large dimensions would exhaust GD memory — keep original
        if ($maxDim > self::ABSOLUTE_MAX_DIMENSION) {
            return null;
        }

        // Already small and within dimension limit: keep as-is (avoid quality loss)
        if ($sizeBytes <= self::KEEP_UNDER_BYTES && $maxDim <= self::MAX_DIMENSION) {
            return null;
        }

        $source = @imagecreatefromstring((string) file_get_contents($fullPath));
        if ($source === false) {
            return null;
        }

        $tmpOutput = tempnam(sys_get_temp_dir(), 'img_').'.webp';
        $resized = null;

        try {
            $image = $source;

            if ($maxDim > self::MAX_DIMENSION) {
                $scale = self::MAX_DIMENSION / $maxDim;
                $newW = (int) round($width * $scale);
                $newH = (int) round($height * $scale);

                $resized = imagescale($source, $newW, $newH, IMG_BICUBIC);
                if ($resized !== false) {
                    $image = $resized;
                }
            }

            $ok = imagewebp($image, $tmpOutput, self::WEBP_QUALITY);
        } catch (\Throwable $e) {
            $ok = false;
        } finally {
            if ($resized !== null && $resized !== false && $resized !== $source) {
                imagedestroy($resized);
            }
            imagedestroy($source);
        }

        if (! $ok || ! file_exists($tmpOutput) || filesize($tmpOutput) === 0) {
            @unlink($tmpOutput);

            return null;
        }

        // Safety: only replace if the result is smaller than the original
        if (filesize($tmpOutput) >= $sizeBytes) {
            @unlink($tmpOutput);

            return null;
        }

        $dir = dirname($path);
        $newName = pathinfo($path, PATHINFO_FILENAME).'.webp';

        $storage->delete($path);
        $storage->putFileAs($dir, new File($tmpOutput), $newName);

        @unlink($tmpOutput);

        return trim($dir.'/'.$newName, '/');
    }
}

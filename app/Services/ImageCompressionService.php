<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Format;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;

class ImageCompressionService
{
    public function __construct(
        private ImageManagerInterface $images,
    ) {}

    /**
     * Compress an already stored image to WebP and keep it under the size limit.
     * Returns the new stored path if replaced, null if unchanged.
     */
    public function compress(string $disk, string $path): ?string
    {
        $storage = Storage::disk($disk);
        $fullPath = $storage->path($path);

        if (! is_file($fullPath)) {
            return null;
        }

        $sizeBytes = (int) filesize($fullPath);
        $maxBytes = $this->maxBytes();
        $dimensions = @getimagesize($fullPath);

        if ($dimensions === false) {
            if ($sizeBytes > $maxBytes) {
                throw new \RuntimeException('Berkas gambar tidak valid atau tidak dapat dikompres.');
            }

            return null;
        }

        [$width, $height] = $dimensions;
        $pixelSize = max($width, $height);

        if ($sizeBytes <= $maxBytes && $pixelSize <= $this->maxDimension()) {
            return null;
        }

        if ($pixelSize > $this->absoluteMaxDimension()) {
            throw new \RuntimeException('Dimensi gambar terlalu besar untuk diproses.');
        }

        try {
            $image = $this->images->decodePath($fullPath);
        } catch (\Throwable) {
            throw new \RuntimeException('Berkas gambar tidak valid atau tidak dapat dikompres.');
        }

        $encoded = $this->encodeUnderLimit($image);

        if (strlen($encoded) >= $sizeBytes && $sizeBytes <= $maxBytes) {
            return null;
        }

        $directory = dirname($path);
        $newName = pathinfo($path, PATHINFO_FILENAME).'.webp';
        $newPath = trim(($directory === '.' ? '' : $directory.'/').$newName, '/');

        $storage->put($newPath, $encoded);
        $storage->delete($path);

        return $newPath;
    }

    /**
     * Store an uploaded image as compressed WebP under the size limit.
     */
    public function storeCompressed(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $realPath = $file->getRealPath();
        $dimensions = $realPath === false ? false : @getimagesize($realPath);

        if ($dimensions === false) {
            throw new \RuntimeException('Berkas gambar tidak valid.');
        }

        if (max($dimensions[0], $dimensions[1]) > $this->absoluteMaxDimension()) {
            throw new \RuntimeException('Dimensi gambar terlalu besar untuk diproses.');
        }

        try {
            $image = $this->images->decodeSplFileInfo($file);
        } catch (\Throwable) {
            throw new \RuntimeException('Berkas gambar tidak valid.');
        }

        $encoded = $this->encodeUnderLimit($image);
        $path = trim($directory, '/').'/'.Str::uuid().'.webp';

        Storage::disk($disk)->put($path, $encoded);

        return $path;
    }

    private function encodeUnderLimit(ImageInterface $image): string
    {
        $image->orient();

        $maxDimension = $this->maxDimension();
        $minDimension = $this->minDimension();

        if (max($image->width(), $image->height()) > $maxDimension) {
            $image->scaleDown(width: $maxDimension, height: $maxDimension);
        }

        $qualities = array_unique([
            $this->webpQuality(),
            70,
            60,
            max(30, $this->minQuality()),
        ]);

        foreach ($qualities as $quality) {
            $encoded = $image->encodeUsingFormat(Format::WEBP, quality: $quality)->toString();

            if (strlen($encoded) <= $this->maxBytes()) {
                return $encoded;
            }
        }

        if (max($image->width(), $image->height()) > $minDimension) {
            $image->scaleDown(width: $minDimension, height: $minDimension);

            $encoded = $image
                ->encodeUsingFormat(Format::WEBP, quality: max(30, $this->minQuality()))
                ->toString();

            if (strlen($encoded) <= $this->maxBytes()) {
                return $encoded;
            }
        }

        throw new \RuntimeException('Gambar tidak dapat dikompres hingga di bawah '.$this->maxMegabytes().'MB.');
    }

    private function maxBytes(): int
    {
        return max(1, (int) config('image.max_kb', 2048)) * 1024;
    }

    private function maxMegabytes(): string
    {
        $kilobytes = max(1, (int) config('image.max_kb', 2048));

        return rtrim(rtrim(number_format($kilobytes / 1024, 1, '.', ''), '0'), '.');
    }

    private function maxDimension(): int
    {
        return (int) config('image.max_dimension', 1920);
    }

    private function minDimension(): int
    {
        return (int) config('image.min_dimension', 1280);
    }

    private function webpQuality(): int
    {
        return (int) config('image.webp_quality', 80);
    }

    private function minQuality(): int
    {
        return (int) config('image.min_quality', 50);
    }

    private function absoluteMaxDimension(): int
    {
        return (int) config('image.absolute_max_dimension', 12000);
    }
}

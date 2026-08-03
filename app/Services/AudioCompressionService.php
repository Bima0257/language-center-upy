<?php

namespace App\Services;

use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class AudioCompressionService
{
    public const BITRATE = '128k';
    private const KEEP_SMALL_MP3_BYTES = 8 * 1024 * 1024; // 8MB

    private ?string $binary = null;

    public function __construct()
    {
        $this->binary = $this->locateBinary();
    }

    private function locateBinary(): ?string
    {
        $candidates = [
            'ffmpeg', // resolve via PATH
            'C:\ffmpeg\bin\ffmpeg.exe',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'ffmpeg') {
                $output = [];
                exec('where ffmpeg 2>NUL', $output, $code);
                if ($code === 0 && ! empty($output)) {
                    return trim($output[0]);
                }
                continue;
            }

            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function isAvailable(): bool
    {
        return $this->binary !== null;
    }

    /**
     * Compress audio to MP3 128kbps mono when beneficial.
     * Returns the new stored path if the file was replaced, null if unchanged.
     */
    public function compress(string $disk, string $path): ?string
    {
        if ($this->binary === null) {
            return null;
        }

        $storage = Storage::disk($disk);
        $fullPath = $storage->path($path);

        if (! file_exists($fullPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $sizeBytes = filesize($fullPath);

        // Already-compressed MP3 that is small enough: keep as-is (avoid generation loss)
        if ($extension === 'mp3' && $sizeBytes <= self::KEEP_SMALL_MP3_BYTES) {
            return null;
        }

        $tmpOutput = tempnam(sys_get_temp_dir(), 'audio_').'.mp3';

        $cmd = sprintf(
            '%s -y -i %s -vn -ac 1 -b:a %s -ar 44100 %s 2>&1',
            escapeshellarg($this->binary),
            escapeshellarg($fullPath),
            self::BITRATE,
            escapeshellarg($tmpOutput)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($tmpOutput) || filesize($tmpOutput) === 0) {
            @unlink($tmpOutput);

            return null;
        }

        // Safety: only replace if the result is smaller than the original
        if (filesize($tmpOutput) >= $sizeBytes) {
            @unlink($tmpOutput);

            return null;
        }

        $dir = dirname($path);
        $newName = pathinfo($path, PATHINFO_FILENAME).'.mp3';

        $storage->delete($path);
        $storage->putFileAs($dir, new File($tmpOutput), $newName);

        @unlink($tmpOutput);

        return trim($dir.'/'.$newName, '/');
    }
}

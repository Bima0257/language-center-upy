<?php

namespace Tests\Feature\Upload;

use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadTest extends TestCase
{
    private ImageCompressionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->service = app(ImageCompressionService::class);
    }

    private function buatGambarBesar(string $path, int $width = 3000, int $height = 2000): void
    {
        $image = imagecreatetruecolor($width, $height);
        $this->assertNotFalse($image);

        for ($x = 0; $x < $width; $x += 8) {
            for ($y = 0; $y < $height; $y += 8) {
                $color = imagecolorallocate(
                    $image,
                    random_int(0, 255),
                    random_int(0, 255),
                    random_int(0, 255),
                );

                imagefilledrectangle(
                    $image,
                    $x,
                    $y,
                    min($x + 7, $width - 1),
                    min($y + 7, $height - 1),
                    $color,
                );
            }
        }

        imagejpeg($image, $path, 100);
        imagedestroy($image);
    }

    public function test_kompresi_menghasilkan_webp_di_bawah_batas(): void
    {
        Storage::disk('public')->makeDirectory('pengujian');

        $path = 'pengujian/gambar-besar.jpg';
        $this->buatGambarBesar(Storage::disk('public')->path($path));

        $this->assertGreaterThan(
            2 * 1024 * 1024,
            Storage::disk('public')->size($path),
        );

        $hasil = $this->service->compress('public', $path);

        $this->assertNotNull($hasil);
        $this->assertStringEndsWith('.webp', $hasil);
        $this->assertTrue(Storage::disk('public')->exists($hasil));
        $this->assertLessThanOrEqual(
            2 * 1024 * 1024,
            Storage::disk('public')->size($hasil),
        );

        $ukuran = getimagesize(Storage::disk('public')->path($hasil));

        $this->assertNotFalse($ukuran);
        $this->assertLessThanOrEqual(1920, max($ukuran[0], $ukuran[1]));
    }

    public function test_gambar_kecil_tidak_diubah(): void
    {
        Storage::disk('public')->makeDirectory('pengujian');

        $temp = tempnam(sys_get_temp_dir(), 'img_');
        $image = imagecreatetruecolor(200, 200);
        $this->assertNotFalse($image);
        imagejpeg($image, $temp, 90);
        imagedestroy($image);

        Storage::disk('public')->put('pengujian/kecil.jpg', file_get_contents($temp));
        @unlink($temp);

        $this->assertNull($this->service->compress('public', 'pengujian/kecil.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('pengujian/kecil.jpg'));
    }

    public function test_store_compressed_menghasilkan_berkas_webp(): void
    {
        Storage::disk('public')->makeDirectory('pengujian');

        $temp = tempnam(sys_get_temp_dir(), 'img_');
        $this->buatGambarBesar($temp, 2400, 1600);

        $berkas = new UploadedFile($temp, 'foto.jpg', 'image/jpeg', null, true);
        $path = $this->service->storeCompressed($berkas, 'pengujian');

        $this->assertStringEndsWith('.webp', $path);
        $this->assertTrue(Storage::disk('public')->exists($path));
        $this->assertLessThanOrEqual(
            2 * 1024 * 1024,
            Storage::disk('public')->size($path),
        );

        @unlink($temp);
    }

    public function test_store_compressed_menolak_berkas_bukan_gambar(): void
    {
        Storage::disk('public')->makeDirectory('pengujian');
        Storage::disk('public')->put('pengujian/teks.txt', 'bukan gambar');

        $berkas = new UploadedFile(
            Storage::disk('public')->path('pengujian/teks.txt'),
            'teks.txt',
            'text/plain',
            null,
            true,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Berkas gambar tidak valid.');

        $this->service->storeCompressed($berkas, 'pengujian');
    }
}

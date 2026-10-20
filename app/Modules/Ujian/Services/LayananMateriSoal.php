<?php

namespace App\Modules\Ujian\Services;

use App\Enums\JenisMedia;
use App\Models\MateriSoal;
use App\Modules\Ujian\Repositories\Contracts\MateriSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\MediaMateriSoalRepositoryInterface;
use App\Services\AudioCompressionService;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mews\Purifier\Facades\Purifier;

class LayananMateriSoal
{
    public function __construct(
        private MateriSoalRepositoryInterface $materiSoalRepo,
        private MediaMateriSoalRepositoryInterface $mediaMateriSoalRepo,
        private LayananKebijakanMateriSoal $kebijakanMateri,
        private AudioCompressionService $audioCompression,
        private ImageCompressionService $imageCompression,
    ) {}

    public function simpan(array $validated, ?Request $request): void
    {
        $storedFiles = [];

        try {
            DB::transaction(function () use ($validated, $request, &$storedFiles) {
                $materi = $this->materiSoalRepo->create([
                    'bank_soal_id' => $validated['bank_soal_id'],
                    'judul' => $validated['judul'],
                    'isi_teks' => $this->bersihkanIsi($validated['isi_teks'] ?? null),
                ]);

                $storedFiles = array_merge(
                    $storedFiles,
                    $this->lampirkanMediaUnggahan($materi, $request, 'berkas_audio', 'berkas_gambar'),
                );

                $this->kebijakanMateri->pastikanIsiMateri($materi, 'materi_soal');
            });
        } catch (\Throwable $e) {
            $this->hapusBerkasTersimpan($storedFiles);

            throw $e;
        }
    }

    public function perbarui(MateriSoal $materi, array $validated, ?Request $request): void
    {
        $storedFiles = [];
        $staleFiles = [];

        try {
            DB::transaction(function () use ($materi, $validated, $request, &$storedFiles, &$staleFiles) {
                $this->materiSoalRepo->update($materi, [
                    'judul' => $validated['judul'],
                    'isi_teks' => $this->bersihkanIsi($validated['isi_teks'] ?? null),
                ]);

                if ($request !== null && $request->hasFile('berkas_audio')) {
                    $this->gantiMedia($materi, JenisMedia::AUDIO, $request->file('berkas_audio'), 'passages/audio', $this->audioCompression, $storedFiles, $staleFiles);
                }

                if ($request !== null && $request->hasFile('berkas_gambar')) {
                    $this->gantiMedia($materi, JenisMedia::IMAGE, $request->file('berkas_gambar'), 'passages/images', $this->imageCompression, $storedFiles, $staleFiles);
                }

                $this->kebijakanMateri->pastikanMateriMemenuhiKeterampilan($materi->fresh());
            });
        } catch (\Throwable $e) {
            $this->hapusBerkasTersimpan($storedFiles);

            throw $e;
        }

        $this->hapusBerkasLama($staleFiles, $materi->id);
    }

    public function hapus(MateriSoal $materi): void
    {
        if ($this->materiSoalRepo->memilikiSoal($materi->id)) {
            throw new \RuntimeException('Materi soal tidak bisa dihapus karena masih dipakai oleh soal.');
        }

        $mediaFiles = $materi->mediaMateriSoal->pluck('lokasi_berkas')->all();

        $this->materiSoalRepo->delete($materi);

        $this->hapusBerkasLama($mediaFiles, $materi->id);
    }

    /**
     * Simpan file audio/gambar dari request sebagai baris media materi soal.
     *
     * @return array<string> path file yang tersimpan di disk publik
     */
    public function lampirkanMediaUnggahan(MateriSoal $materi, ?Request $request, string $audioField, string $imageField): array
    {
        $storedFiles = [];

        if ($request !== null && $request->hasFile($audioField)) {
            $storedFiles[] = $this->buatMedia($materi, JenisMedia::AUDIO, $request->file($audioField), 'passages/audio', $this->audioCompression);
        }

        if ($request !== null && $request->hasFile($imageField)) {
            $storedFiles[] = $this->buatMedia($materi, JenisMedia::IMAGE, $request->file($imageField), 'passages/images', $this->imageCompression);
        }

        return $storedFiles;
    }

    private function buatMedia(MateriSoal $materi, JenisMedia $jenis, UploadedFile $file, string $directory, AudioCompressionService|ImageCompressionService $compression): string
    {
        $lokasiBerkas = $this->simpanTerkompresi($file, $directory, $compression);

        $this->mediaMateriSoalRepo->create([
            'materi_soal_id' => $materi->id,
            'jenis_media' => $jenis,
            'lokasi_berkas' => $lokasiBerkas,
            'nama_asli' => $file->getClientOriginalName(),
            'tipe_mime' => $file->getMimeType(),
            'ukuran_bita' => Storage::disk('public')->size($lokasiBerkas),
            'urutan' => $jenis === JenisMedia::AUDIO ? 0 : 1,
        ]);

        return $lokasiBerkas;
    }

    private function gantiMedia(MateriSoal $materi, JenisMedia $jenis, UploadedFile $file, string $directory, AudioCompressionService|ImageCompressionService $compression, array &$storedFiles, array &$staleFiles): void
    {
        $existing = $materi->mediaMateriSoal->firstWhere('jenis_media', $jenis);

        if ($existing !== null) {
            $staleFiles[] = $existing->lokasi_berkas;
        }

        $lokasiBerkas = $this->simpanTerkompresi($file, $directory, $compression);
        $storedFiles[] = $lokasiBerkas;

        $this->mediaMateriSoalRepo->gantiDenganJenis($materi, $jenis, [
            'lokasi_berkas' => $lokasiBerkas,
            'nama_asli' => $file->getClientOriginalName(),
            'tipe_mime' => $file->getMimeType(),
            'ukuran_bita' => Storage::disk('public')->size($lokasiBerkas),
            'urutan' => $jenis === JenisMedia::AUDIO ? 0 : 1,
        ]);
    }

    private function simpanTerkompresi(UploadedFile $file, string $directory, AudioCompressionService|ImageCompressionService $compression): string
    {
        $path = $file->store($directory, 'public');
        $compressedPath = $compression->compress('public', $path);

        return $compressedPath ?? $path;
    }

    /**
     * @param  array<string>  $files
     */
    private function hapusBerkasTersimpan(array $files): void
    {
        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }
    }

    /**
     * @param  array<string>  $files
     */
    private function hapusBerkasLama(array $files, string $materiId): void
    {
        foreach ($files as $file) {
            if (! $this->mediaMateriSoalRepo->lokasiDigunakan($file, $materiId)) {
                Storage::disk('public')->delete($file);
            }
        }
    }

    private function bersihkanIsi(?string $isi): ?string
    {
        if ($isi === null || trim($isi) === '') {
            return null;
        }

        return Purifier::clean($isi);
    }
}

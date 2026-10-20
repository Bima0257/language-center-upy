<?php

namespace App\Models;

use App\Enums\JenisMedia;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Materi yang dapat dipakai oleh soal dalam satu bank soal.
 *
 * @property string $id
 * @property string $bank_soal_id
 * @property string $judul
 * @property string|null $isi_teks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string|null $url_audio
 * @property-read string|null $url_gambar
 * @property-read bool $memiliki_audio
 * @property-read bool $memiliki_gambar
 * @property-read BankSoal $bankSoal
 * @property-read Collection<int, Soal> $soal
 * @property-read Collection<int, MediaMateriSoal> $mediaMateriSoal
 */
class MateriSoal extends Model
{
    use HasUuids;

    protected $table = 'materi_soal';

    protected $fillable = [
        'bank_soal_id',
        'judul',
        'isi_teks',
    ];

    protected $appends = ['url_audio', 'url_gambar', 'memiliki_audio', 'memiliki_gambar'];

    protected function casts(): array
    {
        return [];
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id', 'id');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'materi_soal_id', 'id');
    }

    public function mediaMateriSoal(): HasMany
    {
        return $this->hasMany(MediaMateriSoal::class, 'materi_soal_id', 'id')
            ->orderBy('urutan');
    }

    public function getUrlAudioAttribute(): ?string
    {
        return $this->lokasiMedia(JenisMedia::AUDIO);
    }

    public function getUrlGambarAttribute(): ?string
    {
        return $this->lokasiMedia(JenisMedia::IMAGE);
    }

    public function getMemilikiAudioAttribute(): bool
    {
        return $this->url_audio !== null;
    }

    public function getMemilikiGambarAttribute(): bool
    {
        return $this->url_gambar !== null;
    }

    private function lokasiMedia(JenisMedia $jenisMedia): ?string
    {
        if ($this->relationLoaded('mediaMateriSoal')) {
            return $this->mediaMateriSoal->firstWhere('jenis_media', $jenisMedia)?->lokasi_berkas;
        }

        return $this->mediaMateriSoal()
            ->where('jenis_media', $jenisMedia->value)
            ->value('lokasi_berkas');
    }
}

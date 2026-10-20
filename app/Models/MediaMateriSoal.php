<?php

namespace App\Models;

use App\Enums\JenisMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Berkas media yang terpasang pada materi soal.
 *
 * @property string $id
 * @property string $materi_soal_id
 * @property JenisMedia $jenis_media
 * @property string $lokasi_berkas
 * @property string|null $nama_asli
 * @property string|null $tipe_mime
 * @property int|null $ukuran_bita
 * @property int|null $durasi_detik
 * @property int $urutan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MateriSoal $materiSoal
 */
#[Fillable([
    'materi_soal_id',
    'jenis_media',
    'lokasi_berkas',
    'nama_asli',
    'tipe_mime',
    'ukuran_bita',
    'durasi_detik',
    'urutan',
])]
class MediaMateriSoal extends Model
{
    use HasUuids;

    protected $table = 'media_materi_soal';

    protected function casts(): array
    {
        return [
            'jenis_media' => JenisMedia::class,
            'ukuran_bita' => 'integer',
            'durasi_detik' => 'integer',
            'urutan' => 'integer',
        ];
    }

    public function materiSoal(): BelongsTo
    {
        return $this->belongsTo(MateriSoal::class, 'materi_soal_id', 'id');
    }
}

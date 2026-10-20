<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Soal yang tersimpan dalam bank soal.
 *
 * @property string $id
 * @property string $bank_soal_id
 * @property string|null $materi_soal_id
 * @property string $keterampilan_id
 * @property string|null $bagian_soal_id
 * @property string $teks_soal
 * @property string $pilihan_a
 * @property string $pilihan_b
 * @property string $pilihan_c
 * @property string $pilihan_d
 * @property string $kunci_jawaban
 * @property int $urutan
 * @property string $status
 * @property string|null $pembuat_id
 * @property string|null $pengubah_id
 * @property string|null $peninjau_id
 * @property Carbon|null $ditinjau_pada
 * @property string|null $catatan_tinjauan
 * @property string|null $materi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BankSoal $bankSoal
 * @property-read MateriSoal|null $materiSoal
 * @property-read Keterampilan $keterampilan
 * @property-read BagianSoal|null $bagianSoal
 * @property-read Pengguna|null $pembuat
 * @property-read Pengguna|null $pengubah
 * @property-read Pengguna|null $peninjau
 * @property-read Collection<int, BagianUjian> $bagianUjian
 */
#[Fillable([
    'bank_soal_id', 'materi_soal_id', 'keterampilan_id', 'bagian_soal_id',
    'teks_soal', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d',
    'kunci_jawaban', 'urutan', 'status',
    'pembuat_id', 'pengubah_id', 'peninjau_id', 'ditinjau_pada', 'catatan_tinjauan',
])]
class Soal extends Model
{
    use HasUuids;

    protected $table = 'soal';

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'ditinjau_pada' => 'datetime',
        ];
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id', 'id');
    }

    public function materiSoal(): BelongsTo
    {
        return $this->belongsTo(MateriSoal::class, 'materi_soal_id', 'id');
    }

    public function keterampilan(): BelongsTo
    {
        return $this->belongsTo(Keterampilan::class, 'keterampilan_id', 'id');
    }

    public function bagianSoal(): BelongsTo
    {
        return $this->belongsTo(BagianSoal::class, 'bagian_soal_id', 'id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pembuat_id', 'id');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengubah_id', 'id');
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'peninjau_id', 'id');
    }

    public function bagianUjian(): BelongsToMany
    {
        return $this->belongsToMany(
            BagianUjian::class,
            'soal_bagian_ujian',
            'soal_id',
            'bagian_ujian_id',
            'id',
            'id',
        );
    }
}

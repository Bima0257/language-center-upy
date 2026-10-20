<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bagian ujian yang mengelompokkan soal berdasarkan keterampilan.
 *
 * @property string $id
 * @property string $ujian_id
 * @property string $bank_soal_id
 * @property string $keterampilan_id
 * @property string $judul
 * @property int $urutan
 * @property int $jumlah_soal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ujian $ujian
 * @property-read BankSoal $bankSoal
 * @property-read Keterampilan $keterampilan
 * @property-read Collection<int, SoalBagianUjian> $soalBagianUjian
 * @property-read Collection<int, BagianSoalUjian> $bagianSoalUjian
 */
#[Fillable(['ujian_id', 'bank_soal_id', 'keterampilan_id', 'judul', 'urutan', 'jumlah_soal'])]
class BagianUjian extends Model
{
    use HasUuids;

    protected $table = 'bagian_ujian';

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'jumlah_soal' => 'integer',
        ];
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id', 'id');
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id', 'id');
    }

    public function keterampilan(): BelongsTo
    {
        return $this->belongsTo(Keterampilan::class, 'keterampilan_id', 'id');
    }

    public function soalBagianUjian(): HasMany
    {
        return $this->hasMany(SoalBagianUjian::class, 'bagian_ujian_id', 'id')
            ->orderBy('urutan');
    }

    public function bagianSoalUjian(): HasMany
    {
        return $this->hasMany(BagianSoalUjian::class, 'bagian_ujian_id', 'id')
            ->orderBy('urutan');
    }
}

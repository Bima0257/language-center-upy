<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Keterampilan bahasa yang diuji.
 *
 * @property string $id
 * @property string $jenis_tes_id
 * @property string $kode
 * @property string $nama
 * @property string|null $deskripsi
 * @property int $urutan
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JenisTes $jenisTes
 * @property-read Collection<int, BagianSoal> $bagianSoal
 * @property-read Collection<int, Soal> $soal
 * @property-read Collection<int, BagianUjian> $bagianUjian
 * @property-read Collection<int, KebijakanMateriSoal> $kebijakanMateriSoal
 */
class Keterampilan extends Model
{
    use HasUuids;

    protected $table = 'keterampilan';

    protected $fillable = [
        'jenis_tes_id',
        'kode',
        'nama',
        'deskripsi',
        'urutan',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function bagianSoal(): HasMany
    {
        return $this->hasMany(BagianSoal::class, 'keterampilan_id', 'id');
    }

    public function jenisTes(): BelongsTo
    {
        return $this->belongsTo(JenisTes::class, 'jenis_tes_id', 'id');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'keterampilan_id', 'id');
    }

    public function bagianUjian(): HasMany
    {
        return $this->hasMany(BagianUjian::class, 'keterampilan_id', 'id');
    }

    public function kebijakanMateriSoal(): HasMany
    {
        return $this->hasMany(KebijakanMateriSoal::class, 'keterampilan_id', 'id');
    }
}

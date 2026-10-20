<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bank soal untuk satu jenis tes.
 *
 * @property string $id
 * @property string $jenis_tes_id
 * @property string $nama
 * @property string|null $deskripsi
 * @property bool $aktif
 * @property-read JenisTes $jenisTes
 * @property-read Collection<int, Soal> $soal
 * @property-read Collection<int, BagianSoal> $bagianSoal
 * @property-read Collection<int, BagianUjian> $bagianUjian
 */
class BankSoal extends Model
{
    use HasUuids;

    protected $table = 'bank_soal';

    protected $fillable = [
        'jenis_tes_id',
        'nama',
        'deskripsi',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function jenisTes(): BelongsTo
    {
        return $this->belongsTo(JenisTes::class, 'jenis_tes_id', 'id');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'bank_soal_id', 'id');
    }

    public function bagianSoal(): HasMany
    {
        return $this->hasMany(BagianSoal::class, 'bank_soal_id', 'id');
    }

    public function bagianUjian(): HasMany
    {
        return $this->hasMany(BagianUjian::class, 'bank_soal_id', 'id');
    }
}

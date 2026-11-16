<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bank soal untuk satu program tes.
 *
 * @property string $id
 * @property string $program_tes_id
 * @property string $nama
 * @property string|null $deskripsi
 * @property bool $aktif
 * @property-read int $soal_count
 * @property-read int $part_ujian_count
 * @property-read ProgramTes $programTes
 * @property-read Collection<int, Soal> $soal
 * @property-read Collection<int, PartSoal> $partSoal
 * @property-read Collection<int, PartUjian> $partUjian
 */
class BankSoal extends Model
{
    use HasUuids;

    protected $table = 'bank_soal';

    protected $fillable = [
        'program_tes_id',
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

    public function programTes(): BelongsTo
    {
        return $this->belongsTo(ProgramTes::class, 'program_tes_id', 'id');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'bank_soal_id', 'id');
    }

    public function partSoal(): HasMany
    {
        return $this->hasMany(PartSoal::class, 'bank_soal_id', 'id');
    }

    public function partUjian(): HasMany
    {
        return $this->hasMany(PartUjian::class, 'bank_soal_id', 'id');
    }
}

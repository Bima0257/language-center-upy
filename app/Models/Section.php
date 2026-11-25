<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Section bahasa yang diuji.
 *
 * @property string $id
 * @property string $program_tes_id
 * @property string $kode
 * @property string $nama
 * @property string|null $deskripsi
 * @property int $urutan
 * @property int $durasi_menit
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProgramTes $programTes
 * @property-read Collection<int, PartSoal> $partSoal
 * @property-read Collection<int, Soal> $soal
 * @property-read Collection<int, PartUjian> $partUjian
 * @property-read KebijakanMateriSoal|null $kebijakanMateriSoal
 * @property-read bool $dipakai_ujian_aktif
 */
class Section extends Model
{
    use HasUuids;

    protected $table = 'section';

    protected $fillable = [
        'program_tes_id',
        'kode',
        'nama',
        'deskripsi',
        'urutan',
        'durasi_menit',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'durasi_menit' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function partSoal(): HasMany
    {
        return $this->hasMany(PartSoal::class, 'section_id', 'id');
    }

    public function programTes(): BelongsTo
    {
        return $this->belongsTo(ProgramTes::class, 'program_tes_id', 'id');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'section_id', 'id');
    }

    public function partUjian(): HasMany
    {
        return $this->hasMany(PartUjian::class, 'section_id', 'id');
    }

    public function kebijakanMateriSoal(): HasOne
    {
        return $this->hasOne(KebijakanMateriSoal::class, 'section_id', 'id');
    }
}

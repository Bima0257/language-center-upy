<?php

namespace App\Models;

use App\Enums\LingkupUjian;
use App\Enums\ModeUjian;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Ujian yang dapat dijadwalkan untuk peserta.
 *
 * @property string $id
 * @property string $program_tes_id
 * @property string $judul
 * @property string|null $deskripsi
 * @property ModeUjian $mode
 * @property int $durasi_menit
 * @property LingkupUjian $lingkup
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProgramTes $programTes
 * @property-read Collection<int, PartUjian> $partUjian
 * @property-read Collection<int, PeriodeUjian> $periodeUjian
 * @property-read Collection<int, ProgramStudi> $programStudi
 * @property-read Collection<int, SesiUjian> $sesiUjian
 */
#[Fillable(['program_tes_id', 'judul', 'deskripsi', 'mode', 'durasi_menit', 'lingkup', 'aktif'])]
class Ujian extends Model
{
    use HasUuids;

    protected $table = 'ujian';

    protected $attributes = [
        'lingkup' => LingkupUjian::UMUM->value,
    ];

    protected function casts(): array
    {
        return [
            'mode' => ModeUjian::class,
            'lingkup' => LingkupUjian::class,
            'aktif' => 'boolean',
            'durasi_menit' => 'integer',
        ];
    }

    public function programTes(): BelongsTo
    {
        return $this->belongsTo(ProgramTes::class, 'program_tes_id', 'id');
    }

    public function partUjian(): HasMany
    {
        return $this->hasMany(PartUjian::class, 'ujian_id', 'id');
    }

    public function periodeUjian(): HasMany
    {
        return $this->hasMany(PeriodeUjian::class, 'ujian_id', 'id');
    }

    public function programStudi(): BelongsToMany
    {
        return $this->belongsToMany(ProgramStudi::class, 'ujian_program_studi', 'ujian_id', 'program_studi_id')
            ->withTimestamps();
    }

    public function sesiUjian(): HasMany
    {
        return $this->hasMany(SesiUjian::class, 'ujian_id', 'id');
    }

    public function terbukaUntuk(Pengguna $pengguna): bool
    {
        return $this->terbukaUntukProdi($pengguna->profilPeserta?->program_studi_id);
    }

    public function terbukaUntukProdi(?string $programStudiId): bool
    {
        if ($this->lingkup === LingkupUjian::UMUM) {
            return true;
        }

        if ($programStudiId === null) {
            return false;
        }

        if ($this->relationLoaded('programStudi')) {
            return $this->programStudi->contains('id', $programStudiId);
        }

        return $this->programStudi()->whereKey($programStudiId)->exists();
    }
}

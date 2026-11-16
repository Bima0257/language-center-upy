<?php

namespace App\Models;

use App\Enums\MetodeTotalPenilaian;
use App\Enums\StatusSkemaPenilaian;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Skema aturan penilaian berversi untuk satu program tes.
 *
 * @property string $id
 * @property string $program_tes_id
 * @property int $versi
 * @property string $nama
 * @property StatusSkemaPenilaian $status
 * @property MetodeTotalPenilaian $metode_total
 * @property string|null $skala_total_minimum
 * @property string|null $skala_total_maksimum
 * @property Carbon|null $diaktifkan_pada
 * @property string|null $dibuat_oleh
 * @property string|null $diaktifkan_oleh
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProgramTes $programTes
 * @property-read Collection<int, AturanPenilaian> $aturanPenilaian
 * @property-read Collection<int, InterpretasiSkor> $interpretasiSkor
 * @property-read Collection<int, HasilPenilaian> $hasilPenilaian
 * @property-read Pengguna|null $pembuat
 * @property-read Pengguna|null $pengaktif
 */
class SkemaPenilaian extends Model
{
    use HasUuids;

    protected $table = 'skema_penilaian';

    protected $fillable = [
        'program_tes_id',
        'versi',
        'nama',
        'status',
        'metode_total',
        'skala_total_minimum',
        'skala_total_maksimum',
        'diaktifkan_pada',
        'dibuat_oleh',
        'diaktifkan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'versi' => 'integer',
            'status' => StatusSkemaPenilaian::class,
            'metode_total' => MetodeTotalPenilaian::class,
            'skala_total_minimum' => 'decimal:1',
            'skala_total_maksimum' => 'decimal:1',
            'diaktifkan_pada' => 'datetime',
        ];
    }

    public function programTes(): BelongsTo
    {
        return $this->belongsTo(ProgramTes::class, 'program_tes_id', 'id');
    }

    public function aturanPenilaian(): HasMany
    {
        return $this->hasMany(AturanPenilaian::class, 'skema_penilaian_id', 'id');
    }

    public function interpretasiSkor(): HasMany
    {
        return $this->hasMany(InterpretasiSkor::class, 'skema_penilaian_id', 'id');
    }

    public function hasilPenilaian(): HasMany
    {
        return $this->hasMany(HasilPenilaian::class, 'skema_penilaian_id', 'id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh', 'id');
    }

    public function pengaktif(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diaktifkan_oleh', 'id');
    }
}

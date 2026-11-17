<?php

namespace App\Models;

use App\Enums\MetodeTotalPenilaian;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Skema aturan penilaian untuk satu program tes.
 *
 * @property string $id
 * @property string $program_tes_id
 * @property MetodeTotalPenilaian $metode_total
 * @property string|null $skala_total_minimum
 * @property string|null $skala_total_maksimum
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProgramTes $programTes
 * @property-read Collection<int, AturanPenilaian> $aturanPenilaian
 * @property-read Collection<int, InterpretasiSkor> $interpretasiSkor
 * @property-read Collection<int, HasilPenilaian> $hasilPenilaian
 */
class SkemaPenilaian extends Model
{
    use HasUuids;

    protected $table = 'skema_penilaian';

    protected $fillable = [
        'program_tes_id',
        'metode_total',
        'skala_total_minimum',
        'skala_total_maksimum',
    ];

    protected function casts(): array
    {
        return [
            'metode_total' => MetodeTotalPenilaian::class,
            'skala_total_minimum' => 'decimal:1',
            'skala_total_maksimum' => 'decimal:1',
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
}

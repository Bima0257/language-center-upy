<?php

namespace App\Models;

use App\Enums\ModeUjian;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Ujian yang dapat dijadwalkan untuk peserta.
 *
 * @property string $id
 * @property string $jenis_tes_id
 * @property string $judul
 * @property string|null $deskripsi
 * @property ModeUjian $mode
 * @property int $durasi_menit
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JenisTes $jenisTes
 * @property-read Collection<int, BagianUjian> $bagianUjian
 * @property-read Collection<int, PeriodeUjian> $periodeUjian
 */
#[Fillable(['jenis_tes_id', 'judul', 'deskripsi', 'mode', 'durasi_menit', 'aktif'])]
class Ujian extends Model
{
    use HasUuids;

    protected $table = 'ujian';

    protected function casts(): array
    {
        return [
            'mode' => ModeUjian::class,
            'aktif' => 'boolean',
            'durasi_menit' => 'integer',
        ];
    }

    public function jenisTes(): BelongsTo
    {
        return $this->belongsTo(JenisTes::class, 'jenis_tes_id', 'id');
    }

    public function bagianUjian(): HasMany
    {
        return $this->hasMany(BagianUjian::class, 'ujian_id', 'id');
    }

    public function periodeUjian(): HasMany
    {
        return $this->hasMany(PeriodeUjian::class, 'ujian_id', 'id');
    }
}

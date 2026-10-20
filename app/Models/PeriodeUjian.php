<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * Periode ujian sebagai rentang tanggal pelaksanaan.
 *
 * @property string $id
 * @property string $ujian_id
 * @property string $judul
 * @property Carbon $tanggal_mulai
 * @property Carbon $tanggal_selesai
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ujian $ujian
 * @property-read Collection<int, SlotUjian> $slotUjian
 * @property-read Collection<int, SesiUjian> $sesiUjian
 */
#[Fillable(['ujian_id', 'judul', 'tanggal_mulai', 'tanggal_selesai', 'aktif'])]
class PeriodeUjian extends Model
{
    use HasUuids;

    protected $table = 'periode_ujian';

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id', 'id');
    }

    public function slotUjian(): HasMany
    {
        return $this->hasMany(SlotUjian::class, 'periode_ujian_id', 'id')
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai');
    }

    public function sesiUjian(): HasManyThrough
    {
        return $this->hasManyThrough(
            SesiUjian::class,
            SlotUjian::class,
            'periode_ujian_id',
            'slot_ujian_id',
            'id',
            'id',
        );
    }

    public function sedangAktif(): bool
    {
        return $this->aktif
            && now()->toDateString() >= $this->tanggal_mulai->toDateString()
            && now()->toDateString() <= $this->tanggal_selesai->toDateString();
    }
}

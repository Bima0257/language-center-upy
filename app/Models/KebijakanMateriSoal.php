<?php

namespace App\Models;

use App\Enums\KetentuanMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Kebijakan penggunaan materi soal untuk satu keterampilan dalam jenis tesnya.
 *
 * @property string $id
 * @property string $keterampilan_id
 * @property KetentuanMedia $ketentuan_materi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Keterampilan $keterampilan
 * @property-read Collection<int, AturanMediaMateriSoal> $aturanMedia
 */
#[Fillable([
    'keterampilan_id',
    'ketentuan_materi',
])]
class KebijakanMateriSoal extends Model
{
    use HasUuids;

    protected $table = 'kebijakan_materi_soal';

    protected function casts(): array
    {
        return [
            'ketentuan_materi' => KetentuanMedia::class,
        ];
    }

    public function keterampilan(): BelongsTo
    {
        return $this->belongsTo(Keterampilan::class, 'keterampilan_id', 'id');
    }

    public function aturanMedia(): HasMany
    {
        return $this->hasMany(AturanMediaMateriSoal::class, 'kebijakan_materi_soal_id', 'id');
    }
}

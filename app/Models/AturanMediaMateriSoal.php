<?php

namespace App\Models;

use App\Enums\JenisMedia;
use App\Enums\KetentuanMedia;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Aturan kebutuhan media untuk satu kebijakan materi soal.
 *
 * @property string $id
 * @property string $kebijakan_materi_soal_id
 * @property JenisMedia $jenis_media
 * @property KetentuanMedia $ketentuan
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read KebijakanMateriSoal $kebijakan
 */
#[Fillable([
    'kebijakan_materi_soal_id',
    'jenis_media',
    'ketentuan',
])]
class AturanMediaMateriSoal extends Model
{
    use HasUuids;

    protected $table = 'aturan_media_materi_soal';

    protected function casts(): array
    {
        return [
            'jenis_media' => JenisMedia::class,
            'ketentuan' => KetentuanMedia::class,
        ];
    }

    public function kebijakan(): BelongsTo
    {
        return $this->belongsTo(KebijakanMateriSoal::class, 'kebijakan_materi_soal_id', 'id');
    }
}

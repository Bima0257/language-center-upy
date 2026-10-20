<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Jawaban peserta untuk satu soal dalam satu sesi ujian.
 *
 * @property string $id
 * @property string $sesi_ujian_id
 * @property string $soal_id
 * @property string|null $teks_jawaban
 * @property bool|null $benar
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SesiUjian $sesiUjian
 * @property-read Soal $soal
 */
#[Fillable(['sesi_ujian_id', 'soal_id', 'teks_jawaban', 'benar'])]
class Jawaban extends Model
{
    use HasUuids;

    protected $table = 'jawaban';

    protected function casts(): array
    {
        return [
            'benar' => 'boolean',
        ];
    }

    public function sesiUjian(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id', 'id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id', 'id');
    }
}

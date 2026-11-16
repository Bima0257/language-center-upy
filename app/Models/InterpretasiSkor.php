<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Interpretasi rentang skor untuk satu program tes.
 *
 * @property string $id
 * @property string|null $skema_penilaian_id
 * @property string $program_tes_id
 * @property int $skor_minimum
 * @property int $skor_maksimum
 * @property string $tingkat_cefr
 * @property string $label_tingkat
 * @property bool $lulus
 * @property string|null $deskripsi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProgramTes $programTes
 * @property-read SkemaPenilaian|null $skemaPenilaian
 */
class InterpretasiSkor extends Model
{
    use HasUuids;

    protected $table = 'interpretasi_skor';

    protected $fillable = [
        'skema_penilaian_id',
        'program_tes_id',
        'skor_minimum',
        'skor_maksimum',
        'tingkat_cefr',
        'label_tingkat',
        'lulus',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'skor_minimum' => 'integer',
            'skor_maksimum' => 'integer',
            'lulus' => 'boolean',
        ];
    }

    public function programTes(): BelongsTo
    {
        return $this->belongsTo(ProgramTes::class, 'program_tes_id', 'id');
    }

    public function skemaPenilaian(): BelongsTo
    {
        return $this->belongsTo(SkemaPenilaian::class, 'skema_penilaian_id', 'id');
    }
}

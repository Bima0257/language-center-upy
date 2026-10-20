<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Interpretasi rentang skor untuk satu jenis tes.
 *
 * @property string $id
 * @property string $jenis_tes_id
 * @property int $skor_minimum
 * @property int $skor_maksimum
 * @property string $tingkat_cefr
 * @property string $label_tingkat
 * @property bool $lulus
 * @property string|null $deskripsi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JenisTes $jenisTes
 */
class InterpretasiSkor extends Model
{
    use HasUuids;

    protected $table = 'interpretasi_skor';

    protected $fillable = [
        'jenis_tes_id',
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

    public function jenisTes(): BelongsTo
    {
        return $this->belongsTo(JenisTes::class, 'jenis_tes_id', 'id');
    }
}

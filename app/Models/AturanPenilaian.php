<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Aturan konversi skor untuk satu keterampilan dalam jenis tes.
 *
 * @property string $id
 * @property string $jenis_tes_id
 * @property string $kode_keterampilan
 * @property array<string, int> $tabel_konversi
 * @property int $skor_mentah_maksimum
 * @property int $skor_skala_maksimum
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read JenisTes $jenisTes
 */
class AturanPenilaian extends Model
{
    use HasUuids;

    protected $table = 'aturan_penilaian';

    protected $fillable = [
        'jenis_tes_id',
        'kode_keterampilan',
        'tabel_konversi',
        'skor_mentah_maksimum',
        'skor_skala_maksimum',
    ];

    protected function casts(): array
    {
        return [
            'tabel_konversi' => 'array',
            'skor_mentah_maksimum' => 'integer',
            'skor_skala_maksimum' => 'integer',
        ];
    }

    public function jenisTes(): BelongsTo
    {
        return $this->belongsTo(JenisTes::class, 'jenis_tes_id', 'id');
    }
}

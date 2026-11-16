<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Aturan konversi skor untuk satu section dalam skema penilaian.
 *
 * @property string $id
 * @property string|null $skema_penilaian_id
 * @property string $program_tes_id
 * @property string|null $section_id
 * @property string $kode_section
 * @property array<int|string, int> $tabel_konversi
 * @property int $skor_mentah_maksimum
 * @property int $skor_skala_minimum
 * @property int $skor_skala_maksimum
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProgramTes $programTes
 * @property-read Section|null $section
 * @property-read SkemaPenilaian|null $skemaPenilaian
 */
class AturanPenilaian extends Model
{
    use HasUuids;

    protected $table = 'aturan_penilaian';

    protected $fillable = [
        'skema_penilaian_id',
        'program_tes_id',
        'section_id',
        'kode_section',
        'tabel_konversi',
        'skor_mentah_maksimum',
        'skor_skala_minimum',
        'skor_skala_maksimum',
    ];

    protected function casts(): array
    {
        return [
            'tabel_konversi' => 'array',
            'skor_mentah_maksimum' => 'integer',
            'skor_skala_minimum' => 'integer',
            'skor_skala_maksimum' => 'integer',
        ];
    }

    public function programTes(): BelongsTo
    {
        return $this->belongsTo(ProgramTes::class, 'program_tes_id', 'id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function skemaPenilaian(): BelongsTo
    {
        return $this->belongsTo(SkemaPenilaian::class, 'skema_penilaian_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Hasil penilaian satu section untuk satu sesi ujian.
 *
 * @property string $id
 * @property string $sesi_ujian_id
 * @property string $section_id
 * @property string $skema_penilaian_id
 * @property int $jumlah_benar
 * @property int $jumlah_soal
 * @property string $skor_skala
 * @property array<int, array<string, mixed>>|null $rincian
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SesiUjian $sesiUjian
 * @property-read Section $section
 * @property-read SkemaPenilaian $skemaPenilaian
 */
class HasilPenilaian extends Model
{
    use HasUuids;

    protected $table = 'hasil_penilaian';

    protected $fillable = [
        'sesi_ujian_id',
        'section_id',
        'skema_penilaian_id',
        'jumlah_benar',
        'jumlah_soal',
        'skor_skala',
        'rincian',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_benar' => 'integer',
            'jumlah_soal' => 'integer',
            'skor_skala' => 'decimal:1',
            'rincian' => 'array',
        ];
    }

    public function sesiUjian(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id', 'id');
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

<?php

namespace App\Models;

use App\Enums\AlasanSelesaiPart;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Catatan waktu pengerjaan satu part/section dalam sebuah sesi ujian.
 *
 * @property string $id
 * @property string $sesi_ujian_id
 * @property string $part_ujian_id
 * @property int $durasi_menit
 * @property Carbon $dimulai_pada
 * @property Carbon|null $selesai_pada
 * @property AlasanSelesaiPart|null $alasan_selesai
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SesiUjian $sesiUjian
 * @property-read PartUjian $partUjian
 */
#[Fillable([
    'sesi_ujian_id',
    'part_ujian_id',
    'durasi_menit',
    'dimulai_pada',
    'selesai_pada',
    'alasan_selesai',
])]
class SesiPartUjian extends Model
{
    use HasUuids;

    protected $table = 'sesi_part_ujian';

    protected function casts(): array
    {
        return [
            'durasi_menit' => 'integer',
            'dimulai_pada' => 'datetime',
            'selesai_pada' => 'datetime',
            'alasan_selesai' => AlasanSelesaiPart::class,
        ];
    }

    public function sesiUjian(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id', 'id');
    }

    public function partUjian(): BelongsTo
    {
        return $this->belongsTo(PartUjian::class, 'part_ujian_id', 'id');
    }

    public function batasWaktu(): Carbon
    {
        return $this->dimulai_pada->copy()->addMinutes($this->durasi_menit);
    }
}

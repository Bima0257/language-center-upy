<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot susunan part soal di dalam part ujian.
 *
 * @property string $id
 * @property string $part_ujian_id
 * @property string $part_soal_id
 * @property int $urutan
 * @property-read PartUjian $partUjian
 * @property-read PartSoal $partSoal
 */
class PartSoalUjian extends Model
{
    use HasUuids;

    protected $table = 'part_soal_ujian';

    public $timestamps = false;

    protected $fillable = [
        'part_ujian_id',
        'part_soal_id',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function partUjian(): BelongsTo
    {
        return $this->belongsTo(PartUjian::class, 'part_ujian_id', 'id');
    }

    public function partSoal(): BelongsTo
    {
        return $this->belongsTo(PartSoal::class, 'part_soal_id', 'id');
    }
}

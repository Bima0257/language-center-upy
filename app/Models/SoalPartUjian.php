<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot susunan soal di dalam part ujian.
 *
 * @property string $id
 * @property string $part_ujian_id
 * @property string $soal_id
 * @property int $urutan
 * @property-read PartUjian $partUjian
 * @property-read Soal $soal
 */
class SoalPartUjian extends Model
{
    use HasUuids;

    protected $table = 'soal_part_ujian';

    public $timestamps = false;

    protected $fillable = [
        'part_ujian_id',
        'soal_id',
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

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id', 'id');
    }
}

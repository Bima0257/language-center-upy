<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot susunan soal di dalam bagian ujian.
 *
 * @property string $id
 * @property string $bagian_ujian_id
 * @property string $soal_id
 * @property int $urutan
 * @property-read BagianUjian $bagianUjian
 * @property-read Soal $soal
 */
class SoalBagianUjian extends Model
{
    use HasUuids;

    protected $table = 'soal_bagian_ujian';

    public $timestamps = false;

    protected $fillable = [
        'bagian_ujian_id',
        'soal_id',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function bagianUjian(): BelongsTo
    {
        return $this->belongsTo(BagianUjian::class, 'bagian_ujian_id', 'id');
    }

    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class, 'soal_id', 'id');
    }
}

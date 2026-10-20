<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot susunan bagian soal di dalam bagian ujian.
 *
 * @property string $id
 * @property string $bagian_ujian_id
 * @property string $bagian_soal_id
 * @property int $urutan
 * @property-read BagianUjian $bagianUjian
 * @property-read BagianSoal $bagianSoal
 */
class BagianSoalUjian extends Model
{
    use HasUuids;

    protected $table = 'bagian_soal_ujian';

    public $timestamps = false;

    protected $fillable = [
        'bagian_ujian_id',
        'bagian_soal_id',
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

    public function bagianSoal(): BelongsTo
    {
        return $this->belongsTo(BagianSoal::class, 'bagian_soal_id', 'id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Sertifikat hasil sesi ujian.
 *
 * @property string $id
 * @property string $sesi_ujian_id
 * @property string $nomor_sertifikat
 * @property Carbon $diterbitkan_pada
 * @property Carbon $berlaku_hingga
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SesiUjian $sesiUjian
 */
class Sertifikat extends Model
{
    use HasUuids;

    protected $table = 'sertifikat';

    protected $fillable = [
        'sesi_ujian_id',
        'nomor_sertifikat',
        'diterbitkan_pada',
        'berlaku_hingga',
    ];

    protected function casts(): array
    {
        return [
            'diterbitkan_pada' => 'datetime',
            'berlaku_hingga' => 'date',
        ];
    }

    public function sesiUjian(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id', 'id');
    }
}

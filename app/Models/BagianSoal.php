<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Bagian soal dalam satu bank soal dan keterampilan.
 *
 * @property string $id
 * @property string $bank_soal_id
 * @property string $keterampilan_id
 * @property string $nama
 * @property int $urutan
 * @property string|null $petunjuk
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BankSoal $bankSoal
 * @property-read Keterampilan $keterampilan
 * @property-read Collection<int, Soal> $soal
 */
class BagianSoal extends Model
{
    use HasUuids;

    protected $table = 'bagian_soal';

    protected $fillable = [
        'bank_soal_id',
        'keterampilan_id',
        'nama',
        'urutan',
        'petunjuk',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id', 'id');
    }

    public function keterampilan(): BelongsTo
    {
        return $this->belongsTo(Keterampilan::class, 'keterampilan_id', 'id');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'bagian_soal_id', 'id');
    }
}

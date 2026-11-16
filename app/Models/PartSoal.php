<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Part soal dalam satu bank soal dan section.
 *
 * @property string $id
 * @property string $bank_soal_id
 * @property string $section_id
 * @property string $nama
 * @property int $urutan
 * @property string|null $petunjuk
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BankSoal $bankSoal
 * @property-read Section $section
 * @property-read Collection<int, Soal> $soal
 * @property-read bool $dipakai_ujian_aktif
 */
class PartSoal extends Model
{
    use HasUuids;

    protected $table = 'part_soal';

    protected $fillable = [
        'bank_soal_id',
        'section_id',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function soal(): HasMany
    {
        return $this->hasMany(Soal::class, 'part_soal_id', 'id');
    }
}

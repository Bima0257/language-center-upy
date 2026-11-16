<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Part ujian yang mengelompokkan soal berdasarkan section.
 *
 * @property string $id
 * @property string $ujian_id
 * @property string $bank_soal_id
 * @property string $section_id
 * @property string $judul
 * @property int $urutan
 * @property int $jumlah_soal
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Ujian $ujian
 * @property-read BankSoal $bankSoal
 * @property-read Section $section
 * @property-read Collection<int, SoalPartUjian> $soalPartUjian
 * @property-read Collection<int, PartSoalUjian> $partSoalUjian
 */
#[Fillable(['ujian_id', 'bank_soal_id', 'section_id', 'judul', 'urutan', 'jumlah_soal'])]
class PartUjian extends Model
{
    use HasUuids;

    protected $table = 'part_ujian';

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'jumlah_soal' => 'integer',
        ];
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id', 'id');
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id', 'id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function soalPartUjian(): HasMany
    {
        return $this->hasMany(SoalPartUjian::class, 'part_ujian_id', 'id')
            ->orderBy('urutan');
    }

    public function partSoalUjian(): HasMany
    {
        return $this->hasMany(PartSoalUjian::class, 'part_ujian_id', 'id')
            ->orderBy('urutan');
    }
}

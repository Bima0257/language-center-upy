<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Soal yang tersimpan dalam bank soal.
 *
 * @property string $id
 * @property string $bank_soal_id
 * @property string|null $materi_soal_id
 * @property string $section_id
 * @property string|null $part_soal_id
 * @property string $teks_soal
 * @property string $pilihan_a
 * @property string $pilihan_b
 * @property string $pilihan_c
 * @property string $pilihan_d
 * @property string $kunci_jawaban
 * @property int $urutan
 * @property string|null $pembuat_id
 * @property string|null $pengubah_id
 * @property string|null $materi
 * @property-read int $part_ujian_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read BankSoal $bankSoal
 * @property-read MateriSoal|null $materiSoal
 * @property-read Section $section
 * @property-read PartSoal|null $partSoal
 * @property-read Pengguna|null $pembuat
 * @property-read Pengguna|null $pengubah
 * @property-read Collection<int, PartUjian> $partUjian
 */
#[Fillable([
    'bank_soal_id', 'materi_soal_id', 'section_id', 'part_soal_id',
    'teks_soal', 'pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d',
    'kunci_jawaban', 'urutan',
    'pembuat_id', 'pengubah_id',
])]
class Soal extends Model
{
    use HasUuids;

    protected $table = 'soal';

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function bankSoal(): BelongsTo
    {
        return $this->belongsTo(BankSoal::class, 'bank_soal_id', 'id');
    }

    public function materiSoal(): BelongsTo
    {
        return $this->belongsTo(MateriSoal::class, 'materi_soal_id', 'id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id', 'id');
    }

    public function partSoal(): BelongsTo
    {
        return $this->belongsTo(PartSoal::class, 'part_soal_id', 'id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pembuat_id', 'id');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengubah_id', 'id');
    }

    public function partUjian(): BelongsToMany
    {
        return $this->belongsToMany(
            PartUjian::class,
            'soal_part_ujian',
            'soal_id',
            'part_ujian_id',
            'id',
            'id',
        );
    }
}

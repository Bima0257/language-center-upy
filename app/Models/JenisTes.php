<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Jenis tes yang tersedia dalam sistem.
 *
 * @property string $id
 * @property string $kode
 * @property string $nama
 * @property int $batas_pelanggaran
 * @property string|null $deskripsi
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Ujian> $ujian
 * @property-read Collection<int, Keterampilan> $keterampilan
 * @property-read Collection<int, BankSoal> $bankSoal
 * @property-read Collection<int, AturanPenilaian> $aturanPenilaian
 * @property-read Collection<int, InterpretasiSkor> $interpretasiSkor
 */
class JenisTes extends Model
{
    use HasUuids;

    protected $table = 'jenis_tes';

    protected $fillable = [
        'kode',
        'nama',
        'batas_pelanggaran',
        'deskripsi',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'batas_pelanggaran' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    public function ujian(): HasMany
    {
        return $this->hasMany(Ujian::class, 'jenis_tes_id', 'id');
    }

    public function keterampilan(): HasMany
    {
        return $this->hasMany(Keterampilan::class, 'jenis_tes_id', 'id');
    }

    public function bankSoal(): HasMany
    {
        return $this->hasMany(BankSoal::class, 'jenis_tes_id', 'id');
    }

    public function aturanPenilaian(): HasMany
    {
        return $this->hasMany(AturanPenilaian::class, 'jenis_tes_id', 'id');
    }

    public function interpretasiSkor(): HasMany
    {
        return $this->hasMany(InterpretasiSkor::class, 'jenis_tes_id', 'id');
    }
}

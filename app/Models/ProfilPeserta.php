<?php

namespace App\Models;

use App\Enums\JenisPeserta;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Profil identitas peserta ujian (mahasiswa, dosen, atau umum).
 *
 * @property string $id
 * @property string $pengguna_id
 * @property JenisPeserta $jenis_peserta
 * @property string|null $nomor_identitas
 * @property string|null $institusi
 * @property string|null $fakultas_id
 * @property string|null $program_studi_id
 * @property int|null $tahun_mulai
 * @property string|null $foto_identitas
 * @property bool $terverifikasi
 * @property Carbon|null $diverifikasi_pada
 * @property string|null $verifikator_id
 * @property string|null $catatan_verifikasi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Pengguna $pengguna
 * @property-read Fakultas|null $fakultas
 * @property-read ProgramStudi|null $programStudi
 * @property-read Pengguna|null $verifikator
 */
#[Fillable([
    'pengguna_id',
    'jenis_peserta',
    'nomor_identitas',
    'institusi',
    'fakultas_id',
    'program_studi_id',
    'tahun_mulai',
    'foto_identitas',
    'terverifikasi',
    'diverifikasi_pada',
    'verifikator_id',
    'catatan_verifikasi',
])]
class ProfilPeserta extends Model
{
    use HasUuids;

    protected $table = 'profil_peserta';

    protected function casts(): array
    {
        return [
            'jenis_peserta' => JenisPeserta::class,
            'tahun_mulai' => 'integer',
            'terverifikasi' => 'boolean',
            'diverifikasi_pada' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id', 'id');
    }

    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_id', 'id');
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id', 'id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'verifikator_id', 'id');
    }
}

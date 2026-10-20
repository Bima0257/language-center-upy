<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Profil akademik dan verifikasi identitas mahasiswa.
 *
 * @property string $id
 * @property string $pengguna_id
 * @property string|null $nim
 * @property string $fakultas_id
 * @property string $program_studi_id
 * @property int|null $tahun_angkatan
 * @property string|null $foto_identitas
 * @property bool $terverifikasi
 * @property Carbon|null $diverifikasi_pada
 * @property string|null $verifikator_id
 * @property string|null $catatan_verifikasi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Pengguna $pengguna
 * @property-read Fakultas $fakultas
 * @property-read ProgramStudi $programStudi
 * @property-read Pengguna|null $verifikator
 */
class ProfilMahasiswa extends Model
{
    use HasUuids;

    protected $table = 'profil_mahasiswa';

    protected $fillable = [
        'pengguna_id',
        'nim',
        'fakultas_id',
        'program_studi_id',
        'tahun_angkatan',
        'foto_identitas',
        'terverifikasi',
        'diverifikasi_pada',
        'verifikator_id',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'terverifikasi' => 'boolean',
            'tahun_angkatan' => 'integer',
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

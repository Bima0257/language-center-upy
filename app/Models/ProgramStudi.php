<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Program studi yang berada di bawah satu fakultas.
 *
 * @property string $id
 * @property string $fakultas_id
 * @property string $nama
 * @property string $kode
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Fakultas $fakultas
 */
class ProgramStudi extends Model
{
    use HasUuids;

    protected $table = 'program_studi';

    protected $fillable = [
        'fakultas_id',
        'nama',
        'kode',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function fakultas(): BelongsTo
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_id', 'id');
    }
}

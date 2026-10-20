<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Fakultas yang menaungi program studi.
 *
 * @property string $id
 * @property string $nama
 * @property string $kode
 * @property bool $aktif
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ProgramStudi> $programStudi
 */
class Fakultas extends Model
{
    use HasUuids;

    protected $table = 'fakultas';

    protected $fillable = [
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

    public function programStudi(): HasMany
    {
        return $this->hasMany(ProgramStudi::class, 'fakultas_id', 'id');
    }
}

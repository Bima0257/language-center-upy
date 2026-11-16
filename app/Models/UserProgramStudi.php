<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $program_studi_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Pengguna $pengguna
 * @property-read ProgramStudi $programStudi
 */
class UserProgramStudi extends Pivot
{
    use HasUuids;

    protected $table = 'user_program_studi';

    protected $fillable = [
        'user_id',
        'program_studi_id',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'user_id', 'id');
    }

    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class, 'program_studi_id', 'id');
    }
}

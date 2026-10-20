<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Counter permanen untuk kode business identifier.
 *
 * @property string $id
 * @property string $ruang
 * @property int $nilai_terakhir
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['ruang', 'nilai_terakhir'])]
class UrutanKode extends Model
{
    use HasUuids;

    protected $table = 'urutan_kode';

    protected function casts(): array
    {
        return [
            'nilai_terakhir' => 'integer',
        ];
    }
}

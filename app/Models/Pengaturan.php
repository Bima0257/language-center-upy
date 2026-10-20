<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Pengaturan aplikasi berbentuk pasangan kunci dan nilai.
 *
 * @property string $id
 * @property string $kunci
 * @property string|null $nilai
 * @property string $kelompok
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Pengaturan extends Model
{
    use HasUuids;

    protected $table = 'pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
        'kelompok',
    ];

    public static function ambil(string $kunci, mixed $bawaan = null): mixed
    {
        $pengaturan = static::where('kunci', $kunci)->first();

        return $pengaturan->nilai ?? $bawaan;
    }

    public static function atur(string $kunci, mixed $nilai): self
    {
        return static::updateOrCreate(
            ['kunci' => $kunci],
            ['nilai' => $nilai],
        );
    }

    public static function semuaBerdasarkanKelompok(string $kelompok): Collection
    {
        return static::where('kelompok', $kelompok)->orderBy('kunci')->get();
    }
}

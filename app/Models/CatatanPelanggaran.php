<?php

namespace App\Models;

use App\Enums\JenisPelanggaran;
use App\Enums\TingkatPelanggaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Catatan pelanggaran yang terjadi selama sesi ujian.
 *
 * @property string $id
 * @property string $sesi_ujian_id
 * @property JenisPelanggaran $jenis
 * @property TingkatPelanggaran $tingkat
 * @property string|null $deskripsi
 * @property int $jumlah_pelanggaran
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SesiUjian $sesiUjian
 */
#[Fillable(['sesi_ujian_id', 'jenis', 'tingkat', 'deskripsi', 'jumlah_pelanggaran'])]
class CatatanPelanggaran extends Model
{
    use HasUuids;

    protected $table = 'catatan_pelanggaran';

    protected function casts(): array
    {
        return [
            'jenis' => JenisPelanggaran::class,
            'tingkat' => TingkatPelanggaran::class,
            'jumlah_pelanggaran' => 'integer',
        ];
    }

    public function sesiUjian(): BelongsTo
    {
        return $this->belongsTo(SesiUjian::class, 'sesi_ujian_id', 'id');
    }
}

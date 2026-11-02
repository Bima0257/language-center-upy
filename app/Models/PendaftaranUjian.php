<?php

namespace App\Models;

use App\Enums\StatusPendaftaranUjian;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Pengajuan peserta untuk satu slot ujian.
 *
 * @property string $id
 * @property string $slot_ujian_id
 * @property string $pengguna_id
 * @property StatusPendaftaranUjian $status
 * @property string|null $lokasi_bukti_pembayaran
 * @property string|null $nama_asli_bukti_pembayaran
 * @property string|null $tipe_mime_bukti_pembayaran
 * @property int|null $ukuran_bukti_pembayaran
 * @property string|null $catatan_admin
 * @property string|null $diproses_oleh_id
 * @property Carbon|null $diproses_pada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SlotUjian $slotUjian
 * @property-read Pengguna $pengguna
 * @property-read Pengguna|null $diprosesOleh
 */
#[Fillable([
    'slot_ujian_id',
    'pengguna_id',
    'status',
    'lokasi_bukti_pembayaran',
    'nama_asli_bukti_pembayaran',
    'tipe_mime_bukti_pembayaran',
    'ukuran_bukti_pembayaran',
    'catatan_admin',
    'diproses_oleh_id',
    'diproses_pada',
])]
class PendaftaranUjian extends Model
{
    use HasUuids;

    protected $table = 'pendaftaran_ujian';

    protected function casts(): array
    {
        return [
            'status' => StatusPendaftaranUjian::class,
            'ukuran_bukti_pembayaran' => 'integer',
            'diproses_pada' => 'datetime',
        ];
    }

    public function slotUjian(): BelongsTo
    {
        return $this->belongsTo(SlotUjian::class, 'slot_ujian_id', 'id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id', 'id');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diproses_oleh_id', 'id');
    }
}

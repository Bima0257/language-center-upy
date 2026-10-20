<?php

namespace App\Models;

use App\Enums\StatusSesi;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Sesi pengerjaan ujian oleh satu pengguna.
 *
 * @property string $id
 * @property string $slot_ujian_id
 * @property string $pengguna_id
 * @property StatusSesi $status
 * @property Carbon|null $dimulai_pada
 * @property Carbon|null $diserahkan_pada
 * @property Carbon|null $dihentikan_pada
 * @property string|null $alasan_penghentian
 * @property string|null $bagian_aktif_id
 * @property Carbon|null $keaktifan_terakhir_pada
 * @property string|null $status_tinjauan
 * @property string|null $catatan_tinjauan
 * @property string|null $peninjau_id
 * @property Carbon|null $ditinjau_pada
 * @property string|null $skor_membaca
 * @property string|null $skor_menyimak
 * @property string|null $skor_berbicara
 * @property string|null $skor_menulis
 * @property string|null $skor_total
 * @property bool $ditandai
 * @property string|null $alasan_penandaan
 * @property int $jumlah_pelanggaran
 * @property string|null $jenis_perangkat
 * @property string|null $agen_pengguna_perangkat
 * @property string|null $lokasi_swafoto
 * @property Carbon|null $swafoto_diambil_pada
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SlotUjian|null $slotUjian
 * @property-read Pengguna $pengguna
 * @property-read BagianUjian|null $bagianAktif
 * @property-read Pengguna|null $peninjau
 * @property-read Collection<int, Jawaban> $jawaban
 * @property-read Collection<int, CatatanPelanggaran> $catatanPelanggaran
 */
#[Fillable([
    'slot_ujian_id', 'pengguna_id', 'status', 'dimulai_pada', 'diserahkan_pada',
    'dihentikan_pada', 'alasan_penghentian', 'bagian_aktif_id',
    'keaktifan_terakhir_pada', 'status_tinjauan', 'catatan_tinjauan',
    'peninjau_id', 'ditinjau_pada', 'skor_membaca', 'skor_menyimak',
    'skor_berbicara', 'skor_menulis', 'skor_total', 'ditandai',
    'alasan_penandaan', 'jumlah_pelanggaran', 'jenis_perangkat',
    'agen_pengguna_perangkat', 'lokasi_swafoto', 'swafoto_diambil_pada',
])]
class SesiUjian extends Model
{
    use HasUuids;

    protected $table = 'sesi_ujian';

    protected function casts(): array
    {
        return [
            'status' => StatusSesi::class,
            'dimulai_pada' => 'datetime',
            'diserahkan_pada' => 'datetime',
            'dihentikan_pada' => 'datetime',
            'keaktifan_terakhir_pada' => 'datetime',
            'ditinjau_pada' => 'datetime',
            'ditandai' => 'boolean',
            'jumlah_pelanggaran' => 'integer',
            'swafoto_diambil_pada' => 'datetime',
            'skor_membaca' => 'decimal:1',
            'skor_menyimak' => 'decimal:1',
            'skor_berbicara' => 'decimal:1',
            'skor_menulis' => 'decimal:1',
            'skor_total' => 'decimal:1',
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

    public function bagianAktif(): BelongsTo
    {
        return $this->belongsTo(BagianUjian::class, 'bagian_aktif_id', 'id');
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'peninjau_id', 'id');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(Jawaban::class, 'sesi_ujian_id', 'id');
    }

    public function catatanPelanggaran(): HasMany
    {
        return $this->hasMany(CatatanPelanggaran::class, 'sesi_ujian_id', 'id');
    }

    public function batasPelanggaranMaksimum(): int
    {
        $jenisTes = $this->slotUjian?->periodeUjian?->ujian?->jenisTes;

        return $jenisTes !== null ? $jenisTes->batas_pelanggaran : 3;
    }
}

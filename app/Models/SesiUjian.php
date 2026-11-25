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
 * @property string|null $slot_ujian_id
 * @property string|null $ujian_id
 * @property string $pengguna_id
 * @property StatusSesi $status
 * @property Carbon|null $dimulai_pada
 * @property Carbon|null $diserahkan_pada
 * @property Carbon|null $dihentikan_pada
 * @property string|null $alasan_penghentian
 * @property string|null $part_ujian_aktif_id
 * @property string|null $skema_penilaian_id
 * @property array<string, mixed>|null $snapshot_penilaian
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
 * @property string|null $skor_total_maksimum
 * @property bool|null $lulus
 * @property string|null $tingkat_cefr
 * @property string|null $label_tingkat
 * @property string|null $deskripsi_tingkat
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
 * @property-read Ujian|null $ujian
 * @property-read Pengguna $pengguna
 * @property-read PartUjian|null $partUjianAktif
 * @property-read SkemaPenilaian|null $skemaPenilaian
 * @property-read Pengguna|null $peninjau
 * @property-read Collection<int, Jawaban> $jawaban
 * @property-read Collection<int, CatatanPelanggaran> $catatanPelanggaran
 * @property-read Collection<int, HasilPenilaian> $hasilPenilaian
 * @property-read Collection<int, SesiPartUjian> $sesiPartUjian
 */
#[Fillable([
    'slot_ujian_id', 'ujian_id', 'pengguna_id', 'status', 'dimulai_pada', 'diserahkan_pada',
    'dihentikan_pada', 'alasan_penghentian', 'part_ujian_aktif_id',
    'skema_penilaian_id', 'snapshot_penilaian',
    'keaktifan_terakhir_pada', 'status_tinjauan', 'catatan_tinjauan',
    'peninjau_id', 'ditinjau_pada', 'skor_membaca', 'skor_menyimak',
    'skor_berbicara', 'skor_menulis', 'skor_total', 'skor_total_maksimum',
    'lulus', 'tingkat_cefr', 'label_tingkat', 'deskripsi_tingkat', 'ditandai',
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
            'skor_total_maksimum' => 'decimal:1',
            'snapshot_penilaian' => 'array',
            'lulus' => 'boolean',
        ];
    }

    public function slotUjian(): BelongsTo
    {
        return $this->belongsTo(SlotUjian::class, 'slot_ujian_id', 'id');
    }

    public function ujian(): BelongsTo
    {
        return $this->belongsTo(Ujian::class, 'ujian_id', 'id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id', 'id');
    }

    public function partUjianAktif(): BelongsTo
    {
        return $this->belongsTo(PartUjian::class, 'part_ujian_aktif_id', 'id');
    }

    public function skemaPenilaian(): BelongsTo
    {
        return $this->belongsTo(SkemaPenilaian::class, 'skema_penilaian_id', 'id');
    }

    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'peninjau_id', 'id');
    }

    public function jawaban(): HasMany
    {
        return $this->hasMany(Jawaban::class, 'sesi_ujian_id', 'id');
    }

    public function hasilPenilaian(): HasMany
    {
        return $this->hasMany(HasilPenilaian::class, 'sesi_ujian_id', 'id');
    }

    public function catatanPelanggaran(): HasMany
    {
        return $this->hasMany(CatatanPelanggaran::class, 'sesi_ujian_id', 'id');
    }

    public function sesiPartUjian(): HasMany
    {
        return $this->hasMany(SesiPartUjian::class, 'sesi_ujian_id', 'id')->orderBy('dimulai_pada');
    }

    public function batasPelanggaranMaksimum(): int
    {
        $ujian = $this->ujian ?? $this->slotUjian?->periodeUjian?->ujian;
        $programTes = $ujian?->programTes;

        return $programTes !== null ? $programTes->batas_pelanggaran : 3;
    }
}

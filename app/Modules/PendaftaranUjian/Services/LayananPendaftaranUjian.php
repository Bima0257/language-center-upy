<?php

namespace App\Modules\PendaftaranUjian\Services;

use App\Enums\ModeUjian;
use App\Enums\StatusPendaftaranUjian;
use App\Models\PendaftaranUjian;
use App\Models\Pengguna;
use App\Models\PeriodePendaftaranUjian;
use App\Models\PeriodeUjian;
use App\Models\SlotUjian;
use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PendaftaranUjianRepositoryInterface;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PeriodePendaftaranUjianRepositoryInterface;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\PenjadwalanRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LayananPendaftaranUjian
{
    public function __construct(
        private PendaftaranUjianRepositoryInterface $pendaftaranRepo,
        private PeriodePendaftaranUjianRepositoryInterface $periodePendaftaranRepo,
        private SlotUjianRepositoryInterface $slotRepo,
        private PenggunaRepositoryInterface $penggunaRepo,
        private FakultasRepositoryInterface $fakultasRepo,
        private ProgramStudiRepositoryInterface $programStudiRepo,
        private PenjadwalanRepositoryInterface $penjadwalanRepo,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataDaftar(array $filters): array
    {
        $periodeUjian = $this->penjadwalanRepo->semuaAktifDenganUjianDanSlot();
        $this->tambahkanRingkasanPendaftaran($periodeUjian);

        $periodeUntukJenisTes = $this->filterPeriode($periodeUjian, [
            'jenis_tes_id' => $filters['jenis_tes_id'] ?? null,
        ]);
        $periodeUntukUjian = $this->filterPeriode($periodeUntukJenisTes, [
            'ujian_id' => $filters['ujian_id'] ?? null,
        ]);
        $periodeUntukPeriode = $this->filterPeriode($periodeUntukUjian, [
            'periode_ujian_id' => $filters['periode_ujian_id'] ?? null,
        ]);

        return [
            'pendaftaranUjian' => ! empty($filters['slot_ujian_id'])
                ? $this->pendaftaranRepo->paginate($filters)
                : $this->paginatorKosong(),
            'ringkasanJenisTes' => $this->buatRingkasanJenisTes($periodeUjian),
            'ringkasanUjian' => $this->buatRingkasanUjian($periodeUntukJenisTes),
            'ringkasanPeriode' => $this->buatRingkasanPeriode($periodeUntukUjian),
            'ringkasanSlot' => $this->buatRingkasanSlot($periodeUntukPeriode),
            'context' => $this->contextDariFilter($filters),
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataPeserta(PeriodePendaftaranUjian $periodePendaftaran, array $filters): array
    {
        $periodePendaftaran = $this->periodePendaftaranRepo->temukan($periodePendaftaran->id);
        $filters['periode_ujian_id'] = $periodePendaftaran->periode_ujian_id;

        return [
            'periodePendaftaranUjian' => $periodePendaftaran,
            'slotUjian' => $periodePendaftaran->slotUjian,
            'pendaftaranUjian' => $this->pendaftaranRepo->paginate($filters),
            'filters' => $filters,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataJadwal(Pengguna $pengguna, array $filters): array
    {
        $periodeUjian = $this->penjadwalanRepo->semuaAktifDenganUjianDanSlot()
            ->filter(fn (PeriodeUjian $periode): bool => $periode->tanggal_selesai->gte(now()->toDateString())
                && $periode->ujian->mode === ModeUjian::OFFICIAL
                && $periode->periodePendaftaran !== null
                && $periode->periodePendaftaran->aktif)
            ->values();
        $this->tambahkanRingkasanPendaftaran($periodeUjian);
        $this->tambahkanStatusPeriodePendaftaran($periodeUjian);

        $periodeUntukJenisTes = $this->filterPeriode($periodeUjian, [
            'jenis_tes_id' => $filters['jenis_tes_id'] ?? null,
        ]);
        $periodeUntukUjian = $this->filterPeriode($periodeUntukJenisTes, [
            'ujian_id' => $filters['ujian_id'] ?? null,
        ]);
        $periodeUntukPeriode = $this->filterPeriode($periodeUntukUjian, [
            'periode_ujian_id' => $filters['periode_ujian_id'] ?? null,
        ]);
        $periodeUntukJadwal = $periodeUntukPeriode->filter(
            fn (PeriodeUjian $periode): bool => ! empty($filters['periode_ujian_id']),
        );

        return [
            'periodeUjian' => $this->tambahkanStatusUntukJadwal(
                $periodeUntukJadwal,
                $pengguna->id,
            )->values(),
            'ringkasanJenisTes' => $this->buatRingkasanJenisTes($periodeUjian),
            'ringkasanUjian' => $this->buatRingkasanUjian($periodeUntukJenisTes),
            'ringkasanPeriode' => $this->buatRingkasanPeriode($periodeUntukUjian),
            'filters' => $this->contextDariFilter($filters),
            ...$this->dataFormMahasiswa($pengguna),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataFormMahasiswa(Pengguna $pengguna): array
    {
        $profil = $pengguna->loadMissing('profilMahasiswa')->profilMahasiswa;

        return [
            'profil' => $profil ? [
                'nim' => $profil->nim,
                'fakultas_id' => $profil->fakultas_id,
                'program_studi_id' => $profil->program_studi_id,
                'tahun_angkatan' => $profil->tahun_angkatan,
            ] : null,
            'fakultas' => $this->fakultasRepo->semuaAktifBerurutan(),
            'programStudi' => $this->programStudiRepo->semuaAktifBerurutan(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string|null>
     */
    private function contextDariFilter(array $filters): array
    {
        return [
            'jenis_tes_id' => $filters['jenis_tes_id'] ?? null,
            'ujian_id' => $filters['ujian_id'] ?? null,
            'periode_ujian_id' => $filters['periode_ujian_id'] ?? null,
            'slot_ujian_id' => $filters['slot_ujian_id'] ?? null,
        ];
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     * @param  array<string, mixed>  $filters
     * @return Collection<int, PeriodeUjian>
     */
    private function filterPeriode(Collection $periodeUjian, array $filters): Collection
    {
        return $periodeUjian->filter(function (PeriodeUjian $periode) use ($filters): bool {
            $ujian = $periode->ujian;

            return (empty($filters['jenis_tes_id']) || $ujian->jenis_tes_id === $filters['jenis_tes_id'])
                && (empty($filters['ujian_id']) || $ujian->id === $filters['ujian_id'])
                && (empty($filters['periode_ujian_id']) || $periode->id === $filters['periode_ujian_id']);
        })->values();
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     * @return array<int, array<string, mixed>>
     */
    private function buatRingkasanJenisTes(Collection $periodeUjian): array
    {
        return $periodeUjian
            ->groupBy(fn (PeriodeUjian $periode): string => $periode->ujian->jenis_tes_id)
            ->map(function (Collection $periodeGroup, string $jenisTesId): array {
                $jenisTes = $periodeGroup->first()->ujian->jenisTes;
                $slotUjian = $this->slotDariPeriode($periodeGroup);

                return [
                    'id' => $jenisTesId,
                    'nama' => $jenisTes->nama,
                    'jumlahUjian' => $periodeGroup->pluck('ujian_id')->unique()->count(),
                    'jumlahPeriode' => $periodeGroup->count(),
                    'jumlahSlot' => $slotUjian->count(),
                    ...$this->hitungPendaftaran($slotUjian),
                ];
            })
            ->sortBy('nama')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     * @return array<int, array<string, mixed>>
     */
    private function buatRingkasanUjian(Collection $periodeUjian): array
    {
        return $periodeUjian
            ->groupBy('ujian_id')
            ->map(function (Collection $periodeGroup, string $ujianId): array {
                $ujian = $periodeGroup->first()->ujian;
                $slotUjian = $this->slotDariPeriode($periodeGroup);

                return [
                    'id' => $ujianId,
                    'jenisTesId' => $ujian->jenis_tes_id,
                    'judul' => $ujian->judul,
                    'mode' => $ujian->mode->value,
                    'jumlahPeriode' => $periodeGroup->count(),
                    'jumlahSlot' => $slotUjian->count(),
                    ...$this->hitungPendaftaran($slotUjian),
                ];
            })
            ->sortBy('judul')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     * @return array<int, array<string, mixed>>
     */
    private function buatRingkasanPeriode(Collection $periodeUjian): array
    {
        return $periodeUjian
            ->map(function (PeriodeUjian $periode): array {
                $slotUjian = $periode->slotUjian;

                return [
                    'id' => $periode->id,
                    'ujianId' => $periode->ujian_id,
                    'judul' => $periode->judul,
                    'tanggalMulai' => $periode->tanggal_mulai,
                    'tanggalSelesai' => $periode->tanggal_selesai,
                    'jumlahSlot' => $slotUjian->count(),
                    ...$this->hitungPendaftaran($slotUjian),
                ];
            })
            ->sortBy('tanggalMulai')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     * @return array<int, array<string, mixed>>
     */
    private function buatRingkasanSlot(Collection $periodeUjian): array
    {
        return $periodeUjian
            ->flatMap(function (PeriodeUjian $periode): array {
                return $periode->slotUjian->map(function (SlotUjian $slot) use ($periode): array {
                    return [
                        'id' => $slot->id,
                        'periodeUjianId' => $periode->id,
                        'periodeJudul' => $periode->judul,
                        'tanggal' => $slot->tanggal,
                        'waktuMulai' => $slot->waktu_mulai,
                        'waktuSelesai' => $slot->waktu_selesai,
                        'kapasitasPeserta' => $slot->kapasitas_peserta,
                        ...$this->hitungPendaftaran(new Collection([$slot])),
                    ];
                })->all();
            })
            ->sortBy(fn (array $slot): string => $slot['tanggal']->toDateString().' '.$slot['waktuMulai'])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     * @return Collection<int, SlotUjian>
     */
    private function slotDariPeriode(Collection $periodeUjian): Collection
    {
        return new Collection($periodeUjian
            ->flatMap(fn (PeriodeUjian $periode): array => $periode->slotUjian->all())
            ->values()
            ->all());
    }

    /**
     * @param  Collection<int, SlotUjian>  $slotUjian
     * @return array{jumlahPendaftaran: int, pendingCount: int, approvedCount: int, rejectedCount: int, cancelledCount: int}
     */
    private function hitungPendaftaran(Collection $slotUjian): array
    {
        return [
            'jumlahPendaftaran' => (int) $slotUjian->sum(fn (SlotUjian $slot): int => (int) $slot->getAttribute('pendaftaran_total_count')),
            'pendingCount' => (int) $slotUjian->sum(fn (SlotUjian $slot): int => (int) $slot->getAttribute('pendaftaran_pending_count')),
            'approvedCount' => (int) $slotUjian->sum(fn (SlotUjian $slot): int => (int) $slot->getAttribute('pendaftaran_approved_count')),
            'rejectedCount' => (int) $slotUjian->sum(fn (SlotUjian $slot): int => (int) $slot->getAttribute('pendaftaran_rejected_count')),
            'cancelledCount' => (int) $slotUjian->sum(fn (SlotUjian $slot): int => (int) $slot->getAttribute('pendaftaran_cancelled_count')),
        ];
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     */
    private function tambahkanRingkasanPendaftaran(Collection $periodeUjian): void
    {
        $slotUjian = $this->slotDariPeriode($periodeUjian);
        $ringkasan = $this->pendaftaranRepo->ringkasanStatusPerSlot($slotUjian->pluck('id')->all());

        $slotUjian->each(function (SlotUjian $slot) use ($ringkasan): void {
            $jumlah = $ringkasan[$slot->id] ?? [
                'jumlahPendaftaran' => 0,
                'pendingCount' => 0,
                'approvedCount' => 0,
                'rejectedCount' => 0,
                'cancelledCount' => 0,
            ];

            $slot->setAttribute('pendaftaran_total_count', $jumlah['jumlahPendaftaran']);
            $slot->setAttribute('pendaftaran_pending_count', $jumlah['pendingCount']);
            $slot->setAttribute('pendaftaran_approved_count', $jumlah['approvedCount']);
            $slot->setAttribute('pendaftaran_rejected_count', $jumlah['rejectedCount']);
            $slot->setAttribute('pendaftaran_cancelled_count', $jumlah['cancelledCount']);
            $slot->setAttribute('pendaftaran_disetujui_count', $jumlah['approvedCount']);
        });
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     */
    private function tambahkanStatusPeriodePendaftaran(Collection $periodeUjian): void
    {
        $periodeUjian->each(function (PeriodeUjian $periode): void {
            $periodePendaftaran = $periode->periodePendaftaran;
            $periode->setAttribute('status_pendaftaran', $periodePendaftaran?->status());

            $periode->slotUjian->each(function (SlotUjian $slot) use ($periodePendaftaran): void {
                $slot->setAttribute('pendaftaran_terbuka', $periodePendaftaran?->sedangDibuka() ?? false);
            });
        });
    }

    private function paginatorKosong(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            15,
            1,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     * @return Collection<int, PeriodeUjian>
     */
    public function tambahkanStatusUntukJadwal(Collection $periodeUjian, string $penggunaId): Collection
    {
        $slotUjianIds = $periodeUjian
            ->flatMap(fn ($periode) => $periode->slotUjian->pluck('id'))
            ->values()
            ->all();
        $statusBySlot = $this->pendaftaranRepo->statusUntukSlot($penggunaId, $slotUjianIds);

        $periodeUjian->each(function ($periode) use ($statusBySlot): void {
            $periode->slotUjian->each(function (SlotUjian $slot) use ($statusBySlot): void {
                $registration = $statusBySlot[$slot->id] ?? null;
                $slot->setAttribute('pendaftaran_id', $registration['id'] ?? null);
                $slot->setAttribute('pendaftaran_status', $registration['status'] ?? null);
            });
        });

        return $periodeUjian;
    }

    public function ajukan(
        Pengguna $pengguna,
        SlotUjian $slotUjian,
        array $data,
        UploadedFile $buktiPembayaran,
    ): PendaftaranUjian {
        $this->pastikanMahasiswa($pengguna);

        $pendaftaranLama = $this->pendaftaranRepo->temukanUntukSlotDanPengguna($slotUjian->id, $pengguna->id);
        $lokasiLama = $pendaftaranLama?->lokasi_bukti_pembayaran;
        $lokasiBaru = $buktiPembayaran->store('payments', 'public');

        try {
            $pendaftaran = DB::transaction(function () use ($pengguna, $slotUjian, $data, $lokasiBaru): PendaftaranUjian {
                $slotTerkunci = $this->slotRepo->temukanUntukUpdate($slotUjian->id);
                $this->pastikanOfficialAktif($slotTerkunci);
                $this->pastikanPendaftaranTerbuka($slotTerkunci);

                $pendaftaranSaatIni = $this->pendaftaranRepo->temukanUntukSlotDanPengguna(
                    $slotTerkunci->id,
                    $pengguna->id,
                );

                if ($pendaftaranSaatIni !== null && in_array($pendaftaranSaatIni->status, [
                    StatusPendaftaranUjian::PENDING,
                    StatusPendaftaranUjian::APPROVED,
                ], true)) {
                    throw new \RuntimeException('Anda sudah memiliki pendaftaran aktif pada slot ini.');
                }

                if ($this->pendaftaranRepo->memilikiAktifDalamPeriode(
                    $pengguna->id,
                    $slotTerkunci->periode_ujian_id,
                    $pendaftaranSaatIni?->id,
                )) {
                    throw new \RuntimeException('Anda sudah memiliki pendaftaran aktif pada periode ujian ini.');
                }

                $this->penggunaRepo->perbaruiProfilMahasiswa($pengguna, [
                    'nim' => $data['nim'],
                    'fakultas_id' => $data['fakultas_id'],
                    'program_studi_id' => $data['program_studi_id'],
                    'tahun_angkatan' => $data['tahun_angkatan'],
                ]);

                $dataPendaftaran = [
                    'slot_ujian_id' => $slotTerkunci->id,
                    'pengguna_id' => $pengguna->id,
                    'status' => StatusPendaftaranUjian::PENDING,
                    'lokasi_bukti_pembayaran' => $lokasiBaru,
                    'nama_asli_bukti_pembayaran' => $data['bukti_pembayaran_nama_asli'] ?? null,
                    'tipe_mime_bukti_pembayaran' => $data['bukti_pembayaran_tipe_mime'] ?? null,
                    'ukuran_bukti_pembayaran' => $data['bukti_pembayaran_ukuran'] ?? null,
                    'catatan_admin' => null,
                    'diproses_oleh_id' => null,
                    'diproses_pada' => null,
                ];

                if ($pendaftaranSaatIni !== null) {
                    return $this->pendaftaranRepo->perbarui($pendaftaranSaatIni, $dataPendaftaran);
                }

                return $this->pendaftaranRepo->buat($dataPendaftaran);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($lokasiBaru);

            throw $exception;
        }

        if ($lokasiLama !== null && $lokasiLama !== $pendaftaran->lokasi_bukti_pembayaran) {
            Storage::disk('public')->delete($lokasiLama);
        }

        return $pendaftaran;
    }

    public function batalkanUntukPengguna(string $pendaftaranId, string $penggunaId): void
    {
        $pendaftaran = $this->pendaftaranRepo->temukanUntukPengguna($pendaftaranId, $penggunaId);

        if (! in_array($pendaftaran->status, [
            StatusPendaftaranUjian::PENDING,
            StatusPendaftaranUjian::APPROVED,
        ], true)) {
            throw new \RuntimeException('Pendaftaran ini sudah tidak dapat dibatalkan.');
        }

        if ($this->waktuMulaiSlot($pendaftaran->slotUjian)->isPast()) {
            throw new \RuntimeException('Pendaftaran tidak dapat dibatalkan setelah slot ujian dimulai.');
        }

        $this->pendaftaranRepo->perbarui($pendaftaran, [
            'status' => StatusPendaftaranUjian::CANCELLED,
            'diproses_oleh_id' => $penggunaId,
            'diproses_pada' => now(),
        ]);
    }

    public function setujui(string $pendaftaranId, string $adminId, ?string $catatan): void
    {
        DB::transaction(function () use ($pendaftaranId, $adminId, $catatan): void {
            $pendaftaran = $this->pendaftaranRepo->temukanUntukUpdate($pendaftaranId);
            $slotUjian = $this->slotRepo->temukanUntukUpdate($pendaftaran->slot_ujian_id);
            $this->pastikanOfficialAktif($slotUjian);

            if ($pendaftaran->status !== StatusPendaftaranUjian::PENDING) {
                throw new \RuntimeException('Hanya pendaftaran yang menunggu yang dapat disetujui.');
            }

            if ($pendaftaran->lokasi_bukti_pembayaran === null) {
                throw new \RuntimeException('Bukti pembayaran belum tersedia.');
            }

            if ($this->waktuMulaiSlot($slotUjian)->isPast()) {
                throw new \RuntimeException('Pendaftaran tidak dapat disetujui setelah slot ujian dimulai.');
            }

            if ($this->pendaftaranRepo->hitungDisetujui($slotUjian->id) >= $slotUjian->kapasitas_peserta) {
                throw new \RuntimeException('Kuota slot ujian sudah penuh.');
            }

            $this->pendaftaranRepo->perbarui($pendaftaran, [
                'status' => StatusPendaftaranUjian::APPROVED,
                'diproses_oleh_id' => $adminId,
                'diproses_pada' => now(),
                'catatan_admin' => $catatan,
            ]);
        });
    }

    public function tolak(string $pendaftaranId, string $adminId, ?string $catatan): void
    {
        $pendaftaran = $this->pendaftaranRepo->temukanUntukUpdate($pendaftaranId);

        if ($pendaftaran->status !== StatusPendaftaranUjian::PENDING) {
            throw new \RuntimeException('Hanya pendaftaran yang menunggu yang dapat ditolak.');
        }

        $this->pendaftaranRepo->perbarui($pendaftaran, [
            'status' => StatusPendaftaranUjian::REJECTED,
            'diproses_oleh_id' => $adminId,
            'diproses_pada' => now(),
            'catatan_admin' => $catatan,
        ]);
    }

    public function batalkanOlehAdmin(string $pendaftaranId, string $adminId, ?string $catatan): void
    {
        $pendaftaran = $this->pendaftaranRepo->temukanUntukUpdate($pendaftaranId);

        if (! in_array($pendaftaran->status, [
            StatusPendaftaranUjian::PENDING,
            StatusPendaftaranUjian::APPROVED,
        ], true)) {
            throw new \RuntimeException('Pendaftaran ini sudah tidak dapat dibatalkan.');
        }

        $this->pendaftaranRepo->perbarui($pendaftaran, [
            'status' => StatusPendaftaranUjian::CANCELLED,
            'diproses_oleh_id' => $adminId,
            'diproses_pada' => now(),
            'catatan_admin' => $catatan,
        ]);
    }

    public function pastikanBolehMulai(Pengguna $pengguna, SlotUjian $slotUjian): void
    {
        if ($slotUjian->periodeUjian->ujian->mode !== ModeUjian::OFFICIAL) {
            return;
        }

        $pendaftaran = $this->pendaftaranRepo->temukanUntukSlotDanPengguna($slotUjian->id, $pengguna->id);

        if ($pendaftaran?->status !== StatusPendaftaranUjian::APPROVED) {
            throw new \RuntimeException('Pendaftaran ujian Anda belum disetujui.');
        }
    }

    public function daftarkanTryout(Pengguna $pengguna, SlotUjian $slotUjian): PendaftaranUjian
    {
        $this->pastikanMahasiswa($pengguna);
        $this->pastikanProfilLengkap($pengguna);

        $pendaftaran = $this->pendaftaranRepo->temukanUntukSlotDanPengguna($slotUjian->id, $pengguna->id);

        if ($pendaftaran?->status === StatusPendaftaranUjian::APPROVED) {
            return $pendaftaran;
        }

        if ($pendaftaran !== null && $this->pendaftaranRepo->memilikiAktifDalamPeriode(
            $pengguna->id,
            $slotUjian->periode_ujian_id,
            $pendaftaran->id,
        )) {
            throw new \RuntimeException('Anda sudah memiliki pendaftaran aktif pada periode ujian ini.');
        }

        if ($this->pendaftaranRepo->hitungDisetujui($slotUjian->id) >= $slotUjian->kapasitas_peserta) {
            throw new \RuntimeException('Kuota slot ujian sudah penuh.');
        }

        $data = [
            'slot_ujian_id' => $slotUjian->id,
            'pengguna_id' => $pengguna->id,
            'status' => StatusPendaftaranUjian::APPROVED,
            'diproses_oleh_id' => null,
            'diproses_pada' => now(),
        ];

        if ($pendaftaran !== null) {
            return $this->pendaftaranRepo->perbarui($pendaftaran, $data);
        }

        return $this->pendaftaranRepo->buat($data);
    }

    private function pastikanMahasiswa(Pengguna $pengguna): void
    {
        if (! $pengguna->adalahMahasiswa()) {
            throw new \RuntimeException('Hanya mahasiswa yang dapat mendaftar ujian.');
        }
    }

    private function pastikanProfilLengkap(Pengguna $pengguna): void
    {
        $profil = $pengguna->loadMissing('profilMahasiswa')->profilMahasiswa;

        if ($profil === null || ! $profil->nim || ! $profil->fakultas_id || ! $profil->program_studi_id || ! $profil->tahun_angkatan) {
            throw new \RuntimeException('Lengkapi identitas akademik sebelum mendaftar atau mengikuti ujian.');
        }
    }

    private function pastikanOfficialAktif(SlotUjian $slotUjian): void
    {
        $periodeUjian = $slotUjian->periodeUjian;
        $ujian = $periodeUjian->ujian;

        if ($ujian->mode !== ModeUjian::OFFICIAL) {
            throw new \RuntimeException('Pendaftaran manual hanya tersedia untuk ujian resmi.');
        }

        if (! $slotUjian->aktif || ! $periodeUjian->aktif || ! $ujian->aktif || ! $ujian->jenisTes->aktif) {
            throw new \RuntimeException('Ujian resmi ini sedang tidak aktif.');
        }

        if ($periodeUjian->periodePendaftaran === null || ! $periodeUjian->periodePendaftaran->aktif) {
            throw new \RuntimeException('Periode pendaftaran ujian ini tidak aktif.');
        }
    }

    private function pastikanPendaftaranTerbuka(SlotUjian $slotUjian): void
    {
        $periodePendaftaran = $slotUjian->periodeUjian->periodePendaftaran;

        if ($periodePendaftaran === null) {
            throw new \RuntimeException('Periode pendaftaran ujian belum tersedia.');
        }

        if ($periodePendaftaran->status() === 'upcoming') {
            throw new \RuntimeException('Pendaftaran ujian belum dibuka.');
        }

        if ($periodePendaftaran->status() !== 'open') {
            throw new \RuntimeException('Pendaftaran ujian sudah ditutup.');
        }
    }

    private function waktuMulaiSlot(SlotUjian $slotUjian): Carbon
    {
        return $slotUjian->tanggal->copy()->setTimeFromTimeString(substr((string) $slotUjian->waktu_mulai, 0, 8));
    }
}

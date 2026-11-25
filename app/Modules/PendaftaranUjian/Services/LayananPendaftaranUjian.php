<?php

namespace App\Modules\PendaftaranUjian\Services;

use App\Enums\ModeUjian;
use App\Enums\StatusPendaftaranUjian;
use App\Models\PendaftaranUjian;
use App\Models\Pengguna;
use App\Models\PeriodeUjian;
use App\Models\ProgramTes;
use App\Models\SlotUjian;
use App\Models\Ujian;
use App\Modules\DataInduk\Repositories\Contracts\FakultasRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramStudiRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\ProgramTesRepositoryInterface;
use App\Modules\PendaftaranUjian\Repositories\Contracts\PendaftaranUjianRepositoryInterface;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\PenjadwalanRepositoryInterface;
use App\Modules\Penjadwalan\Repositories\Contracts\SlotUjianRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\UjianRepositoryInterface;
use App\Services\ImageCompressionService;
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
        private SlotUjianRepositoryInterface $slotRepo,
        private PenggunaRepositoryInterface $penggunaRepo,
        private FakultasRepositoryInterface $fakultasRepo,
        private ProgramStudiRepositoryInterface $programStudiRepo,
        private ProgramTesRepositoryInterface $programTesRepo,
        private UjianRepositoryInterface $ujianRepo,
        private PenjadwalanRepositoryInterface $penjadwalanRepo,
        private ImageCompressionService $imageCompression,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataDaftar(array $filters): array
    {
        $periodeUjian = $this->penjadwalanRepo->semuaAktifDenganUjianDanSlot();
        $this->tambahkanRingkasanPendaftaran($periodeUjian);

        $periodeUntukProgramTes = $this->filterPeriode($periodeUjian, [
            'program_tes_id' => $filters['program_tes_id'] ?? null,
        ]);
        $periodeUntukUjian = $this->filterPeriode($periodeUntukProgramTes, [
            'ujian_id' => $filters['ujian_id'] ?? null,
        ]);
        $periodeUntukPeriode = $this->filterPeriode($periodeUntukUjian, [
            'periode_ujian_id' => $filters['periode_ujian_id'] ?? null,
        ]);

        return [
            'pendaftaranUjian' => ! empty($filters['slot_ujian_id'])
                ? $this->pendaftaranRepo->paginate($filters)
                : $this->paginatorKosong(),
            'ringkasanProgramTes' => $this->buatRingkasanProgramTes($periodeUjian),
            'ringkasanUjian' => $this->buatRingkasanUjian($periodeUntukProgramTes),
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
    public function dataPesertaSemua(array $filters): array
    {
        $filters['search'] = $filters['q'] ?? $filters['search'] ?? null;
        $filters['status'] = $filters['f_status'] ?? $filters['status'] ?? null;
        $filters['program_tes_id'] = $filters['f_program_tes_id'] ?? $filters['program_tes_id'] ?? null;
        $filters['ujian_id'] = $filters['ujian_id'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 15);

        if (filled($filters['ujian_id'])) {
            $ujian = $this->ujianRepo->temukanDenganProgramTes($filters['ujian_id']);

            return [
                'ujianTerpilih' => [
                    'id' => $ujian->id,
                    'judul' => $ujian->judul,
                    'mode' => $ujian->mode->value,
                    'program_tes' => [
                        'nama' => $ujian->programTes->nama,
                    ],
                ],
                'pendaftaranUjian' => $this->pendaftaranRepo->paginate($filters, $perPage),
                'filters' => $filters,
            ];
        }

        return [
            'ringkasanUjian' => $this->pendaftaranRepo->ringkasanStatusPerUjian($filters, $perPage),
            'filters' => $filters,
            'programTes' => $this->programTesRepo->semuaAktifBerurutan()
                ->map(fn (ProgramTes $programTes): array => [
                    'value' => $programTes->id,
                    'label' => $programTes->nama,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dataJadwal(Pengguna $pengguna, array $filters): array
    {
        $programStudiId = $pengguna->loadMissing('profilPeserta')->profilPeserta?->program_studi_id;
        $periodeUjian = $this->periodePendaftaranPeserta($programStudiId);
        $this->tambahkanRingkasanPendaftaran($periodeUjian);
        $this->tambahkanStatusPeriodePendaftaran($periodeUjian);

        $periodeUntukProgramTes = $this->filterPeriode($periodeUjian, [
            'program_tes_id' => $filters['program_tes_id'] ?? null,
        ]);
        $periodeUntukUjian = $this->filterPeriode($periodeUntukProgramTes, [
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
            'ringkasanProgramTes' => $this->buatRingkasanProgramTes($periodeUjian),
            'ringkasanUjian' => $this->buatRingkasanUjian($periodeUntukProgramTes),
            'ringkasanPeriode' => $this->buatRingkasanPeriode($periodeUntukUjian),
            'filters' => $this->contextDariFilter($filters),
            ...$this->dataFormPeserta($pengguna),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataPendaftaran(Pengguna $pengguna): array
    {
        $programStudiId = $pengguna->loadMissing('profilPeserta')->profilPeserta?->program_studi_id;
        $periodeUjian = $this->periodePendaftaranPeserta($programStudiId);
        $this->tambahkanRingkasanPendaftaran($periodeUjian);
        $this->tambahkanStatusPeriodePendaftaran($periodeUjian);
        $this->tambahkanStatusUntukJadwal($periodeUjian, $pengguna->id);

        return [
            'periodeUjian' => $periodeUjian,
            'pendaftaranSaya' => $this->pendaftaranRepo->riwayatUntukPengguna($pengguna->id),
            ...$this->dataFormPeserta($pengguna),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dataUjianResmi(Pengguna $pengguna): array
    {
        $programStudiId = $pengguna->loadMissing('profilPeserta')->profilPeserta?->program_studi_id;

        $periodeUjian = $this->penjadwalanRepo->semuaAktifDenganUjianDanSlot($programStudiId)
            ->filter(fn (PeriodeUjian $periode): bool => $periode->ujian->terbukaUntukProdi($programStudiId)
                && $periode->tanggal_selesai->gte(now()->toDateString())
                && $periode->ujian->mode === ModeUjian::OFFICIAL)
            ->values();

        $this->tambahkanRingkasanPendaftaran($periodeUjian);
        $this->tambahkanStatusPeriodePendaftaran($periodeUjian);
        $this->tambahkanStatusUntukJadwal($periodeUjian, $pengguna->id);

        return [
            'periodeUjian' => $periodeUjian
                ->filter(fn (PeriodeUjian $periode): bool => $this->periodeTampilDiUjianResmi($periode))
                ->values(),
        ];
    }

    private function periodeTampilDiUjianResmi(PeriodeUjian $periode): bool
    {
        if ($periode->periodePendaftaran?->aktif) {
            return true;
        }

        return $periode->slotUjian->contains(
            fn (SlotUjian $slot): bool => in_array(
                $slot->getAttribute('pendaftaran_status'),
                [StatusPendaftaranUjian::PENDING->value, StatusPendaftaranUjian::APPROVED->value],
                true,
            ),
        );
    }

    /**
     * @return Collection<int, PeriodeUjian>
     */
    private function periodePendaftaranPeserta(?string $programStudiId): Collection
    {
        return $this->penjadwalanRepo->semuaAktifDenganUjianDanSlot($programStudiId)
            ->filter(fn (PeriodeUjian $periode): bool => $periode->ujian->terbukaUntukProdi($programStudiId)
                && $periode->tanggal_selesai->gte(now()->toDateString())
                && $periode->ujian->mode === ModeUjian::OFFICIAL
                && $periode->periodePendaftaran !== null
                && $periode->periodePendaftaran->aktif)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function dataFormPeserta(Pengguna $pengguna): array
    {
        $profil = $pengguna->loadMissing('profilPeserta')->profilPeserta;

        return [
            'profil' => $profil ? [
                'jenis_peserta' => $profil->jenis_peserta->value,
                'nomor_identitas' => $profil->nomor_identitas,
                'institusi' => $profil->institusi,
                'fakultas_id' => $profil->fakultas_id,
                'program_studi_id' => $profil->program_studi_id,
                'tahun_mulai' => $profil->tahun_mulai,
            ] : null,
            'fakultas' => $this->fakultasRepo->semuaAktifBerurutan(),
            'programStudi' => $this->programStudiRepo->semuaAktifBerurutan(),
            'prodiTerkunci' => $pengguna->sudahMemakaiUjian(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string|null>
     */
    private function contextDariFilter(array $filters): array
    {
        return [
            'program_tes_id' => $filters['program_tes_id'] ?? null,
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

            return (empty($filters['program_tes_id']) || $ujian->program_tes_id === $filters['program_tes_id'])
                && (empty($filters['ujian_id']) || $ujian->id === $filters['ujian_id'])
                && (empty($filters['periode_ujian_id']) || $periode->id === $filters['periode_ujian_id']);
        })->values();
    }

    /**
     * @param  Collection<int, PeriodeUjian>  $periodeUjian
     * @return array<int, array<string, mixed>>
     */
    private function buatRingkasanProgramTes(Collection $periodeUjian): array
    {
        return $periodeUjian
            ->groupBy(fn (PeriodeUjian $periode): string => $periode->ujian->program_tes_id)
            ->map(function (Collection $periodeGroup, string $programTesId): array {
                $programTes = $periodeGroup->first()->ujian->programTes;
                $slotUjian = $this->slotDariPeriode($periodeGroup);

                return [
                    'id' => $programTesId,
                    'nama' => $programTes->nama,
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
                    'programTesId' => $ujian->program_tes_id,
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
        $this->pastikanPesertaUjian($pengguna);
        $this->pastikanProdiTidakBerubahSaatTerkunci($pengguna, $data);

        $pendaftaranLama = $this->pendaftaranRepo->temukanUntukSlotDanPengguna($slotUjian->id, $pengguna->id);
        $lokasiLama = $pendaftaranLama?->lokasi_bukti_pembayaran;
        $lokasiBaru = $this->imageCompression->storeCompressed($buktiPembayaran, 'payments');

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

                $profilSaatIni = $pengguna->loadMissing('profilPeserta')->profilPeserta;

                $this->penggunaRepo->perbaruiProfilPeserta($pengguna, [
                    'nomor_identitas' => $data['nomor_identitas'],
                    'institusi' => filled($data['institusi'] ?? null) ? $data['institusi'] : $profilSaatIni?->institusi,
                    'fakultas_id' => $data['fakultas_id'] ?? null,
                    'program_studi_id' => $data['program_studi_id'] ?? null,
                    'tahun_mulai' => $data['tahun_mulai'] ?? $profilSaatIni?->tahun_mulai,
                ]);

                $pengguna->refresh()->loadMissing('profilPeserta');
                $this->pastikanLingkupUjian($pengguna, $slotTerkunci->periodeUjian->ujian);

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
            throw new \RuntimeException('Pemeriksaan awal hanya tersedia untuk ujian resmi.');
        }

        $this->pastikanLingkupUjian($pengguna, $slotUjian->periodeUjian->ujian);

        $pendaftaran = $this->pendaftaranRepo->temukanUntukSlotDanPengguna($slotUjian->id, $pengguna->id);

        if ($pendaftaran?->status !== StatusPendaftaranUjian::APPROVED) {
            throw new \RuntimeException('Pendaftaran ujian Anda belum disetujui.');
        }
    }

    private function pastikanPesertaUjian(Pengguna $pengguna): void
    {
        if (! $pengguna->adalahPesertaUjian()) {
            throw new \RuntimeException('Hanya peserta ujian yang dapat mendaftar ujian.');
        }
    }

    private function pastikanLingkupUjian(Pengguna $pengguna, Ujian $ujian): void
    {
        if (! $ujian->terbukaUntuk($pengguna)) {
            throw new \RuntimeException('Ujian ini tidak tersedia untuk program studi Anda.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function pastikanProdiTidakBerubahSaatTerkunci(Pengguna $pengguna, array $data): void
    {
        if (! $pengguna->sudahMemakaiUjian()) {
            return;
        }

        $profil = $pengguna->loadMissing('profilPeserta')->profilPeserta;

        if ($profil === null) {
            return;
        }

        $prodiBaru = array_key_exists('program_studi_id', $data)
            ? $data['program_studi_id']
            : $profil->program_studi_id;
        $fakultasBaru = array_key_exists('fakultas_id', $data)
            ? $data['fakultas_id']
            : $profil->fakultas_id;

        if ($prodiBaru !== $profil->program_studi_id || $fakultasBaru !== $profil->fakultas_id) {
            throw new \RuntimeException('Program studi tidak dapat diubah setelah Anda mengikuti ujian. Hubungi admin.');
        }
    }

    private function pastikanOfficialAktif(SlotUjian $slotUjian): void
    {
        $periodeUjian = $slotUjian->periodeUjian;
        $ujian = $periodeUjian->ujian;

        if ($ujian->mode !== ModeUjian::OFFICIAL) {
            throw new \RuntimeException('Pendaftaran manual hanya tersedia untuk ujian resmi.');
        }

        if (! $slotUjian->aktif || ! $periodeUjian->aktif || ! $ujian->aktif || ! $ujian->programTes->aktif) {
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

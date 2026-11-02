<?php

namespace App\Modules\Ujian\Services;

use App\Models\BagianSoal;
use App\Models\Keterampilan;
use App\Models\MateriSoal;
use App\Models\Soal;
use App\Modules\DataInduk\Repositories\Contracts\BagianSoalRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\JenisTesRepositoryInterface;
use App\Modules\DataInduk\Repositories\Contracts\KeterampilanRepositoryInterface;
use App\Modules\Ujian\DTOs\KonfigurasiKebijakanMateriSoal;
use App\Modules\Ujian\Repositories\Contracts\BankSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\MateriSoalRepositoryInterface;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mews\Purifier\Facades\Purifier;

class LayananSoal
{
    public function __construct(
        private SoalRepositoryInterface $soalRepo,
        private BankSoalRepositoryInterface $bankSoalRepo,
        private MateriSoalRepositoryInterface $materiSoalRepo,
        private LayananMateriSoal $materiSoalService,
        private LayananKebijakanMateriSoal $kebijakanMateri,
        private KeterampilanRepositoryInterface $keterampilanRepo,
        private BagianSoalRepositoryInterface $bagianSoalRepo,
        private JenisTesRepositoryInterface $jenisTesRepo,
    ) {}

    private function opsiKeterampilan(?string $jenisTesId = null): array
    {
        return $this->keterampilanRepo->semuaAktifBerurutan($jenisTesId)
            ->map(fn (Keterampilan $keterampilan) => [
                'value' => $keterampilan->id,
                'label' => $keterampilan->nama,
                'kode' => $keterampilan->kode,
                'deskripsi' => $keterampilan->deskripsi,
                'aktif' => $keterampilan->aktif,
            ])
            ->toArray();
    }

    public function dataTambah(?string $bankSoalTerpilih, array $filter): array
    {
        $bankSoal = $bankSoalTerpilih !== null ? $this->bankSoalRepo->find($bankSoalTerpilih) : null;

        return [
            'bankSoal' => $this->bankSoalRepo->semuaAktifBerurutan(),
            'opsiKeterampilan' => $this->opsiKeterampilan($bankSoal?->jenis_tes_id),
            'bagianSoal' => $this->bagianSoalRepo->semuaAktifBerurutan(),
            'materiSoal' => $bankSoal !== null
                ? $this->materiSoalRepo->semuaUntukBank($bankSoal->id)
                : new Collection,
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
            'bankSoalTerpilih' => $bankSoalTerpilih !== null && $bankSoalTerpilih !== '' && $this->bankSoalRepo->adaAktif($bankSoalTerpilih)
                ? $bankSoalTerpilih
                : null,
            'filterKembali' => $filter,
        ];
    }

    public function dataUbah(Soal $soal): array
    {
        $soalDenganRelasi = $this->soalRepo->temukanDenganRelasi($soal->id);
        $materiSoalAktif = $soalDenganRelasi?->materiSoal;

        return [
            'soal' => $soalDenganRelasi,
            'bankSoal' => $this->bankSoalRepo->semuaAktifBerurutan(),
            'opsiKeterampilan' => $this->opsiKeterampilan($soalDenganRelasi?->bankSoal?->jenis_tes_id),
            'bagianSoal' => $this->bagianSoalRepo->semuaAktifBerurutan(),
            'materiSoal' => $materiSoalAktif !== null ? [$materiSoalAktif] : [],
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
        ];
    }

    public function dataDaftar(array $filter): array
    {
        $bankSoalId = $filter['bank_soal_id'] ?? null;
        $bagianSoal = $this->bagianSoalRepo->semuaAktifBerurutan();

        if (empty($bankSoalId) && ! empty($filter['bagian_soal_id'])) {
            $bagianSoalTerpilih = $bagianSoal->first(
                fn (BagianSoal $part): bool => $part->id === $filter['bagian_soal_id'],
            );
            $bankSoalId = $bagianSoalTerpilih?->bank_soal_id;
        }

        $bankSoal = $bankSoalId !== null ? $this->bankSoalRepo->find($bankSoalId) : null;
        $jenisTesId = $filter['jenis_tes_id'] ?? null;
        if ($bankSoal !== null) {
            $jenisTesId = $bankSoal->jenis_tes_id;
        }
        $opsiKeterampilan = $this->opsiKeterampilan($bankSoal?->jenis_tes_id);
        $normalizedFilter = $filter;

        if ($bankSoalId !== null) {
            $normalizedFilter['bank_soal_id'] = $bankSoalId;
        }

        if ($bankSoal !== null) {
            $selectedPart = ! empty($filter['bagian_soal_id'])
                ? $bagianSoal->first(
                    fn (BagianSoal $part): bool => $part->id === $filter['bagian_soal_id']
                        && $part->bank_soal_id === $bankSoal->id,
                )
                : null;

            $keterampilanId = $filter['keterampilan'] ?? null;
            if ($keterampilanId === null && $selectedPart !== null) {
                $keterampilanId = $selectedPart->keterampilan_id;
            }

            $keterampilanIds = collect($opsiKeterampilan)
                ->pluck('value')
                ->map(static fn ($id): string => (string) $id);

            if ($keterampilanId !== null && ! $keterampilanIds->contains((string) $keterampilanId)) {
                $keterampilanId = null;
            }

            if ($keterampilanId === null) {
                $keterampilanId = $opsiKeterampilan[0]['value'] ?? null;
            }

            if ($keterampilanId !== null) {
                $normalizedFilter['keterampilan'] = $keterampilanId;
            } else {
                unset($normalizedFilter['keterampilan'], $normalizedFilter['bagian_soal_id']);
            }

            $selectedPart = ! empty($normalizedFilter['bagian_soal_id'])
                ? $bagianSoal->first(
                    fn (BagianSoal $part): bool => $part->id === $normalizedFilter['bagian_soal_id']
                        && $part->bank_soal_id === $bankSoal->id
                        && $part->keterampilan_id === $keterampilanId,
                )
                : null;

            if ($selectedPart === null) {
                $firstPart = $bagianSoal->first(
                    fn (BagianSoal $part): bool => $part->bank_soal_id === $bankSoal->id
                        && $part->keterampilan_id === $keterampilanId,
                );

                if ($firstPart !== null) {
                    $normalizedFilter['bagian_soal_id'] = $firstPart->id;
                } else {
                    unset($normalizedFilter['bagian_soal_id']);
                }
            }
        }

        $soal = $bankSoalId
            ? $this->soalRepo->paginateDenganFilter($normalizedFilter)
            : $this->soalRepo->paginatorKosong();

        return [
            'soal' => $soal,
            'jenisTes' => $this->jenisTesRepo->semuaAktifBerurutan(),
            'bankSoal' => $this->bankSoalRepo->semuaAktifDenganJenisTesDanJumlah($jenisTesId),
            'bankSoalTerpilih' => $bankSoal,
            'opsiKeterampilan' => $opsiKeterampilan,
            'bagianSoal' => $bagianSoal,
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
            'filter' => $normalizedFilter,
            'bankSoalTerpilihId' => $bankSoalId,
            'jenisTesTerpilihId' => $jenisTesId,
        ];
    }

    public function simpanPustaka(array $validated, ?Request $request): int
    {
        $storedFiles = [];

        try {
            $count = DB::transaction(function () use ($validated, $request, &$storedFiles) {
                $bankSoalId = $validated['bank_soal_id'];
                $this->pastikanBankBelumDipakai($bankSoalId);
                $materiId = $validated['materi_soal_id'] ?? null;

                if (! $materiId && ! empty($validated['materi_baru_judul'])) {
                    $materi = $this->buatMateriDariRequest($validated, $request, $storedFiles);
                    $materiId = $materi->id;
                }

                $materi = $materiId ? $this->materiSoalRepo->find($materiId) : null;

                if ($materi !== null) {
                    $this->pastikanMateriMilikBank($materi, $bankSoalId);
                }

                $urutan = $materiId ? $this->soalRepo->urutanMaksimumMateriSoal($materiId) + 1 : 1;

                $konfigurasiKebijakan = [];

                $baris = [];
                foreach ($validated['soal'] as $dataSoal) {
                    $keterampilanId = $dataSoal['keterampilan_id'];
                    $konfigurasi = $konfigurasiKebijakan[$keterampilanId] ??= $this->selesaikanKebijakanAtauGagal($keterampilanId, 'soal');

                    $this->pastikanKonteksSoal(
                        $bankSoalId,
                        $keterampilanId,
                        $dataSoal['bagian_soal_id'],
                        'soal',
                    );
                    $this->kebijakanMateri->pastikanMateriDiizinkan($konfigurasi, $materi, 'soal');

                    if ($konfigurasi->materi() === 'text') {
                        $this->pastikanIntegritasTeks($dataSoal, 'soal');
                    }

                    $baris[] = [
                        'bank_soal_id' => $bankSoalId,
                        'keterampilan_id' => $keterampilanId,
                        'bagian_soal_id' => $dataSoal['bagian_soal_id'],
                        'materi_soal_id' => $materiId,
                        'teks_soal' => $dataSoal['teks_soal'] ?? '',
                        'pilihan_a' => $dataSoal['pilihan_a'] ?? '',
                        'pilihan_b' => $dataSoal['pilihan_b'] ?? '',
                        'pilihan_c' => $dataSoal['pilihan_c'] ?? '',
                        'pilihan_d' => $dataSoal['pilihan_d'] ?? '',
                        'kunci_jawaban' => $dataSoal['kunci_jawaban'],
                        'urutan' => $urutan++,
                        'pembuat_id' => auth()->id(),
                    ];
                }

                $this->pastikanMateriSatuGrup($baris);

                $this->soalRepo->buatBanyak($baris);

                return count($baris);
            });
        } catch (\Throwable $e) {
            foreach ($storedFiles as $file) {
                Storage::disk('public')->delete($file);
            }

            throw $e;
        }

        return $count;
    }

    public function dataPratinjau(Soal $soal): array
    {
        $soal = $this->soalRepo->temukanDenganRelasi($soal->id);

        $soalDalamMateri = $soal->materi_soal_id
            ? $this->soalRepo->pratinjauDenganMateriSoal($soal->materi_soal_id)
            : new Collection([$soal]);

        $indeks = $soalDalamMateri->search(fn ($dataSoal) => $dataSoal->id === $soal->id);
        $indeks = $indeks === false ? 0 : $indeks;

        $materi = $this->kebijakanMateri->materiUntuk($soal->keterampilan_id);

        if ($materi !== null) {
            $soalDalamMateri->each(fn ($dataSoal) => $dataSoal->materi = $materi);
        }

        return [
            'soal' => $soalDalamMateri->values(),
            'indeks' => $indeks,
        ];
    }

    public function perbaruiPustaka(Soal $soal, array $validated): void
    {
        $this->pastikanSoalBelumDikunci($soal, $validated['bank_soal_id']);
        $this->pastikanKonteksSoal(
            $validated['bank_soal_id'],
            $validated['keterampilan_id'],
            $validated['bagian_soal_id'],
        );
        $this->pastikanMateriTidakBerubah($soal, $validated);

        $konfigurasi = $this->selesaikanKebijakanAtauGagal($validated['keterampilan_id'], 'keterampilan_id');

        $materi = ! empty($validated['materi_soal_id'])
            ? $this->materiSoalRepo->find($validated['materi_soal_id'])
            : null;

        if ($materi !== null) {
            $this->pastikanMateriMilikBank($materi, $validated['bank_soal_id']);
        }

        $this->kebijakanMateri->pastikanMateriDiizinkan($konfigurasi, $materi, 'materi_soal_id');
        $this->pastikanMateriSatuGrup([$validated], $soal->id);

        if ($konfigurasi->materi() === 'text') {
            $this->pastikanIntegritasTeks($validated, 'teks_soal');
        }

        $data = $validated;
        $data['pengubah_id'] = auth()->id();
        $this->soalRepo->update($soal, $data);
    }

    public function hapus(Soal $soal): void
    {
        $this->pastikanSoalBelumDikunci($soal);

        $this->soalRepo->delete($soal);
    }

    public function simpan(array $validated): void
    {
        DB::transaction(function () use ($validated): void {
            $this->pastikanBankBelumDipakai($validated['bank_soal_id']);
            $this->pastikanKonteksSoal(
                $validated['bank_soal_id'],
                $validated['keterampilan_id'],
                $validated['bagian_soal_id'],
            );
            $this->pastikanKebijakanMateriSoal($validated);
            $this->pastikanMateriSatuGrup([$validated]);

            $this->soalRepo->create(array_merge(
                $validated,
                ['pembuat_id' => auth()->id()]
            ));
        });
    }

    public function perbarui(Soal $soal, array $validated): void
    {
        $this->pastikanSoalBelumDikunci($soal, $validated['bank_soal_id']);
        $this->pastikanKonteksSoal(
            $validated['bank_soal_id'],
            $validated['keterampilan_id'],
            $validated['bagian_soal_id'],
        );
        $this->pastikanMateriTidakBerubah($soal, $validated);
        $this->pastikanKebijakanMateriSoal($validated);
        $this->pastikanMateriSatuGrup([$validated], $soal->id);

        $this->soalRepo->update($soal, array_merge(
            $validated,
            ['pengubah_id' => auth()->id()]
        ));
    }

    public function memilikiDuplikatDalam(array $items, callable $ambilKunci): bool
    {
        $keys = array_map($ambilKunci, $items);

        return count($keys) !== count(array_unique($keys));
    }

    private function buatMateriDariRequest(array $validated, ?Request $request, array &$berkasTersimpan): MateriSoal
    {
        $materi = $this->materiSoalRepo->create([
            'bank_soal_id' => $validated['bank_soal_id'],
            'judul' => $validated['materi_baru_judul'],
            'isi_teks' => ! empty($validated['materi_baru_isi_teks'])
                ? Purifier::clean($validated['materi_baru_isi_teks'])
                : null,
        ]);

        $berkasTersimpan = array_merge(
            $berkasTersimpan,
            $this->materiSoalService->lampirkanMediaUnggahan(
                $materi,
                $request,
                'materi_baru_berkas_audio',
                'materi_baru_berkas_gambar',
            ),
        );

        return $materi;
    }

    private function pastikanKonteksSoal(
        string $bankSoalId,
        string $keterampilanId,
        string $bagianSoalId,
        string $kunciKesalahan = 'bagian_soal_id',
    ): void {
        $bankSoal = $this->bankSoalRepo->find($bankSoalId);
        if ($bankSoal === null || ! $bankSoal->aktif) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Bank soal tidak aktif atau tidak ditemukan.',
            ]);
        }

        $keterampilan = $this->keterampilanRepo->find($keterampilanId);
        if ($keterampilan === null || ! $keterampilan->aktif) {
            throw ValidationException::withMessages([
                'keterampilan_id' => 'Keterampilan tidak aktif atau tidak ditemukan.',
            ]);
        }

        if ($keterampilan->jenis_tes_id !== $bankSoal->jenis_tes_id) {
            throw ValidationException::withMessages([
                'keterampilan_id' => 'Keterampilan harus berasal dari jenis tes yang sama dengan bank soal.',
            ]);
        }

        $bagianSoal = $this->bagianSoalRepo->find($bagianSoalId);

        if ($bagianSoal === null
            || ! $bagianSoal->aktif
            || $bagianSoal->keterampilan_id !== $keterampilanId
            || $bagianSoal->bank_soal_id !== $bankSoalId) {
            throw ValidationException::withMessages([
                $kunciKesalahan => 'Bagian soal harus aktif dan milik bank soal serta keterampilan yang sama.',
            ]);
        }
    }

    private function pastikanBankBelumDipakai(string $bankSoalId): void
    {
        if ($this->bankSoalRepo->digunakanDalamUjian($bankSoalId)) {
            throw ValidationException::withMessages([
                'bank_soal_id' => 'Soal tidak bisa ditambahkan karena bank soal sudah digunakan di ujian.',
            ]);
        }
    }

    private function pastikanSoalBelumDikunci(Soal $soal, ?string $bankSoalId = null): void
    {
        $bankIds = array_unique(array_filter([
            $soal->bank_soal_id,
            $bankSoalId,
        ]));

        foreach ($bankIds as $id) {
            if ($this->bankSoalRepo->digunakanDalamUjian($id)) {
                throw ValidationException::withMessages([
                    'bank_soal_id' => 'Soal tidak bisa diubah atau dihapus karena bank soal sudah digunakan di ujian.',
                ]);
            }
        }

        if ($this->soalRepo->digunakanDalamUjian($soal->id)) {
            throw ValidationException::withMessages([
                'soal' => 'Soal tidak bisa diubah atau dihapus karena sudah digunakan di ujian.',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $baris
     */
    private function pastikanMateriSatuGrup(array $baris, ?string $kecualiSoalId = null): void
    {
        $grupBaruPerMateri = [];

        foreach ($baris as $barisSoal) {
            if (empty($barisSoal['materi_soal_id'])) {
                continue;
            }

            $materiId = (string) $barisSoal['materi_soal_id'];
            $grupBaruPerMateri[$materiId][$this->kunciGrupMateri($barisSoal)] = true;
        }

        if ($grupBaruPerMateri === []) {
            return;
        }

        $grupLamaPerMateri = $this->soalRepo
            ->grupMateriSoal(array_keys($grupBaruPerMateri), $kecualiSoalId)
            ->groupBy('materi_soal_id');

        foreach ($grupBaruPerMateri as $materiId => $grupBaru) {
            if (count($grupBaru) > 1) {
                throw ValidationException::withMessages([
                    'materi_soal_id' => 'Materi soal hanya dapat digunakan pada satu group keterampilan dan bagian soal.',
                ]);
            }

            $grupBaruId = array_key_first($grupBaru);

            foreach ($grupLamaPerMateri->get($materiId, new Collection) as $grupLama) {
                if ($this->kunciGrupMateri([
                    'bank_soal_id' => $grupLama->bank_soal_id,
                    'keterampilan_id' => $grupLama->keterampilan_id,
                    'bagian_soal_id' => $grupLama->bagian_soal_id,
                ]) !== $grupBaruId) {
                    throw ValidationException::withMessages([
                        'materi_soal_id' => 'Materi soal sudah digunakan pada group keterampilan atau bagian soal lain.',
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function pastikanMateriTidakBerubah(Soal $soal, array $validated): void
    {
        if (($validated['materi_soal_id'] ?? null) === $soal->materi_soal_id) {
            return;
        }

        throw ValidationException::withMessages([
            'materi_soal_id' => 'Materi soal tidak dapat diubah setelah soal dibuat.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $barisSoal
     */
    private function kunciGrupMateri(array $barisSoal): string
    {
        return implode('|', [
            (string) ($barisSoal['bank_soal_id'] ?? ''),
            (string) ($barisSoal['keterampilan_id'] ?? ''),
            (string) ($barisSoal['bagian_soal_id'] ?? ''),
        ]);
    }

    private function selesaikanKebijakanAtauGagal(string $keterampilanId, string $kunciKesalahan): KonfigurasiKebijakanMateriSoal
    {
        try {
            return $this->kebijakanMateri->selesaikanAtauGagal($keterampilanId);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([$kunciKesalahan => $e->getMessage()]);
        }
    }

    private function pastikanMateriMilikBank(MateriSoal $materi, string $bankSoalId): void
    {
        if ($materi->bank_soal_id !== $bankSoalId) {
            throw ValidationException::withMessages(['materi_soal_id' => 'Materi soal harus berasal dari bank soal yang sama.']);
        }
    }

    private function pastikanKebijakanMateriSoal(array $validated): void
    {
        $konfigurasi = $this->selesaikanKebijakanAtauGagal($validated['keterampilan_id'], 'materi_soal_id');

        $materi = ! empty($validated['materi_soal_id'])
            ? $this->materiSoalRepo->find($validated['materi_soal_id'])
            : null;

        if ($materi !== null) {
            $this->pastikanMateriMilikBank($materi, $validated['bank_soal_id']);
        }

        $this->kebijakanMateri->pastikanMateriDiizinkan($konfigurasi, $materi, 'materi_soal_id');

        if ($konfigurasi->materi() === 'text') {
            $this->pastikanIntegritasTeks($validated, 'teks_soal');
        }
    }

    private function pastikanIntegritasTeks(array $dataSoal, string $kunciKesalahan): void
    {
        $pilihanKosong = collect(['pilihan_a', 'pilihan_b', 'pilihan_c', 'pilihan_d'])
            ->contains(fn ($kolom) => empty(trim($dataSoal[$kolom] ?? '')));

        if (empty(trim($dataSoal['teks_soal'] ?? '')) || $pilihanKosong) {
            throw ValidationException::withMessages([$kunciKesalahan => 'Soal tipe teks wajib memiliki teks soal dan seluruh pilihan jawaban.']);
        }
    }
}

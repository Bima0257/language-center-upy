<?php

namespace App\Modules\Ujian\Services;

use App\Imports\ImporSoal;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Mews\Purifier\Facades\Purifier;

class LayananSoal
{
    private const STATUSES = ['draft', 'approved', 'rejected'];

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

    public function status(): array
    {
        return self::STATUSES;
    }

    private function opsiKeterampilan(?string $jenisTesId = null): array
    {
        return $this->keterampilanRepo->semuaAktifBerurutan($jenisTesId)
            ->map(fn (Keterampilan $keterampilan) => ['value' => $keterampilan->id, 'label' => $keterampilan->nama, 'kode' => $keterampilan->kode])
            ->toArray();
    }

    public function dataTambah(?string $bankSoalTerpilih, array $filter): array
    {
        $bankSoal = $bankSoalTerpilih !== null ? $this->bankSoalRepo->find($bankSoalTerpilih) : null;

        return [
            'bankSoal' => $this->bankSoalRepo->semuaAktifBerurutan(),
            'opsiKeterampilan' => $this->opsiKeterampilan($bankSoal?->jenis_tes_id),
            'bagianSoal' => $this->bagianSoalRepo->semuaAktifBerurutan(),
            'materiSoal' => $this->materiSoalRepo->semuaBerurutan(),
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

        return [
            'soal' => $soalDenganRelasi,
            'bankSoal' => $this->bankSoalRepo->semuaAktifBerurutan(),
            'opsiKeterampilan' => $this->opsiKeterampilan($soalDenganRelasi?->bankSoal?->jenis_tes_id),
            'bagianSoal' => $this->bagianSoalRepo->semuaAktifBerurutan(),
            'materiSoal' => $this->materiSoalRepo->semuaBerurutan(),
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
        ];
    }

    public function dataDaftar(array $filter): array
    {
        $bankSoalId = $filter['bank_soal_id'] ?? null;
        $bankSoal = $bankSoalId !== null ? $this->bankSoalRepo->find($bankSoalId) : null;

        $soal = $bankSoalId
            ? $this->soalRepo->paginateDenganFilter($filter)
            : $this->soalRepo->paginatorKosong();

        return [
            'soal' => $soal,
            'jenisTes' => $this->jenisTesRepo->semuaAktifBerurutan(),
            'bankSoal' => $this->bankSoalRepo->semuaAktifDenganJenisTesBerurutan(),
            'opsiKeterampilan' => $this->opsiKeterampilan($bankSoal?->jenis_tes_id),
            'bagianSoal' => $this->bagianSoalRepo->semuaAktifBerurutan(),
            'kebijakanMateriSoal' => $this->kebijakanMateri->semuaUntukFrontend(),
            'status' => self::STATUSES,
            'filter' => $filter,
        ];
    }

    public function simpanPustaka(array $validated, ?Request $request): int
    {
        $storedFiles = [];

        try {
            $count = DB::transaction(function () use ($validated, $request, &$storedFiles) {
                $bankSoalId = $validated['bank_soal_id'];
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
                    $konfigurasi = $konfigurasiKebijakan[$keterampilanId] ??= $this->selesaikanKebijakanAtauGagal($bankSoalId, $keterampilanId, 'soal');

                    $this->pastikanKeterampilanBagian(
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
                        'status' => 'draft',
                        'pembuat_id' => auth()->id(),
                    ];
                }

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

        $materi = $this->kebijakanMateri->materiUntuk($soal->bank_soal_id, $soal->keterampilan_id);

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
        $this->pastikanKeterampilanBagian(
            $validated['bank_soal_id'],
            $validated['keterampilan_id'],
            $validated['bagian_soal_id'],
            'bagian_soal_id',
        );

        $konfigurasi = $this->selesaikanKebijakanAtauGagal($validated['bank_soal_id'], $validated['keterampilan_id'], 'keterampilan_id');

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

        $data = $validated;
        $data['pengubah_id'] = auth()->id();
        $data['status'] = 'draft';

        $this->soalRepo->update($soal, $data);
    }

    public function hapus(Soal $soal): void
    {
        if ($soal->bagianUjian()->exists()) {
            throw new \RuntimeException('Soal tidak bisa dihapus karena sudah dipakai di ujian.');
        }

        $this->soalRepo->delete($soal);
    }

    public function tinjau(Soal $soal, array $data): void
    {
        $this->soalRepo->update($soal, [
            'status' => $data['status'],
            'peninjau_id' => auth()->id(),
            'ditinjau_pada' => now(),
            'catatan_tinjauan' => $data['catatan_tinjauan'] ?? null,
        ]);
    }

    public function tinjauMassal(array $ids, array $data): int
    {
        return $this->soalRepo->perbaruiStatusMassal($ids, [
            'status' => $data['status'],
            'peninjau_id' => auth()->id(),
            'ditinjau_pada' => now(),
            'catatan_tinjauan' => $data['catatan_tinjauan'] ?? null,
        ]);
    }

    public function simpan(array $validated): void
    {
        $this->pastikanKebijakanMateriSoal($validated);

        $this->soalRepo->create(array_merge(
            $validated,
            ['pembuat_id' => auth()->id()]
        ));
    }

    public function simpanMassal(array $baris): int
    {
        return DB::transaction(function () use ($baris) {
            $data = array_map(
                fn ($barisSoal) => array_merge($barisSoal, ['pembuat_id' => auth()->id()]),
                $baris,
            );

            $this->soalRepo->buatBanyak($data);

            return count($data);
        });
    }

    public function perbarui(Soal $soal, array $validated): void
    {
        $this->pastikanKebijakanMateriSoal($validated);

        $this->soalRepo->update($soal, array_merge(
            $validated,
            ['pengubah_id' => auth()->id()]
        ));
    }

    public function imporBerkas(UploadedFile $file): void
    {
        Excel::import(new ImporSoal, $file);
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

    private function pastikanKeterampilanBagian(string $bankSoalId, string $keterampilanId, string $bagianSoalId, string $kunciKesalahan): void
    {
        $bagianSoal = $this->bagianSoalRepo->find($bagianSoalId);

        if ($bagianSoal === null || $bagianSoal->keterampilan_id !== $keterampilanId || $bagianSoal->bank_soal_id !== $bankSoalId) {
            throw ValidationException::withMessages([$kunciKesalahan => 'Bagian soal harus milik bank soal dan keterampilan yang sama dengan soal.']);
        }
    }

    private function selesaikanKebijakanAtauGagal(string $bankSoalId, string $keterampilanId, string $kunciKesalahan): KonfigurasiKebijakanMateriSoal
    {
        try {
            return $this->kebijakanMateri->selesaikanAtauGagal($bankSoalId, $keterampilanId);
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
        $konfigurasi = $this->selesaikanKebijakanAtauGagal($validated['bank_soal_id'], $validated['keterampilan_id'], 'materi_soal_id');

        $materi = ! empty($validated['materi_soal_id'])
            ? $this->materiSoalRepo->find($validated['materi_soal_id'])
            : null;

        if ($materi !== null) {
            $this->pastikanMateriMilikBank($materi, $validated['bank_soal_id']);
        }

        $this->kebijakanMateri->pastikanMateriDiizinkan($konfigurasi, $materi, 'materi_soal_id');
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

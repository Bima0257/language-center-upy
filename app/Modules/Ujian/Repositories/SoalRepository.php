<?php

namespace App\Modules\Ujian\Repositories;

use App\Models\BankSoal;
use App\Models\Keterampilan;
use App\Models\Soal;
use App\Modules\Ujian\Repositories\Contracts\SoalRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SoalRepository implements SoalRepositoryInterface
{
    public function paginateDenganFilter(array $filter = [], int $perPage = 20): LengthAwarePaginator
    {
        $keterampilanId = $filter['keterampilan_id'] ?? $filter['keterampilan'] ?? null;
        $bankSoalId = $filter['bank_soal_id'] ?? null;
        $status = $filter['status'] ?? null;
        $cari = $filter['cari'] ?? null;
        $materiId = $filter['materi_soal_id'] ?? null;
        $bagianSoalId = $filter['bagian_soal_id'] ?? null;

        return Soal::with(['materiSoal.mediaMateriSoal', 'bankSoal', 'keterampilan', 'bagianSoal', 'pembuat', 'peninjau'])
            ->when($keterampilanId, fn ($q) => $q->where('keterampilan_id', $keterampilanId))
            ->when($bankSoalId, fn ($q) => $q->where('bank_soal_id', $bankSoalId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($materiId, fn ($q) => $q->where('materi_soal_id', $materiId))
            ->when($bagianSoalId, fn ($q) => $q->where('bagian_soal_id', $bagianSoalId))
            ->when($cari, fn ($q) => $q->where('teks_soal', 'like', "%{$cari}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginatorKosong(int $perPage = 20): LengthAwarePaginator
    {
        return Soal::query()->whereRaw('1 = 0')->paginate($perPage);
    }

    public function find(string $id): ?Soal
    {
        return Soal::find($id);
    }

    public function temukanDenganRelasi(string $id): ?Soal
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'bankSoal', 'bagianSoal', 'pembuat', 'pengubah', 'peninjau'])->find($id);
    }

    public function create(array $data): Soal
    {
        return Soal::create($data);
    }

    public function buatBanyak(array $baris): void
    {
        foreach ($baris as $barisSoal) {
            Soal::create($barisSoal);
        }
    }

    public function update(Soal $soal, array $data): Soal
    {
        $soal->update($data);

        return $soal->fresh();
    }

    public function delete(Soal $soal): void
    {
        $soal->delete();
    }

    public function perbaruiStatusMassal(array $ids, array $data): int
    {
        return Soal::whereIn('id', $ids)->update($data);
    }

    public function urutanMaksimumMateriSoal(string $materiId): int
    {
        return Soal::where('materi_soal_id', $materiId)->max('urutan') ?? 0;
    }

    public function pratinjauDenganMateriSoal(string $materiId): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'keterampilan', 'bagianSoal'])
            ->where('materi_soal_id', $materiId)
            ->orderBy('urutan')
            ->get();
    }

    public function disetujuiDenganIdKeterampilanUntukJenisTes(string $keterampilanId, ?string $jenisTesId, ?string $bankId = null): Collection
    {
        return Soal::where('keterampilan_id', $keterampilanId)
            ->where('status', 'approved')
            ->when($bankId, fn ($q) => $q->where('bank_soal_id', $bankId))
            ->when($jenisTesId, fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('jenis_tes_id', $jenisTesId)))
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string>
     */
    public function idDisetujuiDalam(array $ids, string $keterampilanId, ?string $jenisTesId, ?string $bankId = null): array
    {
        return Soal::whereIn('id', $ids)
            ->where('status', 'approved')
            ->where('keterampilan_id', $keterampilanId)
            ->when($bankId, fn ($q) => $q->where('bank_soal_id', $bankId))
            ->when($jenisTesId, fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('jenis_tes_id', $jenisTesId)))
            ->pluck('id')
            ->all();
    }

    public function temukanDenganIdDenganMateriSoal(array $ids): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'keterampilan', 'bagianSoal'])
            ->whereIn('id', $ids)
            ->where('status', 'approved')
            ->get();
    }

    public function disetujuiDenganBankDanKeterampilanTanpa(string $bankId, string $keterampilanId, array $kecualiIds): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'bankSoal'])
            ->where('bank_soal_id', $bankId)
            ->where('keterampilan_id', $keterampilanId)
            ->where('status', 'approved')
            ->whereNotIn('id', $kecualiIds)
            ->orderBy('id')
            ->get();
    }

    public function cadanganDisetujuiDenganKeterampilan(array $keterampilanIds, ?string $jenisTesId, ?string $bankId, array $kecualiIds): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'keterampilan', 'bagianSoal'])
            ->whereIn('keterampilan_id', $keterampilanIds)
            ->where('status', 'approved')
            ->when($bankId, fn ($q) => $q->where('bank_soal_id', $bankId))
            ->when($jenisTesId, fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('jenis_tes_id', $jenisTesId)))
            ->whereNotIn('id', $kecualiIds)
            ->get();
    }

    public function bankDenganSoalDisetujuiDenganIdKeterampilanDanJenisTes(string $keterampilanId, ?string $jenisTesId, ?string $bankId = null): Collection
    {
        $bankIds = Soal::where('keterampilan_id', $keterampilanId)
            ->where('status', 'approved')
            ->when($jenisTesId, fn ($q) => $q->whereHas('bankSoal', fn ($b) => $b->where('jenis_tes_id', $jenisTesId)))
            ->when($bankId, fn ($q) => $q->where('bank_soal_id', $bankId))
            ->distinct()
            ->pluck('bank_soal_id')
            ->all();

        return BankSoal::whereIn('id', $bankIds)->orderBy('nama')->get();
    }

    public function count(): int
    {
        return Soal::count();
    }

    public function hitungDraf(): int
    {
        return Soal::where('status', 'draft')->count();
    }

    public function drafMenunggu(int $batas = 10): Collection
    {
        return Soal::with(['materiSoal.mediaMateriSoal', 'bankSoal', 'pembuat'])
            ->where('status', 'draft')
            ->latest()
            ->take($batas)
            ->get();
    }

    public function hitungDenganKeterampilan(): \Illuminate\Support\Collection
    {
        $rows = DB::select('
            SELECT keterampilan_id, COUNT(*) as total
            FROM soal
            WHERE keterampilan_id IS NOT NULL
            GROUP BY keterampilan_id
            ORDER BY total DESC
        ');

        $keterampilanIds = array_column($rows, 'keterampilan_id');
        $keterampilanMap = Keterampilan::whereIn('id', $keterampilanIds)->get()->keyBy('id');

        $result = new \Illuminate\Support\Collection;
        foreach ($rows as $row) {
            $keterampilan = $keterampilanMap->get($row->keterampilan_id);

            $result->push([
                'label' => $keterampilan->nama ?? 'Unknown',
                'count' => (int) $row->total,
            ]);
        }

        return $result;
    }

    public function hitungDenganStatus(): Collection
    {
        return Soal::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();
    }

    public function hitungDenganBank(): Collection
    {
        return Soal::selectRaw('bank_soal.nama as label, COUNT(*) as count')
            ->leftJoin('bank_soal', 'bank_soal.id', '=', 'soal.bank_soal_id')
            ->groupBy('bank_soal.nama')
            ->orderByDesc('count')
            ->get();
    }
}

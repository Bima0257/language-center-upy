<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\PerbaruiSoalPustakaRequest;
use App\Http\Requests\Ujian\SimpanSoalPustakaRequest;
use App\Http\Requests\Ujian\TinjauSoalRequest;
use App\Models\Soal;
use App\Modules\Ujian\Services\LayananSoal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PustakaSoalController extends Controller
{
    public function __construct(
        private LayananSoal $layananSoal,
    ) {}

    public function tambah(Request $request): Response
    {
        $bankSoalTerpilih = $request->query('bank_soal_id');

        return Inertia::render('BankSoal/TambahSoal', $this->layananSoal->dataTambah(
            $bankSoalTerpilih,
            $this->filterKembali($request),
        ));
    }

    public function ubah(Soal $soal): Response
    {
        return Inertia::render('BankSoal/UbahSoal', $this->layananSoal->dataUbah($soal));
    }

    public function daftar(Request $request): Response
    {
        return Inertia::render('BankSoal/PustakaSoal', $this->layananSoal->dataDaftar(
            $request->only(['keterampilan', 'bank_soal_id', 'status', 'cari', 'materi_soal_id', 'bagian_soal_id'])
        ));
    }

    public function simpan(SimpanSoalPustakaRequest $request): RedirectResponse
    {
        $jumlah = $this->layananSoal->simpanPustaka($request->validated(), $request);

        return to_route('pustaka-soal.daftar', $this->filterKembaliSetelahSimpan($request))
            ->with('success', "{$jumlah} soal berhasil ditambahkan ke bank soal.");
    }

    public function pratinjau(Soal $soal): Response
    {
        return Inertia::render('BankSoal/PratinjauSoal', $this->layananSoal->dataPratinjau($soal));
    }

    public function perbarui(PerbaruiSoalPustakaRequest $request, Soal $soal): RedirectResponse
    {
        $this->layananSoal->perbaruiPustaka($soal, $request->validated());

        return to_route('pustaka-soal.daftar', [
            'bank_soal_id' => $soal->bank_soal_id,
            'keterampilan' => $soal->keterampilan_id,
        ])->with('success', 'Soal berhasil diperbarui.');
    }

    public function hapus(Soal $soal): RedirectResponse
    {
        try {
            $this->layananSoal->hapus($soal);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function tinjau(TinjauSoalRequest $request, Soal $soal): RedirectResponse
    {
        $this->layananSoal->tinjau($soal, $request->validated());

        $label = $request->status === 'approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "Soal berhasil {$label}.");
    }

    public function tinjauMassal(TinjauSoalRequest $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids) || ! is_array($ids)) {
            return back()->with('error', 'Tidak ada soal yang dipilih.');
        }

        $jumlah = $this->layananSoal->tinjauMassal($ids, $request->validated());

        $label = $request->status === 'approved' ? 'disetujui' : 'ditolak';

        return back()->with('success', "{$jumlah} soal berhasil {$label}.");
    }

    private function filterKembali(Request $request): array
    {
        return array_filter(
            $request->only(['bank_soal_id', 'keterampilan', 'bagian_soal_id', 'status', 'cari']),
            fn ($value) => $value !== null && $value !== ''
        );
    }

    private function filterKembaliSetelahSimpan(Request $request): array
    {
        return array_filter([
            'bank_soal_id' => $request->input('bank_soal_id'),
            'keterampilan' => $request->input('_kembali_keterampilan') ?: $request->input('soal.0.keterampilan_id'),
            'bagian_soal_id' => $request->input('_kembali_bagian_soal_id'),
            'status' => $request->input('_kembali_status'),
            'cari' => $request->input('_kembali_cari'),
        ], fn ($value) => ! empty($value));
    }
}

<?php

namespace App\Modules\Ujian\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ujian\PerbaruiBankSoalRequest;
use App\Http\Requests\Ujian\SimpanBankSoalRequest;
use App\Models\BankSoal;
use App\Models\JenisTes;
use App\Modules\Ujian\Services\LayananBankSoal;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BankSoalController extends Controller
{
    public function __construct(
        private LayananBankSoal $layananBank,
    ) {}

    public function daftar(): Response
    {
        return Inertia::render('BankSoal/KelolaBankSoal/index', $this->layananBank->dataDaftar());
    }

    public function daftarKategori(JenisTes $jenisTes): Response
    {
        return Inertia::render('BankSoal/KelolaBankSoal/index', $this->layananBank->dataDaftar($jenisTes->id) + [
            'kategori' => $jenisTes,
        ]);
    }

    public function simpan(SimpanBankSoalRequest $request): RedirectResponse
    {
        $this->layananBank->simpan($request->validated());

        return back()->with('success', 'Bank soal berhasil dibuat.');
    }

    public function perbarui(PerbaruiBankSoalRequest $request, BankSoal $bankSoal): RedirectResponse
    {
        $this->layananBank->perbarui($bankSoal, $request->validated());

        return back()->with('success', 'Bank soal berhasil diperbarui.');
    }

    public function hapus(BankSoal $bankSoal): RedirectResponse
    {
        try {
            $this->layananBank->hapus($bankSoal);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Bank soal berhasil dihapus.');
    }
}

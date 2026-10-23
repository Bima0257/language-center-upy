<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataInduk\PerbaruiBagianSoalRequest;
use App\Http\Requests\DataInduk\PerbaruiFakultasRequest;
use App\Http\Requests\DataInduk\PerbaruiJenisTesRequest;
use App\Http\Requests\DataInduk\PerbaruiKeterampilanRequest;
use App\Http\Requests\DataInduk\PerbaruiProgramStudiRequest;
use App\Http\Requests\DataInduk\SimpanBagianSoalRequest;
use App\Http\Requests\DataInduk\SimpanFakultasRequest;
use App\Http\Requests\DataInduk\SimpanJenisTesRequest;
use App\Http\Requests\DataInduk\SimpanKeterampilanRequest;
use App\Http\Requests\DataInduk\SimpanProgramStudiMassalRequest;
use App\Http\Requests\DataInduk\SimpanProgramStudiRequest;
use App\Models\BagianSoal;
use App\Models\Fakultas;
use App\Models\JenisTes;
use App\Models\Keterampilan;
use App\Models\ProgramStudi;
use App\Modules\DataInduk\Services\LayananDataInduk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataIndukController extends Controller
{
    public function __construct(
        private LayananDataInduk $layananDataInduk,
    ) {}

    public function daftarKeterampilan(): Response
    {
        return Inertia::render('Admin/DataInduk/Keterampilan/index', $this->layananDataInduk->dataDaftarKeterampilan());
    }

    public function daftarKeterampilanPerJenisTes(JenisTes $jenisTes): Response
    {
        return Inertia::render(
            'Admin/DataInduk/Keterampilan/JenisTes/index',
            $this->layananDataInduk->dataDaftarKeterampilanPerJenisTes($jenisTes),
        );
    }

    public function simpanKeterampilan(SimpanKeterampilanRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatKeterampilan($request->validated());

        return back()->with('success', 'Keterampilan berhasil ditambahkan.');
    }

    public function perbaruiKeterampilan(PerbaruiKeterampilanRequest $request, Keterampilan $keterampilan): RedirectResponse
    {
        $this->layananDataInduk->perbaruiKeterampilan($keterampilan, $request->validated());

        return back()->with('success', 'Keterampilan berhasil diperbarui.');
    }

    public function hapusKeterampilan(Keterampilan $keterampilan): RedirectResponse
    {
        try {
            $this->layananDataInduk->hapusKeterampilan($keterampilan);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Keterampilan berhasil dihapus.');
    }

    public function susunUlangKeterampilan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'keterampilan' => ['required', 'array', 'min:1'],
            'keterampilan.*' => ['required', 'uuid', 'distinct'],
            'jenis_tes_id' => ['required', 'uuid', 'exists:jenis_tes,id'],
        ]);

        $this->layananDataInduk->susunUlangKeterampilan($validated['keterampilan'], $validated['jenis_tes_id']);

        return back()->with('success', 'Urutan keterampilan disimpan.');
    }

    public function daftarJenisTes(): Response
    {
        return Inertia::render('Admin/DataInduk/JenisTes/index', $this->layananDataInduk->dataDaftarJenisTes());
    }

    public function simpanJenisTes(SimpanJenisTesRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatJenisTes($request->validated());

        return back()->with('success', 'Jenis tes berhasil ditambahkan.');
    }

    public function perbaruiJenisTes(PerbaruiJenisTesRequest $request, JenisTes $jenisTes): RedirectResponse
    {
        $this->layananDataInduk->perbaruiJenisTes($jenisTes, $request->validated());

        return back()->with('success', 'Jenis tes berhasil diperbarui.');
    }

    public function hapusJenisTes(JenisTes $jenisTes): RedirectResponse
    {
        try {
            $this->layananDataInduk->hapusJenisTes($jenisTes);
        } catch (\RuntimeException) {
            return back()->with('error', 'Jenis tes tidak bisa dihapus karena masih digunakan atau terjadi konflik data.');
        }

        return back()->with('success', 'Jenis tes berhasil dihapus.');
    }

    public function daftarBagianSoal(Request $request): Response
    {
        $bankId = $request->query('bank_id');
        $jenisTesId = $request->query('jenis_tes_id');

        return Inertia::render('Admin/DataInduk/BagianSoal/index', $this->layananDataInduk->dataDaftarBagianSoal($bankId, $jenisTesId));
    }

    public function simpanBagianSoal(SimpanBagianSoalRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatBagianSoal($request->validated());

        return back()->with('success', 'Bagian soal berhasil ditambahkan.');
    }

    public function perbaruiBagianSoal(PerbaruiBagianSoalRequest $request, BagianSoal $bagianSoal): RedirectResponse
    {
        $this->layananDataInduk->perbaruiBagianSoal($bagianSoal, $request->validated());

        return back()->with('success', 'Bagian soal berhasil diperbarui.');
    }

    public function susunUlangBagianSoal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bagian_soal' => ['required', 'array', 'min:1'],
            'bagian_soal.*' => ['required', 'uuid', 'distinct'],
        ]);

        $this->layananDataInduk->susunUlangBagianSoal($validated['bagian_soal']);

        return back()->with('success', 'Urutan bagian soal disimpan.');
    }

    public function hapusBagianSoal(BagianSoal $bagianSoal): RedirectResponse
    {
        $this->layananDataInduk->hapusBagianSoal($bagianSoal);

        return back()->with('success', 'Bagian soal berhasil dihapus.');
    }

    public function daftarFakultas(): Response
    {
        return Inertia::render('Admin/DataInduk/Fakultas/index', $this->layananDataInduk->dataDaftarFakultas());
    }

    public function simpanFakultas(SimpanFakultasRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatFakultas($request->validated());

        return back()->with('success', 'Fakultas berhasil ditambahkan.');
    }

    public function perbaruiFakultas(PerbaruiFakultasRequest $request, Fakultas $fakultas): RedirectResponse
    {
        $this->layananDataInduk->perbaruiFakultas($fakultas, $request->validated());

        return back()->with('success', 'Fakultas berhasil diperbarui.');
    }

    public function hapusFakultas(Fakultas $fakultas): RedirectResponse
    {
        $this->layananDataInduk->hapusFakultas($fakultas);

        return back()->with('success', 'Fakultas berhasil dihapus.');
    }

    public function daftarProgramStudi(): Response
    {
        return Inertia::render('Admin/DataInduk/ProgramStudi/index', $this->layananDataInduk->dataDaftarProgramStudi());
    }

    public function daftarProgramStudiPerFakultas(Fakultas $fakultas): Response
    {
        return Inertia::render('Admin/DataInduk/ProgramStudi/Fakultas/index', $this->layananDataInduk->dataDaftarProgramStudiPerFakultas($fakultas));
    }

    public function tambahProgramStudiMassal(Fakultas $fakultas): Response
    {
        return Inertia::render('Admin/DataInduk/ProgramStudi/TambahMassal/index', $this->layananDataInduk->dataTambahProgramStudiMassal($fakultas));
    }

    public function simpanProgramStudi(SimpanProgramStudiRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatProgramStudi($request->validated());

        return back()->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function simpanProgramStudiMassal(SimpanProgramStudiMassalRequest $request, Fakultas $fakultas): RedirectResponse
    {
        $validated = $request->validated();
        $this->layananDataInduk->buatBanyakProgramStudi($fakultas, $validated['program_studi']);

        return redirect()
            ->route('admin.data-induk.program-studi.fakultas.daftar', $fakultas)
            ->with('success', 'Program studi berhasil ditambahkan.');
    }

    public function perbaruiProgramStudi(PerbaruiProgramStudiRequest $request, ProgramStudi $programStudi): RedirectResponse
    {
        $this->layananDataInduk->perbaruiProgramStudi($programStudi, $request->validated());

        return back()->with('success', 'Program studi berhasil diperbarui.');
    }

    public function hapusProgramStudi(ProgramStudi $programStudi): RedirectResponse
    {
        $this->layananDataInduk->hapusProgramStudi($programStudi);

        return back()->with('success', 'Program studi berhasil dihapus.');
    }
}

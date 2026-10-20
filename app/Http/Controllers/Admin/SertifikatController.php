<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataInduk\SimpanSertifikatRequest;
use App\Models\Sertifikat;
use App\Modules\DataInduk\Services\LayananDataInduk;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SertifikatController extends Controller
{
    public function __construct(
        private LayananDataInduk $layananDataInduk,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/DataInduk/Sertifikat', $this->layananDataInduk->dataDaftarSertifikat());
    }

    public function store(SimpanSertifikatRequest $request): RedirectResponse
    {
        $this->layananDataInduk->buatSertifikat($request->validated());

        return back()->with('success', 'Sertifikat berhasil diterbitkan.');
    }

    public function destroy(Sertifikat $sertifikat): RedirectResponse
    {
        $this->layananDataInduk->hapusSertifikat($sertifikat);

        return back()->with('success', 'Sertifikat berhasil dihapus.');
    }
}

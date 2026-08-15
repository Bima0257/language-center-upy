<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreCertificateRequest;
use App\Models\Certificate;
use App\Modules\MasterData\Services\MasterDataService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function __construct(
        private MasterDataService $masterData,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/MasterData/Certificates', $this->masterData->certificatesIndexData());
    }

    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $this->masterData->createCertificate($request->validated());

        return back()->with('success', 'Sertifikat berhasil diterbitkan.');
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $this->masterData->deleteCertificate($certificate);

        return back()->with('success', 'Sertifikat berhasil dihapus.');
    }
}

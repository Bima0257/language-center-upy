<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pengguna\UnggahIdentitasRequest;
use App\Modules\Pengguna\Services\LayananOrientasi;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OrientasiController extends Controller
{
    public function __construct(
        private LayananOrientasi $layananOrientasi,
    ) {}

    public function verifikasiIdentitas(): Response
    {
        return Inertia::render('Onboarding/IdentityVerification/index', $this->layananOrientasi->dataVerifikasiIdentitas(auth()->user()));
    }

    public function unggahIdentitas(UnggahIdentitasRequest $request): RedirectResponse
    {
        try {
            $this->layananOrientasi->unggahIdentitas(
                auth()->user(),
                $request->validated(),
                $request->file('foto_identitas'),
                $request->file('foto'),
            );
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['program_studi_id' => $exception->getMessage()]);
        }

        return redirect()->route('onboarding.verify-identity')->with('success', 'Identitas akademik berhasil disimpan.');
    }
}

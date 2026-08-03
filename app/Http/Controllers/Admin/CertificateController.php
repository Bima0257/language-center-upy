<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MasterData/Certificates', [
            'certificates' => Certificate::with([
                'examSession.user',
                'examSession.schedule.exam',
            ])->orderBy('issued_at', 'desc')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_session_id' => ['required', 'exists:exam_sessions,id'],
            'certificate_number' => ['required', 'string', 'max:50', 'unique:certificates,certificate_number'],
            'issued_at' => ['required', 'date'],
            'valid_until' => ['required', 'date', 'after:issued_at'],
        ]);

        Certificate::create($validated);

        return back()->with('success', 'Sertifikat berhasil diterbitkan.');
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        $certificate->delete();

        return back()->with('success', 'Sertifikat berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UploadIdentityRequest;
use App\Modules\Users\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingService $onboarding,
    ) {}

    public function verifyIdentity(): Response
    {
        return Inertia::render('Onboarding/IdentityVerification', $this->onboarding->verifyIdentityData(auth()->user()));
    }

    public function uploadIdentity(UploadIdentityRequest $request): RedirectResponse
    {
        $this->onboarding->uploadIdentity(
            auth()->user(),
            $request->validated(),
            $request->file('identity_photo'),
            $request->file('photo'),
        );

        return redirect()->route('onboarding.verify-identity')->with('success', 'Identitas berhasil diunggah. Menunggu verifikasi admin.');
    }
}

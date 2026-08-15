<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\RejectVerificationRequest;
use App\Models\User;
use App\Modules\Users\Services\UserVerificationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __construct(
        private UserVerificationService $verification,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/VerifyUsers', $this->verification->indexData());
    }

    public function approve(User $user): RedirectResponse
    {
        try {
            $this->verification->approve($user);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$user->name} berhasil diverifikasi.");
    }

    public function revert(User $user): RedirectResponse
    {
        try {
            $this->verification->revert($user);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Verifikasi {$user->name} dibatalkan.");
    }

    public function reject(RejectVerificationRequest $request, User $user): RedirectResponse
    {
        $this->verification->reject($user, $request->validated('reason'));

        return back()->with('success', "{$user->name} ditolak. Foto identitas dihapus.");
    }
}

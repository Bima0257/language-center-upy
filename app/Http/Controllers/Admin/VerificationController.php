<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function index(): Response
    {
        $users = User::role('student')
            ->whereHas('studentProfile', fn($q) => $q->whereNotNull('identity_photo'))
            ->with('studentProfile')
            ->select(['id', 'name', 'email', 'photo', 'created_at'])
            ->get();

        return Inertia::render('Admin/VerifyUsers', [
            'users' => $users,
        ]);
    }

    public function approve(User $user): RedirectResponse
    {
        $profile = $user->studentProfile;

        if (! $profile) {
            return back()->with('error', 'Profile mahasiswa tidak ditemukan.');
        }

        $profile->update([
            'is_verified' => true,
            'verified_at' => now(),
            'verified_by' => auth()->id(),
        ]);

        return back()->with('success', "{$user->name} berhasil diverifikasi.");
    }

    public function revert(User $user): RedirectResponse
    {
        $profile = $user->studentProfile;

        if (! $profile) {
            return back()->with('error', 'Profile mahasiswa tidak ditemukan.');
        }

        $profile->update([
            'is_verified' => false,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return back()->with('success', "Verifikasi {$user->name} dibatalkan.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        $profile = $user->studentProfile;

        if ($profile) {
            $profile->identity_photo = null;
            $profile->save();
        }

        $user->photo = null;
        $user->save();

        return back()->with('success', "{$user->name} ditolak. Foto identitas dihapus.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengguna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PenggunaController extends Controller
{
    private const ROLE_NAMES = ['student', 'admin', 'superadmin'];

    public function index(Request $request): Response
    {
        $request->validate([
            'role' => ['nullable', Rule::in(self::ROLE_NAMES)],
        ]);

        $query = Pengguna::with(['roles', 'profilMahasiswa.fakultas', 'profilMahasiswa.programStudi'])
            ->withCount('sesiUjian');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        if ($request->has('aktif')) {
            $query->where('aktif', $request->boolean('aktif'));
        }

        $users = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Pengguna/index', [
            'users' => $users,
            'roles' => self::ROLE_NAMES,
            'filters' => $request->only(['search', 'role', 'aktif']),
        ]);
    }

    public function update(Request $request, Pengguna $pengguna): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,'.$pengguna->id,
            'nomor_telepon' => 'nullable|string|max:20',
            'aktif' => 'sometimes|boolean',
            'role' => ['sometimes', Rule::in(self::ROLE_NAMES)],
        ]);

        $actor = $request->user();

        if ($actor === null) {
            abort(401);
        }

        $requestedRole = $validated['role'] ?? null;
        unset($validated['role']);

        if ($pengguna->hasRole('superadmin') && ! $actor->hasRole('superadmin')) {
            abort(403, 'Hanya superadmin yang dapat mengubah akun superadmin.');
        }

        if ($requestedRole === 'superadmin' && ! $actor->hasRole('superadmin')) {
            abort(403, 'Hanya superadmin yang dapat menetapkan role superadmin.');
        }

        $pengguna->update($validated);

        if ($requestedRole !== null && $pengguna->getRoleNames()->first() !== $requestedRole) {
            $pengguna->syncRoles([$requestedRole]);
        }

        return back()->with('success', "Data {$pengguna->name} berhasil diperbarui.");
    }

    public function ubahKeaktifan(Pengguna $pengguna): RedirectResponse
    {
        $pengguna->update(['aktif' => ! $pengguna->aktif]);

        $status = $pengguna->aktif ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$pengguna->name} berhasil {$status}.");
    }

    public function aturUlangSandi(Pengguna $pengguna): RedirectResponse
    {
        $pengguna->update(['password' => Hash::make('password')]);

        return back()->with('success', "Password {$pengguna->name} berhasil direset ke 'password'.");
    }
}

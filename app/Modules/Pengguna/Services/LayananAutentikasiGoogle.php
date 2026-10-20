<?php

namespace App\Modules\Pengguna\Services;

use App\Models\Pengguna;
use App\Modules\Pengguna\Repositories\Contracts\PenggunaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LayananAutentikasiGoogle
{
    public function __construct(
        private PenggunaRepositoryInterface $penggunaRepo,
    ) {}

    public function temukanDenganEmail(string $email): ?Pengguna
    {
        return $this->penggunaRepo->temukanDenganEmail($email);
    }

    public function sinkronkanIdentitasGoogle(Pengguna $pengguna, string $googleId, ?string $avatarGoogle): void
    {
        $this->penggunaRepo->update($pengguna, [
            'google_id' => $googleId,
            'avatar_google' => $avatarGoogle,
            'email_verified_at' => $pengguna->email_verified_at ?? now(),
        ]);
    }

    public function buatPenggunaGoogle(string $name, string $email, string $googleId, ?string $avatarGoogle): Pengguna
    {
        return DB::transaction(function () use ($name, $email, $googleId, $avatarGoogle) {
            $pengguna = $this->penggunaRepo->create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'avatar_google' => $avatarGoogle,
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]);

            $pengguna->assignRole('student');

            return $pengguna;
        });
    }
}

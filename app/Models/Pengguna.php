<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

/**
 * Pengguna aplikasi dan identitas autentikasi Laravel.
 *
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $nomor_telepon
 * @property string|null $foto
 * @property bool $aktif
 * @property string|null $google_id
 * @property string|null $avatar_google
 * @property string|null $remember_token
 * @property bool $terverifikasi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProfilMahasiswa|null $profilMahasiswa
 * @property-read Collection<int, SesiUjian> $sesiUjian
 */
#[Fillable(['name', 'email', 'password', 'nomor_telepon', 'foto', 'aktif', 'google_id', 'avatar_google', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class Pengguna extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<PenggunaFactory> */
    use HasFactory, HasRoles, HasUuids, Notifiable;

    protected $table = 'users';

    protected $appends = ['terverifikasi'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function profilMahasiswa(): HasOne
    {
        return $this->hasOne(ProfilMahasiswa::class, 'pengguna_id', 'id');
    }

    public function sesiUjian(): HasMany
    {
        return $this->hasMany(SesiUjian::class, 'pengguna_id', 'id');
    }

    public function profilTerverifikasi(): bool
    {
        if (! $this->adalahMahasiswa()) {
            return true;
        }

        $profil = $this->profilMahasiswa;

        return $profil !== null ? $profil->terverifikasi : false;
    }

    public function getTerverifikasiAttribute(): bool
    {
        return $this->profilTerverifikasi();
    }

    public function adalahMahasiswa(): bool
    {
        return $this->hasRole('student');
    }

    public function batalkanSesiLain(): void
    {
        DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('id', '!=', session()->getId())
            ->delete();
    }
}

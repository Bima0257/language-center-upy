<?php

namespace App\Models;

use App\Enums\JenisPeserta;
use Database\Factories\PenggunaFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

/**
 * Pengguna aplikasi dan identitas autentikasi Laravel.
 *
 * @property string $id
 * @property string $name
 * @property string|null $username
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $nomor_telepon
 * @property string|null $foto
 * @property bool $aktif
 * @property bool $status
 * @property Carbon|null $last_login
 * @property string|null $google_id
 * @property string|null $avatar_google
 * @property string|null $remember_token
 * @property bool $terverifikasi
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProfilPeserta|null $profilPeserta
 * @property-read Collection<int, ProgramStudi> $programStudis
 * @property-read Collection<int, SesiUjian> $sesiUjian
 * @property-read Collection<int, PendaftaranUjian> $pendaftaranUjian
 */
#[Fillable(['name', 'username', 'email', 'password', 'nomor_telepon', 'foto', 'aktif', 'status', 'last_login', 'google_id', 'avatar_google', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class Pengguna extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<PenggunaFactory> */
    use HasFactory, HasUuids, Notifiable;

    use HasRoles {
        roles as private spatieRoles;
    }

    protected $table = 'users';

    protected $appends = ['terverifikasi'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'aktif' => 'boolean',
            'status' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Pengguna $pengguna): void {
            if (filled($pengguna->username)) {
                return;
            }

            $base = Str::slug(Str::before((string) $pengguna->email, '@')) ?: 'user';
            $username = $base;
            $suffix = 1;

            while (Pengguna::query()->where('username', $username)->exists()) {
                $username = $base.'-'.$suffix;
                $suffix++;
            }

            $pengguna->username = $username;
        });

        static::saving(function (Pengguna $pengguna): void {
            if ($pengguna->isDirty('status')) {
                $pengguna->aktif = $pengguna->status;

                return;
            }

            if ($pengguna->isDirty('aktif')) {
                $pengguna->status = $pengguna->aktif;
            }
        });
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->spatieRoles();
    }

    public function profilPeserta(): HasOne
    {
        return $this->hasOne(ProfilPeserta::class, 'pengguna_id', 'id');
    }

    public function programStudis(): BelongsToMany
    {
        return $this->belongsToMany(
            ProgramStudi::class,
            'user_program_studi',
            'user_id',
            'program_studi_id',
        )->using(UserProgramStudi::class)->withTimestamps();
    }

    public function sesiUjian(): HasMany
    {
        return $this->hasMany(SesiUjian::class, 'pengguna_id', 'id');
    }

    public function pendaftaranUjian(): HasMany
    {
        return $this->hasMany(PendaftaranUjian::class, 'pengguna_id', 'id');
    }

    public function profilTerverifikasi(): bool
    {
        if (! $this->adalahPesertaUjian()) {
            return true;
        }

        $profil = $this->profilPeserta;

        if ($profil === null) {
            return false;
        }

        return match ($profil->jenis_peserta) {
            JenisPeserta::MAHASISWA => filled($profil->nomor_identitas)
                && filled($profil->fakultas_id)
                && filled($profil->program_studi_id)
                && filled($profil->tahun_mulai),
            JenisPeserta::DOSEN => filled($profil->nomor_identitas)
                && filled($profil->fakultas_id)
                && filled($profil->program_studi_id),
            JenisPeserta::UMUM => filled($profil->nomor_identitas)
                && filled($profil->institusi),
        };
    }

    public function getTerverifikasiAttribute(): bool
    {
        return $this->profilTerverifikasi();
    }

    public function adalahPesertaUjian(): bool
    {
        return $this->hasRole('peserta');
    }

    public function sudahMemakaiUjian(): bool
    {
        return $this->sesiUjian()->exists() || $this->pendaftaranUjian()->exists();
    }

    public function batalkanSesiLain(): void
    {
        DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('id', '!=', session()->getId())
            ->delete();
    }
}

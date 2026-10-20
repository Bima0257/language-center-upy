<?php

namespace App\Services;

use App\Models\Pengguna;
use App\Models\SesiUjian;
use Illuminate\Support\Facades\DB;

class LayananAnalitik
{
    public function data(): array
    {
        return [
            'penggunaDenganPeran' => $this->penggunaDenganPeran(),
            'sesiDenganBulan' => $this->sesiDenganBulan(),
            'skorDenganKeterampilan' => $this->skorDenganKeterampilan(),
            'tingkatLulus' => $this->tingkatLulus(),
            'mahasiswaTeratas' => $this->mahasiswaTeratas(),
            'partisipasiDenganFakultas' => $this->partisipasiDenganFakultas(),
        ];
    }

    private function penggunaDenganPeran(): array
    {
        return Pengguna::join('model_has_roles', 'users.id', '=', 'model_has_users')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select('roles.name as label', DB::raw('count(*) as count'))
            ->groupBy('roles.name')
            ->get()
            ->toArray();
    }

    private function sesiDenganBulan(): array
    {
        return SesiUjian::where('status', 'submitted')
            ->select(DB::raw("date_format(created_at, '%Y-%m') as label"), DB::raw('count(*) as count'))
            ->groupBy('label')
            ->orderBy('label')
            ->limit(12)
            ->get()
            ->toArray();
    }

    private function skorDenganKeterampilan(): array
    {
        $membaca = SesiUjian::where('status', 'submitted')
            ->whereNotNull('skor_membaca')
            ->avg('skor_membaca');

        $menyimak = SesiUjian::where('status', 'submitted')
            ->whereNotNull('skor_menyimak')
            ->avg('skor_menyimak');

        return [
            ['label' => 'Reading', 'count' => round($membaca ?? 0, 1)],
            ['label' => 'Listening', 'count' => round($menyimak ?? 0, 1)],
        ];
    }

    private function tingkatLulus(): array
    {
        $diserahkan = SesiUjian::where('status', 'submitted')->count();
        $passing = config('settings.passing_score', 300);
        $lulus = SesiUjian::where('status', 'submitted')
            ->where('skor_total', '>=', $passing)
            ->count();

        return [
            ['label' => 'Lulus', 'count' => $lulus],
            ['label' => 'Tidak Lulus', 'count' => max(0, $diserahkan - $lulus)],
        ];
    }

    private function mahasiswaTeratas(): array
    {
        return SesiUjian::where('status', 'submitted')
            ->whereNotNull('skor_total')
            ->with('pengguna:id,name,email')
            ->orderByDesc('skor_total')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'name' => optional($s->pengguna)->name ?? '-',
                'score' => $s->skor_total,
                'date' => $s->created_at->format('d M Y'),
            ])
            ->toArray();
    }

    private function partisipasiDenganFakultas(): array
    {
        return Pengguna::whereHas('profilMahasiswa')
            ->join('profil_mahasiswa', 'users.id', '=', 'profil_mahasiswa.pengguna_id')
            ->join('fakultas', 'profil_mahasiswa.fakultas_id', '=', 'fakultas.id')
            ->join('sesi_ujian', 'users.id', '=', 'sesi_ujian.pengguna_id')
            ->select('fakultas.nama as label', DB::raw('count(distinct users.id) as count'))
            ->groupBy('fakultas.nama')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }
}

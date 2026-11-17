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
            'skorDenganSection' => $this->skorDenganSection(),
            'tingkatLulus' => $this->tingkatLulus(),
            'pesertaTeratas' => $this->pesertaTeratas(),
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

    private function skorDenganSection(): array
    {
        return DB::table('hasil_penilaian')
            ->join('sesi_ujian', 'sesi_ujian.id', '=', 'hasil_penilaian.sesi_ujian_id')
            ->join('section', 'section.id', '=', 'hasil_penilaian.section_id')
            ->where('sesi_ujian.status', 'submitted')
            ->select('section.nama as label', DB::raw('round(avg(hasil_penilaian.skor_skala), 1) as count'))
            ->groupBy('section.nama')
            ->orderBy('section.nama')
            ->get()
            ->toArray();
    }

    private function tingkatLulus(): array
    {
        $lulus = SesiUjian::where('status', 'submitted')->where('lulus', true)->count();
        $tidakLulus = SesiUjian::where('status', 'submitted')->where('lulus', false)->count();
        $belumTerinterpretasi = SesiUjian::where('status', 'submitted')->whereNull('lulus')->count();

        $data = [
            ['label' => 'Lulus', 'count' => $lulus],
            ['label' => 'Tidak Lulus', 'count' => $tidakLulus],
        ];

        if ($belumTerinterpretasi > 0) {
            $data[] = ['label' => 'Belum Terinterpretasi', 'count' => $belumTerinterpretasi];
        }

        return $data;
    }

    private function pesertaTeratas(): array
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
        return Pengguna::whereHas('profilPeserta')
            ->join('profil_peserta', 'users.id', '=', 'profil_peserta.pengguna_id')
            ->join('fakultas', 'profil_peserta.fakultas_id', '=', 'fakultas.id')
            ->join('sesi_ujian', 'users.id', '=', 'sesi_ujian.pengguna_id')
            ->select('fakultas.nama as label', DB::raw('count(distinct users.id) as count'))
            ->groupBy('fakultas.nama')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }
}

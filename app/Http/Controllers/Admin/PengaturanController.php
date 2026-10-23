<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PengaturanController extends Controller
{
    private const DEFAULTS = [
        'general' => [
            'app_name' => 'UPY Language Test',
            'app_tagline' => 'Platform Tes Bahasa',
        ],
        'exam' => [
            'default_max_strikes' => '3',
            'default_duration_minutes' => '160',
        ],
        'scoring' => [
            'passing_score' => '300',
        ],
        'certificate' => [
            'certificate_validity_days' => '365',
        ],
    ];

    public function index(): Response
    {
        $this->pastikanBawaan();

        $pengaturan = Pengaturan::orderBy('kelompok')->orderBy('kunci')->get()->groupBy('kelompok');

        return Inertia::render('Admin/Pengaturan/index', [
            'pengaturan' => $pengaturan,
            'groups' => array_keys(self::DEFAULTS),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nilai' => 'required|array',
            'nilai.*.kunci' => 'required|string',
            'nilai.*.nilai' => 'nullable|string',
        ]);

        foreach ($validated['nilai'] as $item) {
            Pengaturan::atur($item['kunci'], $item['nilai']);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    private function pastikanBawaan(): void
    {
        foreach (self::DEFAULTS as $group => $items) {
            foreach ($items as $key => $value) {
                Pengaturan::firstOrCreate(
                    ['kunci' => $key],
                    ['nilai' => $value, 'kelompok' => $group],
                );
            }
        }
    }
}

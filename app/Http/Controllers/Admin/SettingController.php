<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const DEFAULTS = [
        'general' => [
            'app_name' => 'UPY Language Test',
            'app_tagline' => 'Platform Ujian TOEFL iBT',
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
        $this->ensureDefaults();

        $settings = Setting::orderBy('group')->orderBy('key')->get()->groupBy('group');

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'groups' => array_keys(self::DEFAULTS),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'values' => 'required|array',
            'values.*.key' => 'required|string',
            'values.*.value' => 'nullable|string',
        ]);

        foreach ($validated['values'] as $item) {
            Setting::set($item['key'], $item['value']);
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    private function ensureDefaults(): void
    {
        foreach (self::DEFAULTS as $group => $items) {
            foreach ($items as $key => $value) {
                Setting::firstOrCreate(
                    ['key' => $key],
                    ['value' => $value, 'group' => $group],
                );
            }
        }
    }
}

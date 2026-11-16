<?php

namespace Database\Seeders;

use App\Enums\JenisMedia;
use App\Enums\KetentuanMedia;
use App\Models\AturanMediaMateriSoal;
use App\Models\KebijakanMateriSoal;
use App\Models\Section;
use Illuminate\Database\Seeder;

class KebijakanMateriSoalSeeder extends Seeder
{
    /**
     * Definisi aturan default per skill code.
     *
     * @return array<string, array{ketentuan_materi: KetentuanMedia, aturan_media: array<string, KetentuanMedia>}>
     */
    public static function definisi(): array
    {
        return [
            'reading' => [
                'ketentuan_materi' => KetentuanMedia::OPTIONAL,
                'aturan_media' => [
                    JenisMedia::TEXT->value => KetentuanMedia::OPTIONAL,
                    JenisMedia::AUDIO->value => KetentuanMedia::FORBIDDEN,
                    JenisMedia::IMAGE->value => KetentuanMedia::OPTIONAL,
                ],
            ],
            'listening' => [
                'ketentuan_materi' => KetentuanMedia::REQUIRED,
                'aturan_media' => [
                    JenisMedia::TEXT->value => KetentuanMedia::FORBIDDEN,
                    JenisMedia::AUDIO->value => KetentuanMedia::REQUIRED,
                    JenisMedia::IMAGE->value => KetentuanMedia::OPTIONAL,
                ],
            ],
        ];
    }

    public function run(): void
    {
        $definisi = self::definisi();

        foreach (Section::query()->get() as $section) {
            $kodeSection = strtolower($section->nama);
            $definisiSection = $definisi[$kodeSection] ?? null;

            if ($definisiSection === null) {
                continue;
            }

            $kebijakan = KebijakanMateriSoal::firstOrCreate(
                ['section_id' => $section->id],
                ['ketentuan_materi' => $definisiSection['ketentuan_materi']],
            );

            foreach ($definisiSection['aturan_media'] as $jenisMedia => $ketentuan) {
                AturanMediaMateriSoal::firstOrCreate(
                    ['kebijakan_materi_soal_id' => $kebijakan->id, 'jenis_media' => $jenisMedia],
                    ['ketentuan' => $ketentuan],
                );
            }
        }
    }
}

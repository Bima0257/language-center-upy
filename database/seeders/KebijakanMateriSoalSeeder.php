<?php

namespace Database\Seeders;

use App\Enums\JenisMedia;
use App\Enums\KetentuanMedia;
use App\Models\AturanMediaMateriSoal;
use App\Models\BankSoal;
use App\Models\KebijakanMateriSoal;
use App\Models\Keterampilan;
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
        $daftarBankSoal = BankSoal::all();

        foreach ($daftarBankSoal as $bankSoal) {
            foreach ($definisi as $kodeKeterampilan => $definisiKeterampilan) {
                $namaKeterampilan = $kodeKeterampilan === 'reading' ? 'Reading' : 'Listening';
                $keterampilan = Keterampilan::where('jenis_tes_id', $bankSoal->jenis_tes_id)
                    ->where('nama', $namaKeterampilan)
                    ->first();

                if ($keterampilan === null) {
                    continue;
                }

                $kebijakan = KebijakanMateriSoal::updateOrCreate(
                    ['bank_soal_id' => $bankSoal->id, 'keterampilan_id' => $keterampilan->id],
                    ['ketentuan_materi' => $definisiKeterampilan['ketentuan_materi']],
                );

                foreach ($definisiKeterampilan['aturan_media'] as $jenisMedia => $ketentuan) {
                    AturanMediaMateriSoal::updateOrCreate(
                        ['kebijakan_materi_soal_id' => $kebijakan->id, 'jenis_media' => $jenisMedia],
                        ['ketentuan' => $ketentuan],
                    );
                }
            }
        }
    }
}

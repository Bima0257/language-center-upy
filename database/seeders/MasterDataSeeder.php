<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ExamType;
use App\Models\Faculty;
use App\Models\QuestionBank;
use App\Models\ScoringRule;
use App\Models\ScoreInterpretation;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $reading = Skill::create(['name' => 'Reading', 'code' => 'reading', 'is_active' => true]);
        $listening = Skill::create(['name' => 'Listening', 'code' => 'listening', 'is_active' => true]);
        Skill::create(['name' => 'Speaking', 'code' => 'speaking', 'is_active' => true]);
        Skill::create(['name' => 'Writing', 'code' => 'writing', 'is_active' => true]);

        $fkip = Faculty::create(['name' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'code' => 'FKIP', 'is_active' => true]);
        $ft = Faculty::create(['name' => 'Fakultas Teknik', 'code' => 'FT', 'is_active' => true]);

        Department::create(['faculty_id' => $fkip->id, 'name' => 'Pendidikan Bahasa Inggris', 'code' => 'PBI', 'is_active' => true]);
        Department::create(['faculty_id' => $fkip->id, 'name' => 'Pendidikan Matematika', 'code' => 'PMAT', 'is_active' => true]);
        Department::create(['faculty_id' => $ft->id, 'name' => 'Teknik Informatika', 'code' => 'TI', 'is_active' => true]);

        QuestionBank::create(['name' => 'Bank Soal 2022', 'description' => 'Kumpulan soal tryout tahun 2022', 'is_active' => true]);
        QuestionBank::create(['name' => 'Bank Soal 2023', 'description' => 'Kumpulan soal tryout tahun 2023', 'is_active' => true]);

        $toefl = ExamType::firstOrCreate([
            'name' => 'TOEFL iBT',
        ], [
            'max_strikes' => 3,
            'description' => 'Test of English as a Foreign Language',
            'is_active' => true,
        ]);

        // Conversion table: raw 0-50 -> scaled 100-200 (2x + 100)
        $conversion = [];
        for ($raw = 0; $raw <= 50; $raw++) {
            $conversion[(string) $raw] = 100 + ($raw * 2);
        }

        foreach (['reading', 'listening'] as $skill) {
            ScoringRule::create([
                'exam_type_id' => $toefl->id,
                'section_skill' => $skill,
                'conversion_table' => $conversion,
                'max_raw' => 50,
                'max_scaled' => 200,
            ]);
        }

        $interpretations = [
            ['min' => 380, 'max' => 400, 'cefr' => 'B2', 'label' => 'Excellent', 'passing' => true, 'desc' => 'Very Good User'],
            ['min' => 360, 'max' => 379, 'cefr' => 'B2-', 'label' => 'Very Good', 'passing' => true, 'desc' => 'Independent User'],
            ['min' => 340, 'max' => 359, 'cefr' => 'B1+', 'label' => 'Good', 'passing' => true, 'desc' => 'Upper Intermediate'],
            ['min' => 320, 'max' => 339, 'cefr' => 'B1', 'label' => 'Competent', 'passing' => true, 'desc' => 'Intermediate'],
            ['min' => 300, 'max' => 319, 'cefr' => 'B1-', 'label' => 'Satisfactory', 'passing' => true, 'desc' => 'Lower Intermediate'],
            ['min' => 280, 'max' => 299, 'cefr' => 'A2+', 'label' => 'Developing', 'passing' => false, 'desc' => 'Elementary Plus'],
            ['min' => 260, 'max' => 279, 'cefr' => 'A2', 'label' => 'Basic', 'passing' => false, 'desc' => 'Elementary'],
            ['min' => 240, 'max' => 259, 'cefr' => 'A1+', 'label' => 'Limited', 'passing' => false, 'desc' => 'High Beginner'],
            ['min' => 200, 'max' => 239, 'cefr' => 'A1', 'label' => 'Beginning', 'passing' => false, 'desc' => 'Beginner'],
        ];

        foreach ($interpretations as $interp) {
            ScoreInterpretation::create([
                'exam_type_id' => $toefl->id,
                'min_score' => $interp['min'],
                'max_score' => $interp['max'],
                'cefr_level' => $interp['cefr'],
                'level_label' => $interp['label'],
                'is_passing' => $interp['passing'],
                'description' => $interp['desc'],
            ]);
        }
    }
}

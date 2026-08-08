<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ExamType;
use App\Models\Faculty;
use App\Models\QuestionBank;
use App\Models\ScoringRule;
use App\Models\ScoreInterpretation;
use App\Models\Skill;
use App\Models\SkillPart;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $toefl = ExamType::firstOrCreate([
            'name' => 'TOEFL iBT',
        ], [
            'max_strikes' => 3,
            'description' => 'Test of English as a Foreign Language',
            'is_active' => true,
        ]);

        $reading = Skill::firstOrCreate(['code' => 'reading'], [
            'exam_type_id' => $toefl->id,
            'name' => 'Reading',
            'is_active' => true,
        ]);
        $reading->update(['exam_type_id' => $toefl->id]);

        $listening = Skill::firstOrCreate(['code' => 'listening'], [
            'exam_type_id' => $toefl->id,
            'name' => 'Listening',
            'is_active' => true,
        ]);
        $listening->update(['exam_type_id' => $toefl->id]);

        SkillPart::firstOrCreate(
            ['skill_id' => $reading->id, 'name' => 'Part 1'],
            ['order' => 1, 'directions' => 'Read each passage carefully. Answer questions based on the information given in the passage.', 'is_active' => true],
        );
        SkillPart::firstOrCreate(
            ['skill_id' => $reading->id, 'name' => 'Part 2'],
            ['order' => 2, 'directions' => 'A word or phrase is missing in each of the sentences below. Select the best answer to complete the sentence.', 'is_active' => true],
        );
        SkillPart::firstOrCreate(
            ['skill_id' => $listening->id, 'name' => 'Part 1'],
            ['order' => 1, 'directions' => 'Listen to each short conversation and question. Select the best answer to each question based on what is stated or implied by the speakers.', 'is_active' => true],
        );
        SkillPart::firstOrCreate(
            ['skill_id' => $listening->id, 'name' => 'Part 2'],
            ['order' => 2, 'directions' => 'Listen to each longer conversation or talk. Answer the questions based on the information you hear.', 'is_active' => true],
        );
        SkillPart::firstOrCreate(
            ['skill_id' => $listening->id, 'name' => 'Part 3'],
            ['order' => 3, 'directions' => 'Listen to each lecture. Answer the questions based on the information presented in the lecture.', 'is_active' => true],
        );

        $fkip = Faculty::firstOrCreate(['code' => 'FKIP'], ['name' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'is_active' => true]);
        $ft = Faculty::firstOrCreate(['code' => 'FT'], ['name' => 'Fakultas Teknik', 'is_active' => true]);

        Department::firstOrCreate(['faculty_id' => $fkip->id, 'code' => 'PBI'], ['name' => 'Pendidikan Bahasa Inggris', 'is_active' => true]);
        Department::firstOrCreate(['faculty_id' => $fkip->id, 'code' => 'PMAT'], ['name' => 'Pendidikan Matematika', 'is_active' => true]);
        Department::firstOrCreate(['faculty_id' => $ft->id, 'code' => 'TI'], ['name' => 'Teknik Informatika', 'is_active' => true]);

        QuestionBank::firstOrCreate(['name' => 'Bank Soal 2022'], ['exam_type_id' => $toefl->id, 'description' => 'Kumpulan soal tryout tahun 2022', 'is_active' => true]);
        QuestionBank::firstOrCreate(['name' => 'Bank Soal 2023'], ['exam_type_id' => $toefl->id, 'description' => 'Kumpulan soal tryout tahun 2023', 'is_active' => true]);

        // Conversion table: raw 0-50 -> scaled 100-200 (2x + 100)
        $conversion = [];
        for ($raw = 0; $raw <= 50; $raw++) {
            $conversion[(string) $raw] = 100 + ($raw * 2);
        }

        foreach (['reading', 'listening'] as $skill) {
            ScoringRule::firstOrCreate(
                ['exam_type_id' => $toefl->id, 'section_skill' => $skill],
                [
                    'conversion_table' => $conversion,
                    'max_raw' => 50,
                    'max_scaled' => 200,
                ],
            );
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
            ScoreInterpretation::firstOrCreate(
                ['exam_type_id' => $toefl->id, 'min_score' => $interp['min']],
                [
                    'max_score' => $interp['max'],
                    'cefr_level' => $interp['cefr'],
                    'level_label' => $interp['label'],
                    'is_passing' => $interp['passing'],
                    'description' => $interp['desc'],
                ],
            );
        }
    }
}

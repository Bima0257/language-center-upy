<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamSection;
use App\Models\ExamSession;
use App\Models\ExamType;
use App\Models\Passage;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Skill;
use App\Models\SkillPart;
use App\Models\User;
use App\Models\ViolationLog;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        if (Exam::where('title', 'TOEFL iBT Try Out 1')->exists()) {
            $this->command->info('ExamSeeder dilewati — data demo sudah ada.');

            return;
        }

        $toefl = ExamType::firstOrCreate([
            'name' => 'TOEFL iBT',
        ], [
            'max_strikes' => 3,
            'description' => 'Test of English as a Foreign Language',
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'exam_type_id' => $toefl->id,
            'title' => 'TOEFL iBT Try Out 1',
            'description' => 'Try out TOEFL iBT full simulation — Reading & Listening sections.',
            'mode' => 'tryout',
            'duration_minutes' => 71,
            'is_active' => true,
        ]);

        $readingSkill = Skill::where('code', 'reading')->firstOrFail();
        $listeningSkill = Skill::where('code', 'listening')->firstOrFail();

        $readingPart = SkillPart::where('skill_id', $readingSkill->id)->orderBy('order')->first();
        $listeningPart = SkillPart::where('skill_id', $listeningSkill->id)->orderBy('order')->first();

        $reading = ExamSection::create([
            'exam_id' => $exam->id,
            'skill_id' => $readingSkill->id,
            'title' => 'Reading Section',
            'order' => 1,
            'duration_minutes' => 35,
            'instructions' => 'Baca setiap passage dengan saksama. Jawab soal berdasarkan informasi yang diberikan dalam passage.',
            'total_questions' => 4,
        ]);

        $listening = ExamSection::create([
            'exam_id' => $exam->id,
            'skill_id' => $listeningSkill->id,
            'title' => 'Listening Section',
            'order' => 2,
            'duration_minutes' => 36,
            'instructions' => 'Dengarkan setiap audio. Jawab soal berdasarkan informasi dari audio.',
            'total_questions' => 4,
        ]);

        $bank = QuestionBank::firstOrCreate(['name' => 'Bank Soal Demo'], [
            'exam_type_id' => $toefl->id,
            'is_active' => true,
        ]);
        $bank->update(['exam_type_id' => $toefl->id]);

        $readingPassage = Passage::create([
            'title' => 'The History of Solar Energy',
            'type' => 'text',
            'content_text' => 'Solar energy has been used by humans for thousands of years...',
        ]);

        $listeningPassage = Passage::create([
            'title' => 'Marine Biology',
            'type' => 'audio',
            'content_text' => 'Lecture on the relationship between coral reefs and their environment...',
        ]);

        $readingQuestions = [
            [
                'question_text' => 'What is the main topic of the passage?',
                'a' => 'The invention of solar panels in the 20th century',
                'b' => 'The history and development of solar energy',
                'c' => 'How ancient Greeks designed their buildings',
                'd' => 'The efficiency of modern solar cells',
                'answer' => 'B',
            ],
            [
                'question_text' => 'When was the photovoltaic effect first discovered?',
                'a' => '7th century BC',
                'b' => '1839',
                'c' => '1954',
                'd' => '2020',
                'answer' => 'B',
            ],
            [
                'question_text' => 'According to the passage, what efficiency did the first silicon solar cell achieve?',
                'a' => 'About 2%',
                'b' => 'About 6%',
                'c' => 'About 15%',
                'd' => 'About 22%',
                'answer' => 'B',
            ],
            [
                'question_text' => 'What does the passage imply about modern solar panels?',
                'a' => 'They are less efficient than early models',
                'b' => 'They were invented by the ancient Greeks',
                'c' => 'They are significantly more efficient than the first solar cell',
                'd' => 'They are no longer growing as an energy source',
                'answer' => 'C',
            ],
        ];

        $listeningQuestions = [
            [
                'question_text' => 'What is the lecture mainly about?',
                'a' => 'The differences between coral and algae',
                'b' => 'The relationship between coral reefs and their environment',
                'c' => 'How climate change affects ocean temperatures',
                'd' => 'Methods for preserving marine ecosystems',
                'answer' => 'B',
            ],
            [
                'question_text' => 'According to the professor, what causes coral bleaching?',
                'a' => 'An increase in marine predators',
                'b' => 'The loss of symbiotic algae due to warm water',
                'c' => 'Pollution from coastal development',
                'd' => 'Overfishing in reef areas',
                'answer' => 'B',
            ],
            [
                'question_text' => 'What does the professor say about the relationship between corals and algae?',
                'a' => 'Corals provide shelter, and algae provide food through photosynthesis',
                'b' => 'Algae compete with corals for space',
                'c' => 'Corals eat the algae for nutrition',
                'd' => 'Algae block sunlight from reaching the corals',
                'answer' => 'A',
            ],
            [
                'question_text' => 'What can be inferred about the professor view on coral reef preservation?',
                'a' => 'She believes it is too late to save most reefs',
                'b' => 'She thinks local efforts are more effective than global ones',
                'c' => 'She believes addressing climate change is essential',
                'd' => 'She thinks coral reefs can adapt to warmer temperatures',
                'answer' => 'C',
            ],
        ];

        foreach ($readingQuestions as $i => $q) {
            Question::create([
                'question_bank_id' => $bank->id,
                'passage_id' => $readingPassage->id,
                'skill_id' => $readingSkill->id,
                'skill_part_id' => $readingPart?->id,
                'type' => 'multiple_choice',
                'question_text' => $q['question_text'],
                'option_a' => $q['a'],
                'option_b' => $q['b'],
                'option_c' => $q['c'],
                'option_d' => $q['d'],
                'correct_answer' => $q['answer'],
                'order' => $i + 1,
                'status' => 'approved',
            ]);
        }

        foreach ($listeningQuestions as $i => $q) {
            Question::create([
                'question_bank_id' => $bank->id,
                'passage_id' => $listeningPassage->id,
                'skill_id' => $listeningSkill->id,
                'skill_part_id' => $listeningPart?->id,
                'type' => 'multiple_choice',
                'question_text' => $q['question_text'],
                'option_a' => $q['a'],
                'option_b' => $q['b'],
                'option_c' => $q['c'],
                'option_d' => $q['d'],
                'correct_answer' => $q['answer'],
                'order' => $i + 1,
                'status' => 'approved',
            ]);
        }

        $student = User::role('student')->first();

        if ($student) {
            $schedule = ExamSchedule::create([
                'exam_id' => $exam->id,
                'title' => 'Demo Session — 10 Juli 2026',
                'scheduled_start' => now()->subHour(),
                'scheduled_end' => now()->addHours(2),
                'max_participants' => 30,
                'is_active' => true,
            ]);

            $activeSession = ExamSession::create([
                'exam_schedule_id' => $schedule->id,
                'user_id' => $student->id,
                'status' => 'in_progress',
                'started_at' => now()->subMinutes(25),
                'current_section_id' => $reading->id,
                'violation_strikes' => 1,
            ]);

            ViolationLog::create([
                'exam_session_id' => $activeSession->id,
                'type' => 'tab_switch',
                'severity' => 'minor',
                'description' => 'Peserta pindah tab ke aplikasi lain.',
                'strike_count' => 1,
            ]);

            $schedule2 = ExamSchedule::create([
                'exam_id' => $exam->id,
                'title' => 'Flagged Demo — 9 Juli 2026',
                'scheduled_start' => now()->subDay(),
                'scheduled_end' => now()->addDay(),
                'max_participants' => 30,
                'is_active' => true,
            ]);

            $flaggedSession = ExamSession::create([
                'exam_schedule_id' => $schedule2->id,
                'user_id' => $student->id,
                'status' => 'terminated',
                'started_at' => now()->subHours(2),
                'terminated_at' => now()->subHour(),
                'termination_reason' => '3 strikes violation',
                'violation_strikes' => 3,
                'is_flagged' => true,
                'flag_reason' => 'Pelanggaran mencapai 3 strike.',
            ]);

            ViolationLog::create([
                'exam_session_id' => $flaggedSession->id,
                'type' => 'tab_switch',
                'severity' => 'minor',
                'description' => 'Peserta pindah tab — strike 1.',
                'strike_count' => 1,
            ]);

            ViolationLog::create([
                'exam_session_id' => $flaggedSession->id,
                'type' => 'fullscreen_exit',
                'severity' => 'minor',
                'description' => 'Peserta keluar dari mode layar penuh — strike 2.',
                'strike_count' => 2,
            ]);

            ViolationLog::create([
                'exam_session_id' => $flaggedSession->id,
                'type' => 'tab_switch',
                'severity' => 'minor',
                'description' => 'Peserta pindah tab kembali — strike 3. Sesi dihentikan otomatis.',
                'strike_count' => 3,
            ]);

            $schedule3 = ExamSchedule::create([
                'exam_id' => $exam->id,
                'title' => 'Submitted Demo — 9 Juli 2026',
                'scheduled_start' => now()->subDays(2),
                'scheduled_end' => now()->subDay(),
                'max_participants' => 30,
                'is_active' => false,
            ]);

            ExamSession::create([
                'exam_schedule_id' => $schedule3->id,
                'user_id' => $student->id,
                'status' => 'submitted',
                'started_at' => now()->subDays(1),
                'submitted_at' => now()->subDays(1)->addMinutes(35),
                'violation_strikes' => 0,
                'score_reading' => 22.5,
                'score_listening' => 18.0,
                'score_total' => 40.5,
            ]);
        }
    }
}

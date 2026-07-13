<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamSection;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuestionGroup;
use App\Models\ExamSchedule;
use App\Models\ExamSession;
use App\Models\ViolationLog;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $toefl = ExamType::create([
            'name' => 'TOEFL iBT',
            'target_language' => 'English',
            'section_config' => json_encode([
                ['skill' => 'reading',    'name' => 'Reading Section',    'default_duration' => 35, 'default_questions' => 20],
                ['skill' => 'listening',  'name' => 'Listening Section',  'default_duration' => 36, 'default_questions' => 28],
                ['skill' => 'speaking',   'name' => 'Speaking Section',   'default_duration' => 16, 'default_questions' => 4],
                ['skill' => 'writing',    'name' => 'Writing Section',    'default_duration' => 29, 'default_questions' => 2],
            ]),
            'max_strikes' => 3,
        ]);

        $exam = Exam::create([
            'exam_type_id' => $toefl->id,
            'title' => 'TOEFL iBT Try Out 1',
            'description' => 'Try out TOEFL iBT full simulation — Reading & Listening sections.',
            'mode' => 'tryout',
            'duration_minutes' => 71,
            'is_active' => true,
        ]);

        $reading = ExamSection::create([
            'exam_id' => $exam->id,
            'skill' => 'reading',
            'type' => 'reading',
            'title' => 'Reading Section',
            'order' => 1,
            'duration_minutes' => 35,
            'instructions' => 'Baca setiap passage dengan saksama. Jawab soal berdasarkan informasi yang diberikan dalam passage.',
            'total_questions' => 4,
            'navigation_enabled' => true,
        ]);

        $listening = ExamSection::create([
            'exam_id' => $exam->id,
            'skill' => 'listening',
            'type' => 'listening',
            'title' => 'Listening Section',
            'order' => 2,
            'duration_minutes' => 36,
            'instructions' => 'Dengarkan setiap audio. Jawab soal berdasarkan informasi dari audio.',
            'total_questions' => 4,
            'navigation_enabled' => true,
        ]);

        $group = QuestionGroup::create([
            'exam_section_id' => $reading->id,
            'title' => 'Passage 1 — The History of Solar Energy',
            'passage_text' => 'Solar energy has been used by humans for thousands of years...',
            'order' => 1,
        ]);

        Question::create([
            'question_group_id' => $group->id,
            'type' => 'multiple_choice',
            'question_text' => 'What is the main topic of the passage?',
            'options' => json_encode([
                ['key' => 'A', 'text' => 'The invention of solar panels in the 20th century'],
                ['key' => 'B', 'text' => 'The history and development of solar energy'],
                ['key' => 'C', 'text' => 'How ancient Greeks designed their buildings'],
                ['key' => 'D', 'text' => 'The efficiency of modern solar cells'],
            ]),
            'correct_answer' => 'B',
            'points' => 1,
            'order' => 1,
        ]);

        Question::create([
            'question_group_id' => $group->id,
            'type' => 'multiple_choice',
            'question_text' => 'When was the photovoltaic effect first discovered?',
            'options' => json_encode([
                ['key' => 'A', 'text' => '7th century BC'],
                ['key' => 'B', 'text' => '1839'],
                ['key' => 'C', 'text' => '1954'],
                ['key' => 'D', 'text' => '2020'],
            ]),
            'correct_answer' => 'B',
            'points' => 1,
            'order' => 2,
        ]);

        Question::create([
            'question_group_id' => $group->id,
            'type' => 'multiple_choice',
            'question_text' => 'According to the passage, what efficiency did the first silicon solar cell achieve?',
            'options' => json_encode([
                ['key' => 'A', 'text' => 'About 2%'],
                ['key' => 'B', 'text' => 'About 6%'],
                ['key' => 'C', 'text' => 'About 15%'],
                ['key' => 'D', 'text' => 'About 22%'],
            ]),
            'correct_answer' => 'B',
            'points' => 1,
            'order' => 3,
        ]);

        Question::create([
            'question_group_id' => $group->id,
            'type' => 'multiple_choice',
            'question_text' => 'What does the passage imply about modern solar panels?',
            'options' => json_encode([
                ['key' => 'A', 'text' => 'They are less efficient than early models'],
                ['key' => 'B', 'text' => 'They were invented by the ancient Greeks'],
                ['key' => 'C', 'text' => 'They are significantly more efficient than the first solar cell'],
                ['key' => 'D', 'text' => 'They are no longer growing as an energy source'],
            ]),
            'correct_answer' => 'C',
            'points' => 1,
            'order' => 4,
        ]);

        $listeningGroup = QuestionGroup::create([
            'exam_section_id' => $listening->id,
            'title' => 'Lecture — Marine Biology',
            'audio_file' => null,
            'order' => 1,
        ]);

        Question::create([
            'question_group_id' => $listeningGroup->id,
            'type' => 'multiple_choice',
            'question_text' => 'What is the lecture mainly about?',
            'options' => json_encode([
                ['key' => 'A', 'text' => 'The differences between coral and algae'],
                ['key' => 'B', 'text' => 'The relationship between coral reefs and their environment'],
                ['key' => 'C', 'text' => 'How climate change affects ocean temperatures'],
                ['key' => 'D', 'text' => 'Methods for preserving marine ecosystems'],
            ]),
            'correct_answer' => 'B',
            'points' => 1,
            'order' => 1,
        ]);

        Question::create([
            'question_group_id' => $listeningGroup->id,
            'type' => 'multiple_choice',
            'question_text' => 'According to the professor, what causes coral bleaching?',
            'options' => json_encode([
                ['key' => 'A', 'text' => 'An increase in marine predators'],
                ['key' => 'B', 'text' => 'The loss of symbiotic algae due to warm water'],
                ['key' => 'C', 'text' => 'Pollution from coastal development'],
                ['key' => 'D', 'text' => 'Overfishing in reef areas'],
            ]),
            'correct_answer' => 'B',
            'points' => 1,
            'order' => 2,
        ]);

        Question::create([
            'question_group_id' => $listeningGroup->id,
            'type' => 'multiple_choice',
            'question_text' => 'What does the professor say about the relationship between corals and algae?',
            'options' => json_encode([
                ['key' => 'A', 'text' => 'Corals provide shelter, and algae provide food through photosynthesis'],
                ['key' => 'B', 'text' => 'Algae compete with corals for space'],
                ['key' => 'C', 'text' => 'Corals eat the algae for nutrition'],
                ['key' => 'D', 'text' => 'Algae block sunlight from reaching the corals'],
            ]),
            'correct_answer' => 'A',
            'points' => 1,
            'order' => 3,
        ]);

        Question::create([
            'question_group_id' => $listeningGroup->id,
            'type' => 'multiple_choice',
            'question_text' => 'What can be inferred about the professor\'s view on coral reef preservation?',
            'options' => json_encode([
                ['key' => 'A', 'text' => 'She believes it is too late to save most reefs'],
                ['key' => 'B', 'text' => 'She thinks local efforts are more effective than global ones'],
                ['key' => 'C', 'text' => 'She believes addressing climate change is essential'],
                ['key' => 'D', 'text' => 'She thinks coral reefs can adapt to warmer temperatures'],
            ]),
            'correct_answer' => 'C',
            'points' => 1,
            'order' => 4,
        ]);

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
                'metadata' => json_encode(['url_detected' => 'https://www.google.com']),
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
